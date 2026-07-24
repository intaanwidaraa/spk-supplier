<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LaporanTerpusatExport;
use App\Models\Calculation;
use App\Models\Supplier;
use App\Models\PurchaseHistory;
use App\Models\Product;
use App\Models\ProductGroup;
use Carbon\Carbon;
use App\Services\SupplierScoreCalculator;

class LaporanController extends Controller
{
    public function exportExcel(Request $request)
    {
        $filterData = $request->all();
        $jenis = $filterData['jenis_laporan'] ?? 'laporan';
        
        $filename = 'Laporan_' . ucfirst($jenis) . '_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new LaporanTerpusatExport($jenis, $filterData), $filename);
    }

    public function cetakPDF(Request $request, SupplierScoreCalculator $calculator)
    {
        $data = $request->all();
        $jenis = $data['jenis_laporan'] ?? null;
        if (!$jenis) return "Filter jenis laporan tidak valid.";

        $reportData = [];
        
        if (in_array($jenis, ['evaluasi', 'ranking', 'pembobotan'])) {
            $calc = Calculation::with([
                'recaDetails' => fn($q) => $q->orderBy('contribution_rank'), 
                'mautRankings' => fn($q) => $q->orderBy('rank'),
                'selectedSuppliers'
            ])->findOrFail($data['calculation_id']);
            $reportData['calculation'] = $calc;

            if ($jenis == 'evaluasi' || $jenis == 'ranking') {
                $limit = $data['batas_ranking'] === 'Top 5' ? 5 : ($data['batas_ranking'] === 'Top 10' ? 10 : 999);
                $reportData['rankings'] = $calc->mautRankings->take($limit);
            }
        } elseif ($jenis == 'penilaian') {
            $suppliers = Supplier::where('kategori', $data['kategori'])->get();
            $start = Carbon::parse($data['period_start']);
            $end = Carbon::parse($data['period_end']);
            
            $results = [];
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
            $reportData['penilaian'] = collect($results);
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
            $reportData['historis'] = $query->latest('tanggal_pembelian')->get();
        } elseif ($jenis == 'riwayat') {
            $query = Calculation::with('productGroup', 'product')->latest();
            if (!empty($data['kategori'])) $query->where('supplier_category', $data['kategori']);
            if (!empty($data['product_group_id'])) $query->where('product_group_id', $data['product_group_id']);
            if (!empty($data['status'])) $query->where('status', $data['status']);
            $reportData['riwayat'] = $query->get();
        } elseif ($jenis == 'supplier') {
            $reportData['supplier'] = \App\Services\ReportService::getSupplierReportQuery($data)->get();
        }

        // Format mapping judul
        $judulMap = [
            'evaluasi' => 'Laporan Hasil Evaluasi Supplier RECA-MAUT',
            'ranking' => 'Laporan Perankingan Supplier MAUT',
            'pembobotan' => 'Laporan Pembobotan Kriteria RECA',
            'penilaian' => 'Laporan Penilaian Kinerja Supplier',
            'historis' => 'Laporan Data Historis Pembelian',
            'riwayat' => 'Laporan Riwayat Perhitungan',
            'supplier' => 'Laporan Data Master Supplier'
        ];
        
        $judul = $judulMap[$jenis] ?? 'Laporan Sistem';
        $user = auth()->user()->name ?? 'Administrator';

        return view('reports.cetak-laporan', [
            'jenis' => $jenis,
            'filterData' => $data,
            'reportData' => $reportData,
            'judul' => $judul,
            'user' => $user
        ]);
    }
}
