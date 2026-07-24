<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\Calculation;
use App\Models\Supplier;
use App\Models\PurchaseHistory;
use App\Models\Product;
use App\Models\ProductGroup;
use Carbon\Carbon;
use App\Services\SupplierScoreCalculator;

class LaporanTerpusatExport implements WithMultipleSheets
{
    use Exportable;

    protected $jenis_laporan;
    protected $filterData;

    public function __construct(string $jenis_laporan, array $filterData)
    {
        $this->jenis_laporan = $jenis_laporan;
        $this->filterData = $filterData;
    }

    public function sheets(): array
    {
        $sheets = [];
        $jenis = $this->jenis_laporan;
        $data = $this->filterData;

        if (in_array($jenis, ['evaluasi', 'ranking', 'pembobotan'])) {
            $calc = Calculation::with([
                'recaDetails' => fn($q) => $q->orderBy('contribution_rank'), 
                'mautRankings' => fn($q) => $q->orderBy('rank'),
                'selectedSuppliers'
            ])->findOrFail($data['calculation_id']);

            if ($jenis == 'evaluasi') {
                $sheets[] = new LaporanSheet('Ringkasan', 'reports.excel.sheet', ['type' => 'ringkasan', 'calc' => $calc]);
                $sheets[] = new LaporanSheet('Supplier Dinilai', 'reports.excel.sheet', ['type' => 'supplier_dinilai', 'calc' => $calc]);
                $sheets[] = new LaporanSheet('Bobot RECA', 'reports.excel.sheet', ['type' => 'bobot_reca', 'calc' => $calc]);
                
                $limit = $data['batas_ranking'] === 'Top 5' ? 5 : ($data['batas_ranking'] === 'Top 10' ? 10 : 999);
                $ranks = $calc->mautRankings->take($limit);
                $sheets[] = new LaporanSheet('Ranking MAUT', 'reports.excel.sheet', ['type' => 'ranking_maut', 'calc' => $calc, 'ranks' => $ranks]);
            } elseif ($jenis == 'ranking') {
                $limit = $data['batas_ranking'] === 'Top 5' ? 5 : ($data['batas_ranking'] === 'Top 10' ? 10 : 999);
                $ranks = $calc->mautRankings->take($limit);
                $sheets[] = new LaporanSheet('Ranking MAUT', 'reports.excel.sheet', ['type' => 'ranking_maut', 'calc' => $calc, 'ranks' => $ranks]);
            } elseif ($jenis == 'pembobotan') {
                $sheets[] = new LaporanSheet('Bobot RECA', 'reports.excel.sheet', ['type' => 'bobot_reca', 'calc' => $calc]);
            }
        } elseif ($jenis == 'penilaian') {
            $suppliers = Supplier::where('kategori', $data['kategori'])->get();
            $start = Carbon::parse($data['period_start']);
            $end = Carbon::parse($data['period_end']);
            
            $results = [];
            $calculator = new SupplierScoreCalculator();
            foreach ($suppliers as $sup) {
                $scores = $calculator->calculateForSupplier(
                    $sup, $start, $end, 
                    $data['product_group_id'] ?? null, 
                    $data['product_id'] ?? null
                );
                $c1 = $scores['scores']['C1']['score'] ?? 0;
                $c2 = $scores['scores']['C2']['score'] ?? 0;
                $c3 = $scores['scores']['C3']['score'] ?? 0;
                $c4 = $scores['scores']['C4']['score'] ?? 0;
                $c5 = $scores['scores']['C5']['score'] ?? 0;
                $total = $c1 + $c2 + $c3 + $c4 + $c5;
                $isComplete = $c1 && $c2 && $c3 && $c4 && $c5;
                if ($scores['transaction_count'] == 0 && empty($c1)) continue;

                $results[] = [
                    'supplier_code' => $sup->kode_supplier,
                    'supplier_name' => $sup->nama_supplier,
                    'c1' => $c1, 'c2' => $c2, 'c3' => $c3, 'c4' => $c4, 'c5' => $c5,
                    'total' => $total,
                    'status_data' => $isComplete ? 'Lengkap' : 'Tidak Lengkap',
                ];
            }
            usort($results, fn($a, $b) => $b['total'] <=> $a['total']);
            $sheets[] = new LaporanSheet('Penilaian Kinerja', 'reports.excel.sheet', ['type' => 'penilaian', 'results' => $results, 'filter' => $data]);
        } elseif ($jenis == 'historis') {
            $query = PurchaseHistory::query();
            if (!empty($data['supplier_id'])) $query->where('supplier_id', $data['supplier_id']);
            if (!empty($data['product_id'])) {
                $prod = Product::find($data['product_id']);
                if ($prod) $query->where('kode_produk', $prod->kode_produk)->orWhere('nama_produk', 'like', '%'.$prod->nama_produk.'%');
            } elseif (!empty($data['product_group_id'])) {
                $grp = ProductGroup::find($data['product_group_id']);
                if ($grp) {
                    $names = $grp->products()->pluck('nama_produk');
                    if ($names->isEmpty()) $names = collect([$grp->nama_kelompok_produk]);
                    $query->where(function($q) use ($names) {
                        foreach($names as $n) $q->orWhere('nama_produk', 'like', '%'.$n.'%');
                    });
                }
            }
            if (!empty($data['period_start']) && !empty($data['period_end'])) {
                $query->whereBetween('tanggal_pembelian', [$data['period_start'], $data['period_end']]);
            }
            $sheets[] = new LaporanSheet('Historis Pembelian', 'reports.excel.sheet', ['type' => 'historis', 'results' => $query->latest('tanggal_pembelian')->get()]);
        } elseif ($jenis == 'riwayat') {
            $query = Calculation::with('productGroup', 'product')->latest();
            if (!empty($data['kategori'])) $query->where('supplier_category', $data['kategori']);
            if (!empty($data['product_group_id'])) $query->where('product_group_id', $data['product_group_id']);
            if (!empty($data['status'])) $query->where('status', $data['status']);
            $sheets[] = new LaporanSheet('Riwayat Perhitungan', 'reports.excel.sheet', ['type' => 'riwayat', 'results' => $query->get()]);
        } elseif ($jenis == 'supplier') {
            $query = Supplier::query();
            if (!empty($data['kategori'])) $query->where('kategori', $data['kategori']);
            if (!empty($data['status'])) $query->where('status_kerja_sama', $data['status']);
            $sheets[] = new LaporanSheet('Data Supplier', 'reports.excel.sheet', ['type' => 'supplier', 'results' => $query->get()]);
        }

        return $sheets;
    }
}

class LaporanSheet implements FromView, WithTitle
{
    private $title;
    private $view;
    private $data;

    public function __construct($title, $view, $data)
    {
        $this->title = $title;
        $this->view = $view;
        $this->data = $data;
    }

    public function view(): View
    {
        return view($this->view, $this->data);
    }

    public function title(): string
    {
        return $this->title;
    }
}
