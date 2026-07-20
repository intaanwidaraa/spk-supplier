<?php

namespace App\Services;

use App\Models\Criteria;
use App\Models\EvaluationPeriod;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\PurchaseHistory;
use App\Models\Supplier;
use App\Models\SupplierPerformanceAssessment;
use App\Models\SupplierPerformanceScoreDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SupplierPerformanceCalculationService
{
    private function normalizeString($value): ?string
    {
        if (is_array($value)) {
            if (isset($value['value'])) {
                return $value['value'] ? (string) $value['value'] : null;
            }
            $value = collect($value)->flatten()->filter()->first();
        }
        return $value ? (string) $value : null;
    }

    private function normalizeInt($value): ?int
    {
        if (is_array($value)) {
            if (isset($value['value'])) {
                $value = $value['value'];
            } else {
                $value = collect($value)->flatten()->filter()->first();
            }
        }
        return $value ? (int) $value : null;
    }

    private function getOrCreateDefaultEvaluationPeriod(): EvaluationPeriod
    {
        $period = EvaluationPeriod::query()
            ->where('status', 'Aktif')
            ->latest()
            ->first();

        if ($period) {
            return $period;
        }

        $period = EvaluationPeriod::query()->latest()->first();

        if ($period) {
            return $period;
        }

        return EvaluationPeriod::create([
            'evaluation_code' => 'EV-AUTO-2025',
            'name' => 'Penilaian Otomatis Supplier 2025',
            'year' => date('Y'),
            'start_date' => date('Y') . '-01-01',
            'end_date' => date('Y') . '-12-31',
            'product_category' => 'Raw Material',
            'status' => 'Aktif',
            'description' => 'Periode otomatis untuk penilaian kinerja supplier.',
        ]);
    }

    public function calculateAndSyncAll(): void
    {
        $evaluationPeriod = $this->getOrCreateDefaultEvaluationPeriod();
        
        $suppliers = Supplier::all();

        if ($suppliers->isEmpty()) return;

        $criteriaList = Criteria::all()->keyBy('kode_kriteria');

        $allPurchaseHistories = PurchaseHistory::whereIn('supplier_id', $suppliers->pluck('id'))->get();
        
        $productPriceGroups = $allPurchaseHistories->groupBy(function ($item) {
            return $item->nama_produk . '|' . $item->satuan;
        });
        
        $medianPrices = [];
        foreach ($productPriceGroups as $key => $histories) {
            $prices = [];
            $supplierGroups = $histories->groupBy('supplier_id');
            foreach ($supplierGroups as $sId => $sHistories) {
                $sumQty = $sHistories->sum('qty_pembelian');
                $sumTotal = $sHistories->sum('total_pembelian');
                if ($sumQty > 0) {
                    $prices[] = $sumTotal / $sumQty;
                }
            }
            if (count($prices) > 0) {
                sort($prices);
                $count = count($prices);
                $mid = floor(($count - 1) / 2);
                $medianPrices[$key] = ($prices[$mid] + $prices[$mid + 1 - $count % 2]) / 2;
            }
        }

        foreach ($suppliers as $supplier) {
            $supplierHistories = $allPurchaseHistories->where('supplier_id', $supplier->id);
            
            $c1 = $this->calculateC1($supplierHistories, $criteriaList->get('C1'));
            $c2 = $this->calculateC2($supplierHistories, $medianPrices, $criteriaList->get('C2'));
            $c3 = $this->calculateC3($supplier, $criteriaList->get('C3'));
            $c4 = $this->calculateC4($supplierHistories, $criteriaList->get('C4'));
            $c5 = $this->calculateC5($supplierHistories, $criteriaList->get('C5'));

            $totalScore = 0;
            if ($c1) $totalScore += $c1['auto_score'];
            if ($c2) $totalScore += $c2['auto_score'];
            if ($c3) $totalScore += $c3['auto_score'];
            if ($c4) $totalScore += $c4['auto_score'];
            if ($c5) $totalScore += $c5['auto_score'];

            $existingAssessment = SupplierPerformanceAssessment::where([
                'evaluation_period_id' => $evaluationPeriod->id,
                'supplier_id' => $supplier->id,
            ])->first();

            if ($existingAssessment && $existingAssessment->status === 'Final') {
                continue;
            }

            $assessment = SupplierPerformanceAssessment::updateOrCreate(
                [
                    'evaluation_period_id' => $evaluationPeriod->id,
                    'supplier_id' => $supplier->id,
                    // Remove product filter bindings so it's a general assessment
                    'product_group_id' => null,
                    'product_id' => null,
                ],
                [
                    'product_category' => $supplier->kategori,
                    'assessment_date' => Carbon::today(),
                    'c1_score' => $c1 ? $c1['auto_score'] : null,
                    'c2_score' => $c2 ? $c2['auto_score'] : null,
                    'c3_score' => $c3 ? $c3['auto_score'] : null,
                    'c4_score' => $c4 ? $c4['auto_score'] : null,
                    'c5_score' => $c5 ? $c5['auto_score'] : null,
                    'total_score' => $totalScore,
                    'status' => 'Otomatis',
                    'is_auto_calculated' => true,
                    'calculated_at' => now(),
                    'notes' => 'Penilaian dihitung otomatis berdasarkan data historis pembelian dan data supplier.',
                ]
            );

            $this->saveScoreDetail($assessment, $criteriaList->get('C1'), $c1);
            $this->saveScoreDetail($assessment, $criteriaList->get('C2'), $c2);
            $this->saveScoreDetail($assessment, $criteriaList->get('C3'), $c3);
            $this->saveScoreDetail($assessment, $criteriaList->get('C4'), $c4);
            $this->saveScoreDetail($assessment, $criteriaList->get('C5'), $c5);
        }
    }
    public function calculateAndSyncForTable(
        ?int $evaluationPeriodId = null,
        ?string $kategoriSupplier = null,
        ?int $productGroupId = null,
        ?int $productId = null
    ) {
        $evaluationPeriodId = $this->normalizeInt($evaluationPeriodId);
        $kategoriSupplier = $this->normalizeString($kategoriSupplier);
        $productGroupId = $this->normalizeInt($productGroupId);
        $productId = $this->normalizeInt($productId);

        $evaluationPeriod = null;
        if ($evaluationPeriodId) {
            $evaluationPeriod = EvaluationPeriod::find($evaluationPeriodId);
        }

        if (!$evaluationPeriod) {
            $evaluationPeriod = EvaluationPeriod::query()
                ->where('status', 'Aktif')
                ->latest()
                ->first()
                ?? EvaluationPeriod::query()->latest()->first();
        }

        if (!$evaluationPeriod) {
            throw new \Exception('Periode evaluasi belum tersedia. Buat periode evaluasi terlebih dahulu.');
        }

        $productGroup = $productGroupId ? ProductGroup::find($productGroupId) : null;
        $product = $productId ? Product::find($productId) : null;

        $suppliersQuery = Supplier::query();

        if ($kategoriSupplier && $kategoriSupplier !== 'Semua') {
            $suppliersQuery->where('kategori', $kategoriSupplier);
        }

        $targetProductNames = [];
        $targetProductCodes = [];

        if ($productId && $product) {
            $targetProductNames[] = $product->nama_produk;
            $targetProductCodes[] = $product->kode_produk;
            
            $supplierIds = DB::table('supplier_products')->where('product_id', $productId)->pluck('supplier_id')->toArray();
            if (!empty($supplierIds)) {
                $suppliersQuery->whereIn('id', $supplierIds);
            }
        } elseif ($productGroupId && $productGroup) {
            $products = Product::where('product_group_id', $productGroupId)->get();
            $targetProductNames = $products->pluck('nama_produk')->filter()->toArray();
            $targetProductCodes = $products->pluck('kode_produk')->filter()->toArray();

            $supplierIds = DB::table('supplier_products')->whereIn('product_id', $products->pluck('id'))->pluck('supplier_id')->toArray();
            if (!empty($supplierIds)) {
                $suppliersQuery->whereIn('id', $supplierIds);
            }
        }

        $suppliers = $suppliersQuery->get();

        if ($suppliers->isEmpty()) {
            throw new \Exception('Tidak ada supplier yang sesuai dengan filter yang diberikan.');
        }

        $criteriaList = Criteria::all()->keyBy('kode_kriteria');

        $purchaseHistoriesQuery = PurchaseHistory::whereIn('supplier_id', $suppliers->pluck('id'))
            ->whereYear('tanggal_pembelian', $evaluationPeriod->year);
        
        if (!empty($targetProductNames) || !empty($targetProductCodes)) {
             $purchaseHistoriesQuery->where(function ($q) use ($targetProductNames, $targetProductCodes) {
                 if (count($targetProductNames) > 0) {
                     $q->whereIn('nama_produk', $targetProductNames);
                 }
                 if (count($targetProductCodes) > 0) {
                     $q->orWhereIn('kode_produk', $targetProductCodes);
                 }
             });
        }
            
        $allPurchaseHistories = $purchaseHistoriesQuery->get();

        $productPriceGroups = $allPurchaseHistories->groupBy(function ($item) {
            return $item->nama_produk . '|' . $item->satuan;
        });
        
        $medianPrices = [];
        foreach ($productPriceGroups as $key => $histories) {
            $prices = [];
            $supplierGroups = $histories->groupBy('supplier_id');
            foreach ($supplierGroups as $sId => $sHistories) {
                $sumQty = $sHistories->sum('qty_pembelian');
                $sumTotal = $sHistories->sum('total_pembelian');
                if ($sumQty > 0) {
                    $prices[] = $sumTotal / $sumQty;
                }
            }
            if (count($prices) > 0) {
                sort($prices);
                $count = count($prices);
                $mid = floor(($count - 1) / 2);
                $medianPrices[$key] = ($prices[$mid] + $prices[$mid + 1 - $count % 2]) / 2;
            }
        }

        foreach ($suppliers as $supplier) {
            $supplierHistories = $allPurchaseHistories->where('supplier_id', $supplier->id);
            
            $c1 = $this->calculateC1($supplierHistories, $criteriaList->get('C1'));
            $c2 = $this->calculateC2($supplierHistories, $medianPrices, $criteriaList->get('C2'));
            $c3 = $this->calculateC3($supplier, $criteriaList->get('C3'));
            $c4 = $this->calculateC4($supplierHistories, $criteriaList->get('C4'));
            $c5 = $this->calculateC5($supplierHistories, $criteriaList->get('C5'));

            $totalScore = 0;
            if ($c1) $totalScore += $c1['auto_score'];
            if ($c2) $totalScore += $c2['auto_score'];
            if ($c3) $totalScore += $c3['auto_score'];
            if ($c4) $totalScore += $c4['auto_score'];
            if ($c5) $totalScore += $c5['auto_score'];

            $existingAssessment = SupplierPerformanceAssessment::where([
                'evaluation_period_id' => $evaluationPeriod->id,
                'supplier_id' => $supplier->id,
                'product_group_id' => $productGroupId,
                'product_id' => $productId,
            ])->first();

            if ($existingAssessment && $existingAssessment->status === 'Final') {
                continue;
            }

            $assessment = SupplierPerformanceAssessment::updateOrCreate(
                [
                    'evaluation_period_id' => $evaluationPeriod->id,
                    'supplier_id' => $supplier->id,
                    'product_group_id' => $productGroupId,
                    'product_id' => $productId,
                ],
                [
                    'product_category' => $supplier->kategori ?? $kategoriSupplier,
                    'assessment_date' => Carbon::today(),
                    'c1_score' => $c1 ? $c1['auto_score'] : null,
                    'c2_score' => $c2 ? $c2['auto_score'] : null,
                    'c3_score' => $c3 ? $c3['auto_score'] : null,
                    'c4_score' => $c4 ? $c4['auto_score'] : null,
                    'c5_score' => $c5 ? $c5['auto_score'] : null,
                    'total_score' => $totalScore,
                    'status' => 'Otomatis',
                    'is_auto_calculated' => true,
                    'calculated_at' => now(),
                    'notes' => 'Penilaian dihitung otomatis berdasarkan data historis pembelian dan data supplier.',
                ]
            );

            $this->saveScoreDetail($assessment, $criteriaList->get('C1'), $c1);
            $this->saveScoreDetail($assessment, $criteriaList->get('C2'), $c2);
            $this->saveScoreDetail($assessment, $criteriaList->get('C3'), $c3);
            $this->saveScoreDetail($assessment, $criteriaList->get('C4'), $c4);
            $this->saveScoreDetail($assessment, $criteriaList->get('C5'), $c5);
        }
    }

    public function calculate(int $evaluationPeriodId, int $productGroupId, ?int $productId = null)
    {
        $evaluationPeriod = EvaluationPeriod::findOrFail($evaluationPeriodId);
        $productGroup = ProductGroup::findOrFail($productGroupId);
        
        $products = [];
        if ($productId) {
            $product = Product::findOrFail($productId);
            $products[] = $product;
        } else {
            $products = $productGroup->products;
        }

        if (count($products) === 0) {
            return;
        }

        $productIds = collect($products)->pluck('id')->toArray();
        $productCodes = collect($products)->pluck('kode_produk')->filter()->toArray();
        $productNames = collect($products)->pluck('nama_produk')->filter()->toArray();

        // 1. Get Target Suppliers
        // First, check from pivot supplier_products
        $supplierIds = DB::table('supplier_products')
            ->whereIn('product_id', $productIds)
            ->pluck('supplier_id')
            ->unique()
            ->toArray();

        // Fallback: Check purchase histories if pivot is incomplete
        if (empty($supplierIds) && count($productNames) > 0) {
            $historySuppliers = PurchaseHistory::query()
                ->where(function ($q) use ($productNames, $productCodes) {
                    if (count($productNames) > 0) {
                        $q->whereIn('nama_produk', $productNames);
                    }
                    if (count($productCodes) > 0) {
                        $q->orWhereIn('kode_produk', $productCodes);
                    }
                })
                ->pluck('supplier_name')
                ->unique()
                ->toArray();

            if (!empty($historySuppliers)) {
                $supplierIds = Supplier::whereIn('nama_supplier', $historySuppliers)
                    ->pluck('id')
                    ->toArray();
            }
        }

        if (empty($supplierIds)) {
            return; // No suppliers found to evaluate
        }

        $suppliers = Supplier::whereIn('id', $supplierIds)->get();
        $criteriaList = Criteria::all()->keyBy('kode_kriteria');

        // Prepare Comparison Data (for C2 - Price)
        // Find the median price for each product+satuan in the evaluation period year
        $purchaseHistoriesQuery = PurchaseHistory::whereIn('supplier_id', $supplierIds)
            ->where(function ($q) use ($productNames, $productCodes) {
                if (count($productNames) > 0) {
                    $q->whereIn('nama_produk', $productNames);
                }
                if (count($productCodes) > 0) {
                    $q->orWhereIn('kode_produk', $productCodes);
                }
            })
            ->whereYear('tanggal_pembelian', $evaluationPeriod->year);
            
        $allPurchaseHistories = $purchaseHistoriesQuery->get();

        // Median calculation logic for C2 pembanding
        $productPriceGroups = $allPurchaseHistories->groupBy(function ($item) {
            return $item->nama_produk . '|' . $item->satuan;
        });
        
        $medianPrices = [];
        foreach ($productPriceGroups as $key => $histories) {
            $prices = [];
            $supplierGroups = $histories->groupBy('supplier_id');
            foreach ($supplierGroups as $sId => $sHistories) {
                $sumQty = $sHistories->sum('qty_pembelian');
                $sumTotal = $sHistories->sum('total_pembelian'); // total_pembelian or qty*harga_satuan
                if ($sumQty > 0) {
                    $prices[] = $sumTotal / $sumQty;
                }
            }
            if (count($prices) > 0) {
                sort($prices);
                $count = count($prices);
                $mid = floor(($count - 1) / 2);
                $medianPrices[$key] = ($prices[$mid] + $prices[$mid + 1 - $count % 2]) / 2;
            }
        }

        foreach ($suppliers as $supplier) {
            $supplierHistories = $allPurchaseHistories->where('supplier_id', $supplier->id);
            
            // Calculate Scores
            $c1 = $this->calculateC1($supplierHistories, $criteriaList->get('C1'));
            $c2 = $this->calculateC2($supplierHistories, $medianPrices, $criteriaList->get('C2'));
            $c3 = $this->calculateC3($supplier, $criteriaList->get('C3'));
            $c4 = $this->calculateC4($supplierHistories, $criteriaList->get('C4'));
            $c5 = $this->calculateC5($supplierHistories, $criteriaList->get('C5'));

            // Calculate total score
            $totalScore = 0;
            if ($c1) $totalScore += $c1['auto_score'];
            if ($c2) $totalScore += $c2['auto_score'];
            if ($c3) $totalScore += $c3['auto_score'];
            if ($c4) $totalScore += $c4['auto_score'];
            if ($c5) $totalScore += $c5['auto_score'];

            // Update or Create Assessment
            $assessment = SupplierPerformanceAssessment::updateOrCreate(
                [
                    'evaluation_period_id' => $evaluationPeriod->id,
                    'supplier_id' => $supplier->id,
                    'product_group_id' => $productGroup->id,
                    'product_id' => $productId, // null if grouped
                ],
                [
                    'product_category' => $productGroup->kategori_produk,
                    'assessment_date' => Carbon::today(),
                    'c1_score' => $c1 ? $c1['auto_score'] : null,
                    'c2_score' => $c2 ? $c2['auto_score'] : null,
                    'c3_score' => $c3 ? $c3['auto_score'] : null,
                    'c4_score' => $c4 ? $c4['auto_score'] : null,
                    'c5_score' => $c5 ? $c5['auto_score'] : null,
                    'total_score' => $totalScore,
                    'status' => 'Dihitung Otomatis',
                    'is_auto_calculated' => true,
                    'calculated_at' => now(),
                    'notes' => 'Penilaian dihitung otomatis berdasarkan data historis pembelian dan data supplier.',
                ]
            );

            // Save Details
            $this->saveScoreDetail($assessment, $criteriaList->get('C1'), $c1);
            $this->saveScoreDetail($assessment, $criteriaList->get('C2'), $c2);
            $this->saveScoreDetail($assessment, $criteriaList->get('C3'), $c3);
            $this->saveScoreDetail($assessment, $criteriaList->get('C4'), $c4);
            $this->saveScoreDetail($assessment, $criteriaList->get('C5'), $c5);
        }
    }

    private function calculateC1($histories, $criterion)
    {
        if (!$criterion || $histories->isEmpty()) return null;
        
        $productGroups = $histories->groupBy(function ($item) {
            return $item->nama_produk . '|' . $item->satuan;
        });

        $totalProductCombinations = $productGroups->count();
        $repeatProductCount = 0;

        foreach ($productGroups as $key => $items) {
            $uniquePos = $items->pluck('nomor_po')->unique()->count();
            if ($uniquePos >= 2) {
                $repeatProductCount++;
            }
        }

        if ($totalProductCombinations == 0) return null;

        $repeatRate = $repeatProductCount / $totalProductCombinations;
        
        $score = 1;
        if ($repeatRate >= 0.80) $score = 5;
        elseif ($repeatRate >= 0.60) $score = 4;
        elseif ($repeatRate >= 0.40) $score = 3;
        elseif ($repeatRate >= 0.20) $score = 2;

        return [
            'raw_value' => $repeatRate,
            'raw_value_label' => "{$repeatProductCount} dari {$totalProductCombinations} jenis produk pernah dipesan ulang",
            'auto_score' => $score,
            'calculation_description' => "C1 menggunakan proxy konsistensi pembelian ulang karena data QC/reject belum tersedia.",
        ];
    }

    private function calculateC2($histories, $medianPrices, $criterion)
    {
        if (!$criterion || $histories->isEmpty()) return null;

        $productGroups = $histories->groupBy(function ($item) {
            return $item->nama_produk . '|' . $item->satuan;
        });

        $priceIndices = [];
        $totalWeight = 0;

        foreach ($productGroups as $key => $items) {
            $sumQty = $items->sum('qty_pembelian');
            $sumTotal = $items->sum('total_pembelian');
            
            if ($sumQty > 0 && isset($medianPrices[$key]) && $medianPrices[$key] > 0) {
                $weightedPrice = $sumTotal / $sumQty;
                $index = $weightedPrice / $medianPrices[$key];
                
                // Weight by qty or just simple average of indices?
                // The prompt says: "Indeks harga: price_index = harga_rata_rata_tertimbang_supplier / median_harga_pembanding"
                // If supplier has multiple products, we can average the indices
                $priceIndices[] = $index;
            }
        }

        if (count($priceIndices) === 0) {
            return [
                'raw_value' => null,
                'raw_value_label' => "Data tidak cukup",
                'auto_score' => null,
                'calculation_description' => "Data harga pembanding tidak cukup.",
            ];
        }

        $overallPriceIndex = array_sum($priceIndices) / count($priceIndices);

        $score = 1;
        if ($overallPriceIndex <= 0.90) $score = 5;
        elseif ($overallPriceIndex <= 1.00) $score = 4;
        elseif ($overallPriceIndex <= 1.10) $score = 3;
        elseif ($overallPriceIndex <= 1.20) $score = 2;

        $formattedIndex = number_format($overallPriceIndex, 2, ',', '.');
        return [
            'raw_value' => $overallPriceIndex,
            'raw_value_label' => "Indeks gabungan {$formattedIndex}",
            'auto_score' => $score,
            'calculation_description' => "Indeks harga dihitung dari rasio harga tertimbang supplier terhadap median harga pembanding.",
        ];
    }

    private function calculateC3($supplier, $criterion)
    {
        if (!$criterion) return null;

        $duration = (float) $supplier->masa_kerja_sama;
        
        $score = 1;
        if ($duration >= 9) $score = 5;
        elseif ($duration >= 7) $score = 4;
        elseif ($duration >= 5) $score = 3;
        elseif ($duration >= 3) $score = 2;

        return [
            'raw_value' => $duration,
            'raw_value_label' => "{$duration} tahun masa kerja sama",
            'auto_score' => $score,
            'calculation_description' => "Dinilai berdasarkan masa kerja sama yang tercatat di master data supplier.",
        ];
    }

    private function calculateC4($histories, $criterion)
    {
        if (!$criterion || $histories->isEmpty()) return null;

        $sumPembelian = $histories->sum('qty_pembelian');
        $sumDiterima = $histories->sum('qty_diterima');

        if ($sumPembelian <= 0) {
            return [
                'raw_value' => null,
                'raw_value_label' => "Data kuantitas tidak cukup",
                'auto_score' => null,
                'calculation_description' => "Data kuantitas pembelian tidak cukup.",
            ];
        }

        $fulfillmentRate = ($sumDiterima / $sumPembelian) * 100;
        
        $score = 1;
        if ($fulfillmentRate >= 99) $score = 5;
        elseif ($fulfillmentRate >= 95) $score = 4;
        elseif ($fulfillmentRate >= 90) $score = 3;
        elseif ($fulfillmentRate >= 80) $score = 2;

        $formattedRate = number_format($fulfillmentRate, 2, ',', '.');
        $formattedDiterima = number_format($sumDiterima, 0, ',', '.');
        $formattedPembelian = number_format($sumPembelian, 0, ',', '.');

        return [
            'raw_value' => $fulfillmentRate,
            'raw_value_label' => "Qty diterima {$formattedDiterima} dari qty pembelian {$formattedPembelian}, fulfillment rate {$formattedRate}%",
            'auto_score' => $score,
            'calculation_description' => "Persentase qty diterima dibandingkan dengan qty pembelian.",
        ];
    }

    private function calculateC5($histories, $criterion)
    {
        if (!$criterion || $histories->isEmpty()) return null;

        $leadTimes = [];

        foreach ($histories as $history) {
            if ($history->lead_time_hari !== null) {
                if ($history->lead_time_hari >= 0) {
                    $leadTimes[] = $history->lead_time_hari;
                }
            } elseif ($history->tanggal_penerimaan && $history->tanggal_pembelian) {
                $tglTerima = Carbon::parse($history->tanggal_penerimaan);
                $tglBeli = Carbon::parse($history->tanggal_pembelian);
                $diff = $tglBeli->diffInDays($tglTerima, false);
                if ($diff >= 0) {
                    $leadTimes[] = $diff;
                }
            }
        }

        if (count($leadTimes) === 0) {
            return [
                'raw_value' => null,
                'raw_value_label' => "Data tidak cukup",
                'auto_score' => null,
                'calculation_description' => "Data lead time tidak cukup.",
            ];
        }

        $avgLeadTime = array_sum($leadTimes) / count($leadTimes);

        $score = 1;
        if ($avgLeadTime <= 8.8) $score = 5;
        elseif ($avgLeadTime <= 10.6) $score = 4;
        elseif ($avgLeadTime <= 12.0) $score = 3;
        elseif ($avgLeadTime <= 16.1) $score = 2;

        $formattedLeadTime = number_format($avgLeadTime, 2, ',', '.');

        return [
            'raw_value' => $avgLeadTime,
            'raw_value_label' => "Rata-rata lead time {$formattedLeadTime} hari",
            'auto_score' => $score,
            'calculation_description' => "Rata-rata lead time pengiriman berdasarkan tanggal PO hingga penerimaan.",
        ];
    }

    private function saveScoreDetail($assessment, $criterion, $result)
    {
        if (!$criterion) return;

        // Check for existing manual override
        $existingDetail = SupplierPerformanceScoreDetail::where('supplier_performance_assessment_id', $assessment->id)
            ->where('criterion_id', $criterion->id)
            ->first();

        if ($existingDetail && $existingDetail->is_manual_override) {
            // Keep manual override but update auto score context
            $existingDetail->auto_score = $result ? $result['auto_score'] : null;
            $existingDetail->raw_value = $result ? $result['raw_value'] : null;
            $existingDetail->raw_value_label = $result ? $result['raw_value_label'] : null;
            $existingDetail->calculation_description = $result ? $result['calculation_description'] : null;
            $existingDetail->save();
            return;
        }

        // Determine category from guidelines based on score
        $category = null;
        if ($result && $result['auto_score']) {
            $guideline = $criterion->scoreGuidelines->where('score', $result['auto_score'])->first();
            $category = $guideline ? ($guideline->subcriteria ?? $guideline->quantitative_parameter) : null;
        }

        SupplierPerformanceScoreDetail::updateOrCreate(
            [
                'supplier_performance_assessment_id' => $assessment->id,
                'criterion_id' => $criterion->id,
            ],
            [
                'raw_value' => $result ? (string)$result['raw_value'] : null, // keep as string due to schema? actually it's a string column
                'raw_value_label' => $result ? $result['raw_value_label'] : null,
                'auto_score' => $result ? $result['auto_score'] : null,
                'final_score' => $result ? $result['auto_score'] : null,
                'score_category' => $category,
                'calculation_description' => $result ? $result['calculation_description'] : null,
                'is_manual_override' => false,
                'override_reason' => null,
                'overridden_by' => null,
                'overridden_at' => null,
            ]
        );
    }
}
