<?php

namespace App\Services;

use App\Models\Criteria;
use App\Models\EvaluationPeriod;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Supplier;
use App\Models\SupplierPerformanceAssessment;
use App\Models\SupplierPerformanceScoreDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PerformanceCalculationService
{
    /**
     * Calculate performance for a product group, optionally for one specific product detail.
     * Returns the number of assessments created/updated.
     */
    public function calculateForGroup(
        EvaluationPeriod $period,
        ProductGroup $productGroup,
        ?Product $product = null
    ): int {
        // Build list of product names to search in purchase_histories
        $productNames = $product
            ? collect([$product->nama_produk])
            : $productGroup->products()->pluck('nama_produk');

        if ($productNames->isEmpty()) {
            $productNames = collect([$productGroup->nama_kelompok_produk]);
        }

        // Find suppliers from pivot table (preferred source)
        $supplierIdsFromPivot = DB::table('supplier_products')
            ->join('products', 'products.id', '=', 'supplier_products.product_id')
            ->where('products.product_group_id', $productGroup->id)
            ->when($product, fn ($q) => $q->where('products.id', $product->id))
            ->pluck('supplier_products.supplier_id')
            ->unique();

        // Also find suppliers from purchase history (fallback)
        $supplierIdsFromHistory = DB::table('purchase_histories')
            ->where(function ($query) use ($productNames) {
                foreach ($productNames as $name) {
                    $query->orWhere('nama_produk', 'like', '%'.$name.'%');
                }
            })
            ->whereBetween('tanggal_pembelian', [$period->start_date, $period->end_date])
            ->whereNotNull('supplier_id')
            ->pluck('supplier_id')
            ->unique();

        $supplierIds = $supplierIdsFromPivot->merge($supplierIdsFromHistory)->unique()->values();

        if ($supplierIds->isEmpty()) {
            return 0;
        }

        $suppliers = Supplier::whereIn('id', $supplierIds)
            ->where('kategori', $period->product_category)
            ->get();

        if ($suppliers->isEmpty()) {
            return 0;
        }

        // Load all purchase histories matching product names + period
        $allHistories = DB::table('purchase_histories')
            ->where(function ($query) use ($productNames) {
                foreach ($productNames as $name) {
                    $query->orWhere('nama_produk', 'like', '%'.$name.'%');
                }
            })
            ->whereBetween('tanggal_pembelian', [$period->start_date, $period->end_date])
            ->whereNotNull('supplier_id')
            ->whereIn('supplier_id', $supplierIds)
            ->get();

        $criteria = Criteria::orderBy('kode_kriteria')->get()->keyBy('kode_kriteria');

        $count = 0;

        foreach ($suppliers as $supplier) {
            $histories = $allHistories->where('supplier_id', $supplier->id)->values();
            if ($histories->isEmpty()) {
                continue;
            }

            $scores = [
                'c1' => $this->calculateC1($histories),
                'c2' => $this->calculateC2($histories, $allHistories, $supplierIds),
                'c3' => $this->calculateC3($supplier),
                'c4' => $this->calculateC4($histories),
                'c5' => $this->calculateC5($histories),
            ];

            $totalScore = round(array_sum(array_column($scores, 'score')) / 5, 4);

            $assessment = SupplierPerformanceAssessment::updateOrCreate(
                [
                    'evaluation_period_id' => $period->id,
                    'product_group_id' => $productGroup->id,
                    'product_id' => $product?->id,
                    'supplier_id' => $supplier->id,
                ],
                [
                    'product_category' => $period->product_category,
                    'assessment_date' => now()->toDateString(),
                    'c1_score' => $scores['c1']['score'],
                    'c2_score' => $scores['c2']['score'],
                    'c3_score' => $scores['c3']['score'],
                    'c4_score' => $scores['c4']['score'],
                    'c5_score' => $scores['c5']['score'],
                    'total_score' => $totalScore,
                    'status' => 'Draft',
                    'is_auto_calculated' => true,
                    'calculated_at' => now(),
                ]
            );

            $criteriaKeys = ['C1' => 'c1', 'C2' => 'c2', 'C3' => 'c3', 'C4' => 'c4', 'C5' => 'c5'];
            foreach ($criteriaKeys as $kode => $key) {
                $criterion = $criteria->get($kode);
                if (! $criterion) {
                    continue;
                }

                $scoreData = $scores[$key];

                SupplierPerformanceScoreDetail::updateOrCreate(
                    [
                        'supplier_performance_assessment_id' => $assessment->id,
                        'criterion_id' => $criterion->id,
                    ],
                    [
                        'raw_value' => $scoreData['raw_value'] ?? null,
                        'raw_value_label' => $scoreData['raw_value_label'] ?? null,
                        'auto_score' => $scoreData['score'],
                        'final_score' => $scoreData['score'],
                        'score_category' => $scoreData['score_category'] ?? null,
                        'calculation_description' => $scoreData['description'] ?? null,
                        'is_manual_override' => false,
                        'override_reason' => null,
                        'overridden_by' => null,
                        'overridden_at' => null,
                    ]
                );
            }

            $count++;
        }

        return $count;
    }

    /**
     * Legacy method — backward compatibility.
     */
    public function calculate(EvaluationPeriod $period, Product $product): int
    {
        $group = $product->productGroup;
        if (! $group) {
            return 0;
        }

        return $this->calculateForGroup($period, $group, $product);
    }

    // -------------------------------------------------------------------------
    // C1 - Kualitas Produk
    // -------------------------------------------------------------------------
    private function calculateC1(Collection $histories): array
    {
        $totalOrders = $histories->count();
        $repeatOrders = $histories->groupBy('nomor_po')->count();
        $receivedOrders = $histories->whereNotNull('tanggal_penerimaan')->count();

        $repeatRate = $totalOrders > 0 ? ($repeatOrders / max($totalOrders, 1)) * 100 : 0;
        $fulfilledRate = $totalOrders > 0 ? ($receivedOrders / $totalOrders) * 100 : 0;
        $combinedRate = ($repeatRate + $fulfilledRate) / 2;

        $score = match (true) {
            $combinedRate >= 90 => 5,
            $combinedRate >= 75 => 4,
            $combinedRate >= 55 => 3,
            $combinedRate >= 35 => 2,
            default => 1,
        };

        $categoryMap = [5 => 'Sangat Baik', 4 => 'Baik', 3 => 'Cukup', 2 => 'Buruk', 1 => 'Sangat Buruk'];

        return [
            'score' => $score,
            'raw_value' => round($combinedRate, 2),
            'raw_value_label' => "Total PO: {$totalOrders}, Diterima: {$receivedOrders}, Rate: ".round($combinedRate, 2).'%',
            'score_category' => $categoryMap[$score],
            'description' => "C1 dihitung dari konsistensi penerimaan ({$receivedOrders}/{$totalOrders}) dan repeat PO ({$repeatOrders}/{$totalOrders}). Rate gabungan: ".round($combinedRate, 2).'%.',
        ];
    }

    // -------------------------------------------------------------------------
    // C2 - Harga (output = skor BENEFIT 1-5, 5 = paling kompetitif)
    // -------------------------------------------------------------------------
    private function calculateC2(Collection $histories, Collection $allHistories, Collection $supplierIds): array
    {
        $totalQty = $histories->sum('qty_pembelian');
        $weightedAvg = $totalQty > 0
            ? $histories->sum(fn ($h) => $h->harga_satuan * $h->qty_pembelian) / $totalQty
            : 0;

        $supplierAvgPrices = collect();
        foreach ($supplierIds as $sid) {
            $sh = $allHistories->where('supplier_id', $sid);
            $qty = $sh->sum('qty_pembelian');
            if ($qty > 0) {
                $supplierAvgPrices->push($sh->sum(fn ($h) => $h->harga_satuan * $h->qty_pembelian) / $qty);
            }
        }

        $sorted = $supplierAvgPrices->sort()->values();
        $median = $sorted->count() > 0
            ? ($sorted->count() % 2 === 0
                ? ($sorted[$sorted->count() / 2 - 1] + $sorted[$sorted->count() / 2]) / 2
                : $sorted[(int) ($sorted->count() / 2)])
            : $weightedAvg;

        $ratio = $median > 0 ? $weightedAvg / $median : 1;

        $score = match (true) {
            $ratio <= 0.90 => 5,
            $ratio <= 0.97 => 4,
            $ratio <= 1.03 => 3,
            $ratio <= 1.10 => 2,
            default => 1,
        };

        $categoryMap = [5 => 'Sangat Kompetitif', 4 => 'Kompetitif', 3 => 'Sebanding', 2 => 'Mahal', 1 => 'Sangat Mahal'];

        return [
            'score' => $score,
            'raw_value' => round($weightedAvg, 4),
            'raw_value_label' => 'Harga rata-rata tertimbang: Rp '.number_format($weightedAvg, 2, ',', '.').' | Median: Rp '.number_format($median, 2, ',', '.'),
            'score_category' => $categoryMap[$score],
            'description' => 'C2: Harga rata-rata tertimbang Rp '.number_format($weightedAvg, 0, ',', '.').' vs median Rp '.number_format($median, 0, ',', '.').'. Rasio: '.round($ratio, 4).'. Skor sudah BENEFIT (5=paling kompetitif).',
        ];
    }

    // -------------------------------------------------------------------------
    // C3 - Masa Kerja Sama
    // -------------------------------------------------------------------------
    private function calculateC3(Supplier $supplier): array
    {
        $tahun = (int) ($supplier->masa_kerja_sama ?? 0);

        $score = match (true) {
            $tahun >= 10 => 5,
            $tahun >= 7 => 4,
            $tahun >= 4 => 3,
            $tahun >= 1 => 2,
            default => 1,
        };

        $categoryMap = [5 => 'Sangat Lama', 4 => 'Lama', 3 => 'Cukup Lama', 2 => 'Singkat', 1 => 'Sangat Singkat'];

        return [
            'score' => $score,
            'raw_value' => $tahun,
            'raw_value_label' => "{$tahun} tahun kerja sama",
            'score_category' => $categoryMap[$score],
            'description' => "C3 dihitung dari masa kerja sama: {$tahun} tahun. Skor: ≥10=5, ≥7=4, ≥4=3, ≥1=2, <1=1.",
        ];
    }

    // -------------------------------------------------------------------------
    // C4 - Ketepatan Kuantitas
    // -------------------------------------------------------------------------
    private function calculateC4(Collection $histories): array
    {
        $totalDipesan = $histories->sum('qty_pembelian');
        $totalDiterima = $histories->sum('qty_diterima');
        $rate = $totalDipesan > 0 ? ($totalDiterima / $totalDipesan) * 100 : 0;

        $score = match (true) {
            $rate >= 98 => 5,
            $rate >= 90 => 4,
            $rate >= 75 => 3,
            $rate >= 50 => 2,
            default => 1,
        };

        $categoryMap = [5 => 'Sangat Tepat', 4 => 'Tepat', 3 => 'Cukup Tepat', 2 => 'Kurang Tepat', 1 => 'Tidak Tepat'];

        return [
            'score' => $score,
            'raw_value' => round($rate, 2),
            'raw_value_label' => "Diterima: {$totalDiterima} / Dipesan: {$totalDipesan} = ".round($rate, 2).'%',
            'score_category' => $categoryMap[$score],
            'description' => "C4: {$totalDiterima}/{$totalDipesan} × 100 = ".round($rate, 2).'%. Skor: ≥98%=5, ≥90%=4, ≥75%=3, ≥50%=2, <50%=1.',
        ];
    }

    // -------------------------------------------------------------------------
    // C5 - Ketepatan Waktu Pengiriman
    // -------------------------------------------------------------------------
    private function calculateC5(Collection $histories): array
    {
        $validLeadTimes = $histories
            ->filter(fn ($h) => ! is_null($h->lead_time_hari) && $h->lead_time_hari >= 0)
            ->pluck('lead_time_hari');

        if ($validLeadTimes->isEmpty()) {
            return [
                'score' => 1,
                'raw_value' => null,
                'raw_value_label' => 'Tidak ada data lead time yang valid',
                'score_category' => 'Tidak Tersedia',
                'description' => 'C5 tidak dapat dihitung karena tidak ada data tanggal penerimaan yang terisi.',
            ];
        }

        $avgLeadTime = $validLeadTimes->avg();

        $score = match (true) {
            $avgLeadTime <= 3 => 5,
            $avgLeadTime <= 7 => 4,
            $avgLeadTime <= 14 => 3,
            $avgLeadTime <= 21 => 2,
            default => 1,
        };

        $categoryMap = [5 => 'Sangat Cepat', 4 => 'Cepat', 3 => 'Cukup Cepat', 2 => 'Lambat', 1 => 'Sangat Lambat'];

        return [
            'score' => $score,
            'raw_value' => round($avgLeadTime, 2),
            'raw_value_label' => 'Rata-rata lead time: '.round($avgLeadTime, 1).' hari (dari '.$validLeadTimes->count().' transaksi)',
            'score_category' => $categoryMap[$score],
            'description' => 'C5: rata-rata lead time = '.round($avgLeadTime, 1).' hari. Skor: ≤3=5, ≤7=4, ≤14=3, ≤21=2, >21=1.',
        ];
    }
}
