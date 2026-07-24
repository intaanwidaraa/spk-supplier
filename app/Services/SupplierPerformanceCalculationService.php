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
        $this->performCalculation($evaluationPeriod, null, null, null);
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
            $evaluationPeriod = $this->getOrCreateDefaultEvaluationPeriod();
        }

        $this->performCalculation($evaluationPeriod, $kategoriSupplier, $productGroupId, $productId);
    }

    public function calculate(int $evaluationPeriodId, int $productGroupId, ?int $productId = null)
    {
        $evaluationPeriod = EvaluationPeriod::findOrFail($evaluationPeriodId);
        $this->performCalculation($evaluationPeriod, null, $productGroupId, $productId);
    }

    private function performCalculation(EvaluationPeriod $evaluationPeriod, ?string $kategoriSupplier, ?int $productGroupId, ?int $productId)
    {
        $suppliersQuery = Supplier::query();

        if ($kategoriSupplier && $kategoriSupplier !== 'Semua') {
            $suppliersQuery->where('kategori', $kategoriSupplier);
        }

        $productGroup = $productGroupId ? ProductGroup::find($productGroupId) : null;
        $product = $productId ? Product::find($productId) : null;

        $targetProductNames = [];
        $targetProductCodes = [];

        if ($productId && $product) {
            $targetProductNames[] = $product->nama_produk;
            $targetProductCodes[] = $product->kode_produk;
            
            $supplierIds = DB::table('product_supplier')->where('product_id', $productId)->pluck('supplier_id')->toArray();
            if (!empty($supplierIds)) {
                $suppliersQuery->whereIn('id', $supplierIds);
            }
        } elseif ($productGroupId && $productGroup) {
            $products = Product::where('product_group_id', $productGroupId)->get();
            $targetProductNames = $products->pluck('nama_produk')->filter()->toArray();
            $targetProductCodes = $products->pluck('kode_produk')->filter()->toArray();

            $supplierIds = DB::table('product_supplier')->whereIn('product_id', $products->pluck('id'))->pluck('supplier_id')->toArray();
            if (!empty($supplierIds)) {
                $suppliersQuery->whereIn('id', $supplierIds);
            }
        }

        $suppliers = $suppliersQuery->get();

        if ($suppliers->isEmpty()) {
            return;
        }

        $criteriaList = Criteria::all()->keyBy('kode_kriteria');
        $validSupplierIds = $suppliers->pluck('id')->toArray();

        // 1. Get all histories for these suppliers in the given year
        $purchaseHistoriesQuery = PurchaseHistory::whereIn('supplier_id', $validSupplierIds)
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

        // Prepare Median Prices for C2
        // Filter out zero prices and zero qty
        $validPricesHistories = $allPurchaseHistories->filter(function($h) {
            return $h->qty_pembelian > 0 && $h->harga_satuan > 0;
        });

        $productPriceGroups = $validPricesHistories->groupBy(function ($item) {
            $identity = $item->kode_produk ?: $item->nama_produk;
            return trim(strtoupper($identity)) . '|' . trim(strtoupper($item->satuan));
        });
        
        $medianPrices = [];
        foreach ($productPriceGroups as $key => $histories) {
            $prices = [];
            $supplierGroups = $histories->groupBy('supplier_id');
            foreach ($supplierGroups as $sId => $sHistories) {
                $sumQty = $sHistories->sum('qty_pembelian');
                $sumTotal = $sHistories->sum(function($h) {
                    return $h->qty_pembelian * $h->harga_satuan;
                });
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

        DB::beginTransaction();
        try {
            foreach ($suppliers as $supplier) {
                $supplierHistories = $allPurchaseHistories->where('supplier_id', $supplier->id);
                
                $c1 = $this->calculateC1($supplierHistories, $criteriaList->get('C1'));
                $c2 = $this->calculateC2($supplierHistories, $medianPrices, $criteriaList->get('C2'));
                $c3 = $this->calculateC3($supplier, $criteriaList->get('C3'));
                $c4 = $this->calculateC4($supplierHistories, $criteriaList->get('C4'));
                $c5 = $this->calculateC5($supplierHistories, $criteriaList->get('C5'));

                // Ensure all 5 criteria are present
                if (!$c1 || !$c2 || !$c3 || !$c4 || !$c5) {
                    \Log::warning("Perhitungan gagal untuk supplier {$supplier->nama_supplier}: Data tidak lengkap untuk C1-C5.");
                    continue;
                }

                $totalScore = ($c1['auto_score'] + $c2['auto_score'] + $c3['auto_score'] + $c4['auto_score'] + $c5['auto_score']) / 5;

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
                        'c1_score' => $c1['auto_score'],
                        'c2_score' => $c2['auto_score'],
                        'c3_score' => $c3['auto_score'],
                        'c4_score' => $c4['auto_score'],
                        'c5_score' => $c5['auto_score'],
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
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calculateC1($histories, $criterion)
    {
        if (!$criterion || $histories->isEmpty()) return null;
        
        $validHistories = $histories->filter(function($h) {
            return $h->qty_pembelian > 0;
        });

        if ($validHistories->isEmpty()) return null;

        $productGroups = $validHistories->groupBy(function ($item) {
            $identity = $item->kode_produk ?: $item->nama_produk;
            return trim(strtoupper($identity)) . '|' . trim(strtoupper($item->satuan));
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

        $repeatRate = ($repeatProductCount / $totalProductCombinations) * 100;
        
        $score = 1;
        if ($repeatRate > 80) $score = 5;
        elseif ($repeatRate > 60 && $repeatRate <= 80) $score = 4;
        elseif ($repeatRate > 40 && $repeatRate <= 60) $score = 3;
        elseif ($repeatRate > 20 && $repeatRate <= 40) $score = 2;
        elseif ($repeatRate <= 20) $score = 1;

        $formattedRate = number_format($repeatRate, 2, ',', '.');
        return [
            'raw_value' => $repeatRate,
            'raw_value_label' => "{$repeatProductCount} dari {$totalProductCombinations} jenis produk pernah dipesan ulang (RPR {$formattedRate}%)",
            'auto_score' => $score,
            'calculation_description' => "C1 menggunakan proxy konsistensi pembelian ulang: kombinasi produk dengan >=2 PO dibagi total kombinasi produk.",
        ];
    }

    private function calculateC2($histories, $medianPrices, $criterion)
    {
        if (!$criterion || $histories->isEmpty()) return null;

        $validHistories = $histories->filter(function($h) {
            return $h->qty_pembelian > 0 && $h->harga_satuan > 0;
        });

        if ($validHistories->isEmpty()) return null;

        $productGroups = $validHistories->groupBy(function ($item) {
            $identity = $item->kode_produk ?: $item->nama_produk;
            return trim(strtoupper($identity)) . '|' . trim(strtoupper($item->satuan));
        });

        $priceIndices = [];
        $singleSourceCount = 0;

        foreach ($productGroups as $key => $items) {
            $sumQty = $items->sum('qty_pembelian');
            $sumTotal = $items->sum(function($h) {
                return $h->qty_pembelian * $h->harga_satuan;
            });
            
            if ($sumQty > 0) {
                $weightedPrice = $sumTotal / $sumQty;
                
                if (isset($medianPrices[$key]) && $medianPrices[$key] > 0) {
                    $index = $weightedPrice / $medianPrices[$key];
                    $priceIndices[] = $index;
                } else {
                    // Single source or no median available
                    $singleSourceCount++;
                    $priceIndices[] = 1.0; // Assume market price if no comparison available based on common logic, or 1.0
                }
            }
        }

        if (count($priceIndices) === 0) {
            return null;
        }

        $overallPriceIndex = array_sum($priceIndices) / count($priceIndices);

        $score = 1;
        if ($overallPriceIndex <= 0.90) $score = 5;
        elseif ($overallPriceIndex > 0.90 && $overallPriceIndex <= 1.00) $score = 4;
        elseif ($overallPriceIndex > 1.00 && $overallPriceIndex <= 1.10) $score = 3;
        elseif ($overallPriceIndex > 1.10 && $overallPriceIndex <= 1.20) $score = 2;
        elseif ($overallPriceIndex > 1.20) $score = 1;

        $formattedIndex = number_format($overallPriceIndex, 2, ',', '.');
        $desc = "Indeks harga dihitung rata-rata rasio harga tertimbang supplier terhadap median. Dibandingkan: " . count($priceIndices) . " produk. Single source: {$singleSourceCount}.";

        return [
            'raw_value' => $overallPriceIndex,
            'raw_value_label' => "Indeks gabungan {$formattedIndex}",
            'auto_score' => $score,
            'calculation_description' => $desc,
        ];
    }

    private function calculateC3($supplier, $criterion)
    {
        if (!$criterion) return null;

        $duration = (float) $supplier->masa_kerja_sama;
        
        $score = 1;
        if ($duration >= 9) $score = 5;
        elseif ($duration >= 7 && $duration < 9) $score = 4;
        elseif ($duration >= 5 && $duration < 7) $score = 3;
        elseif ($duration >= 3 && $duration < 5) $score = 2;
        elseif ($duration < 3) $score = 1;

        return [
            'raw_value' => $duration,
            'raw_value_label' => "{$duration} tahun masa kerja sama",
            'auto_score' => $score,
            'calculation_description' => "Dinilai berdasarkan durasi di master supplier (batas eksklusif antar interval).",
        ];
    }

    private function calculateC4($histories, $criterion)
    {
        if (!$criterion || $histories->isEmpty()) return null;

        $sumPembelian = $histories->sum('qty_pembelian');
        $sumDiterima = $histories->sum('qty_diterima');

        if ($sumPembelian <= 0) return null;

        $fulfillmentRate = ($sumDiterima / $sumPembelian) * 100;
        
        $score = 1;
        if ($fulfillmentRate >= 99) $score = 5;
        elseif ($fulfillmentRate >= 95 && $fulfillmentRate < 99) $score = 4;
        elseif ($fulfillmentRate >= 90 && $fulfillmentRate < 95) $score = 3;
        elseif ($fulfillmentRate >= 80 && $fulfillmentRate < 90) $score = 2;
        elseif ($fulfillmentRate < 80) $score = 1;

        $formattedRate = number_format($fulfillmentRate, 2, ',', '.');
        $formattedDiterima = number_format($sumDiterima, 0, ',', '.');
        $formattedPembelian = number_format($sumPembelian, 0, ',', '.');

        return [
            'raw_value' => $fulfillmentRate,
            'raw_value_label' => "Qty diterima {$formattedDiterima} dari {$formattedPembelian} (FR {$formattedRate}%)",
            'auto_score' => $score,
            'calculation_description' => "FR dihitung dari SUM(diterima)/SUM(pembelian) x 100% pada periode berjalan.",
        ];
    }

    private function calculateC5($histories, $criterion)
    {
        if (!$criterion || $histories->isEmpty()) return null;

        $leadTimes = [];

        foreach ($histories as $history) {
            $lt = $history->lead_time_hari;
            
            if ($lt === null) {
                if ($history->tanggal_penerimaan && $history->tanggal_pembelian) {
                    $tglTerima = Carbon::parse($history->tanggal_penerimaan);
                    $tglBeli = Carbon::parse($history->tanggal_pembelian);
                    $lt = $tglBeli->diffInDays($tglTerima, false);
                }
            }
            
            if ($lt !== null && $lt >= 0) {
                $leadTimes[] = $lt;
            }
        }

        if (count($leadTimes) === 0) return null;

        $avgLeadTime = array_sum($leadTimes) / count($leadTimes);

        $score = 1;
        if ($avgLeadTime <= 8.8) $score = 5;
        elseif ($avgLeadTime > 8.8 && $avgLeadTime <= 10.6) $score = 4;
        elseif ($avgLeadTime > 10.6 && $avgLeadTime <= 12.0) $score = 3;
        elseif ($avgLeadTime > 12.0 && $avgLeadTime <= 16.1) $score = 2;
        elseif ($avgLeadTime > 16.1) $score = 1;

        $formattedLeadTime = number_format($avgLeadTime, 2, ',', '.');

        return [
            'raw_value' => $avgLeadTime,
            'raw_value_label' => "Rata-rata lead time {$formattedLeadTime} hari",
            'auto_score' => $score,
            'calculation_description' => "Menggunakan rata-rata lead time dari " . count($leadTimes) . " transaksi valid (>= 0 hari).",
        ];
    }

    private function saveScoreDetail($assessment, $criterion, $result)
    {
        if (!$criterion) return;

        $existingDetail = SupplierPerformanceScoreDetail::where('supplier_performance_assessment_id', $assessment->id)
            ->where('criterion_id', $criterion->id)
            ->first();

        if ($existingDetail && $existingDetail->is_manual_override) {
            $existingDetail->auto_score = $result ? $result['auto_score'] : null;
            $existingDetail->raw_value = $result ? $result['raw_value'] : null;
            $existingDetail->raw_value_label = $result ? $result['raw_value_label'] : null;
            $existingDetail->calculation_description = $result ? $result['calculation_description'] : null;
            $existingDetail->save();
            return;
        }

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
                'raw_value' => $result ? (string)$result['raw_value'] : null,
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
