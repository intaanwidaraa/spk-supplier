<?php

namespace App\Services;

use App\Models\Criteria;
use App\Models\PurchaseHistory;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierScoreCalculator
{
    /**
     * @var Collection
     */
    protected $criteria;

    public function __construct()
    {
        // Preload criteria with their guidelines
        $this->criteria = Criteria::with('scoreGuidelines')->get()->keyBy('kode_kriteria');
    }

    /**
     * Menghitung nilai C1-C5 untuk kandidat supplier berdasarkan filter periode dan produk
     */
    public function calculateForSupplier(
        Supplier $supplier,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $productGroupId = null,
        ?int $productId = null
    ): array {
        // Ambil data transaksi historis sesuai filter
        $query = PurchaseHistory::where('supplier_id', $supplier->id)
            ->whereBetween('tanggal_pembelian', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')]);

        if ($productId) {
            // Filter by specific product using the product's nama_produk or kode_produk
            $product = \App\Models\Product::find($productId);
            if ($product) {
                $query->where(function($q) use ($product) {
                    $q->where('kode_produk', $product->kode_produk)
                      ->orWhere('nama_produk', 'like', '%' . $product->nama_produk . '%');
                });
            }
        } elseif ($productGroupId) {
            $group = \App\Models\ProductGroup::find($productGroupId);
            if ($group) {
                $productNames = $group->products()->pluck('nama_produk');
                if ($productNames->isEmpty()) {
                    $productNames = collect([$group->nama_kelompok_produk]);
                }
                $query->where(function($q) use ($productNames) {
                    foreach ($productNames as $name) {
                        $q->orWhere('nama_produk', 'like', '%' . $name . '%');
                    }
                });
            }
        }

        $histories = $query->get();

        $result = [
            'transaction_count' => $histories->count(),
            'scores' => [
                'C1' => ['score' => null, 'data' => []],
                'C2' => ['score' => null, 'data' => []],
                'C3' => ['score' => null, 'data' => []],
                'C4' => ['score' => null, 'data' => []],
                'C5' => ['score' => null, 'data' => []],
            ]
        ];

        if ($histories->isEmpty() && !$this->canCalculateWithoutHistory()) {
            // Jika butuh C3 saja, C3 bisa dihitung tanpa history
            $result['scores']['C3'] = $this->calculateC3($supplier);
            return $result;
        }

        // --- C1: Kualitas Produk (Repeat Product Rate) ---
        $result['scores']['C1'] = $this->calculateC1($histories);

        // --- C2: Harga ---
        // Butuh data semua supplier untuk median, jadi C2 mungkin perlu dilempar history dari semua supplier
        // Tapi untuk sementara hitung harga rata-rata, nanti rasio bisa dihitung terpisah atau C2 disederhanakan
        $allSupplierHistoriesQuery = PurchaseHistory::whereBetween('tanggal_pembelian', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')]);
        // Terapkan filter produk yang sama
        if ($productId) {
            $product = \App\Models\Product::find($productId);
            if ($product) {
                $allSupplierHistoriesQuery->where(function($q) use ($product) {
                    $q->where('kode_produk', $product->kode_produk)
                      ->orWhere('nama_produk', 'like', '%' . $product->nama_produk . '%');
                });
            }
        } elseif ($productGroupId) {
            $group = \App\Models\ProductGroup::find($productGroupId);
            if ($group) {
                $productNames = $group->products()->pluck('nama_produk');
                if ($productNames->isEmpty()) {
                    $productNames = collect([$group->nama_kelompok_produk]);
                }
                $allSupplierHistoriesQuery->where(function($q) use ($productNames) {
                    foreach ($productNames as $name) {
                        $q->orWhere('nama_produk', 'like', '%' . $name . '%');
                    }
                });
            }
        }
        $allHistories = $allSupplierHistoriesQuery->get();
        $result['scores']['C2'] = $this->calculateC2($histories, $allHistories);

        // --- C3: Masa Kerja Sama ---
        $result['scores']['C3'] = $this->calculateC3($supplier);

        // --- C4: Kapasitas Pemenuhan (Fulfillment Rate) ---
        $result['scores']['C4'] = $this->calculateC4($histories);

        // --- C5: Pengiriman (Lead Time) ---
        $result['scores']['C5'] = $this->calculateC5($histories);

        return $result;
    }

    protected function canCalculateWithoutHistory(): bool
    {
        return false;
    }

    /**
     * Hitung C1 - Repeat Product Rate
     */
    protected function calculateC1(Collection $histories): array
    {
        if ($histories->isEmpty()) {
            return ['score' => 1, 'data' => ['rate' => 0, 'label' => 'Tidak ada transaksi']];
        }

        $totalTransactions = $histories->count();
        $uniqueProducts = $histories->pluck('nama_produk')->unique()->count();
        
        // Repeat rate: (Total Transaksi - Unique Produk) / Total Transaksi
        // Jika 1 transaksi, 1 produk unik -> rate = 0 (belum repeat)
        $repeatRate = $totalTransactions > 1 ? (($totalTransactions - $uniqueProducts) / $totalTransactions) * 100 : 0;

        $score = $this->mapScore('C1', $repeatRate);

        return [
            'score' => $score,
            'data' => [
                'total_transactions' => $totalTransactions,
                'unique_products' => $uniqueProducts,
                'repeat_rate' => round($repeatRate, 2),
                'label' => "Repeat Rate: " . round($repeatRate, 2) . "%"
            ]
        ];
    }

    /**
     * Hitung C2 - Harga
     */
    protected function calculateC2(Collection $supplierHistories, Collection $allHistories): array
    {
        if ($supplierHistories->isEmpty()) {
            return ['score' => 1, 'data' => ['ratio' => null, 'label' => 'Tidak ada transaksi']];
        }

        // Hitung rata-rata harga dari supplier ini
        $avgSupplierPrice = $supplierHistories->avg('harga_satuan');

        // Hitung median harga dari semua supplier
        $allPrices = $allHistories->pluck('harga_satuan')->sort()->values();
        $medianPrice = 0;
        $count = $allPrices->count();
        if ($count > 0) {
            $middle = floor(($count - 1) / 2);
            if ($count % 2) {
                $medianPrice = $allPrices[$middle];
            } else {
                $medianPrice = ($allPrices[$middle] + $allPrices[$middle + 1]) / 2;
            }
        }

        if ($medianPrice == 0) {
            // Fallback jika median 0
            $ratio = 1;
        } else {
            $ratio = $avgSupplierPrice / $medianPrice;
        }

        $score = $this->mapScore('C2', $ratio);

        return [
            'score' => $score,
            'data' => [
                'avg_supplier_price' => round($avgSupplierPrice, 2),
                'median_price' => round($medianPrice, 2),
                'ratio' => round($ratio, 4),
                'label' => "Rasio Harga: " . round($ratio, 2)
            ]
        ];
    }

    /**
     * Hitung C3 - Masa Kerja Sama
     */
    protected function calculateC3(Supplier $supplier): array
    {
        $durationYears = 0;
        
        if ($supplier->tanggal_awal_kerja_sama) {
            $start = Carbon::parse($supplier->tanggal_awal_kerja_sama);
            $durationYears = $start->diffInYears(now());
        } else {
            $durationYears = $supplier->masa_kerja_sama ?? 0;
        }

        $score = $this->mapScore('C3', $durationYears);

        // Apply partnership category rule
        if ($supplier->partnership_category === 'Transactional' && $score > 2) {
            $score = 2; // Batasi maksimal skor 2 jika transactional
        }

        return [
            'score' => $score,
            'data' => [
                'duration_years' => $durationYears,
                'partnership_category' => $supplier->partnership_category,
                'label' => "Durasi: {$durationYears} tahun" . ($supplier->partnership_category ? " ({$supplier->partnership_category})" : "")
            ]
        ];
    }

    /**
     * Hitung C4 - Kapasitas Pemenuhan
     */
    protected function calculateC4(Collection $histories): array
    {
        if ($histories->isEmpty()) {
            return ['score' => 1, 'data' => ['fulfillment_rate' => 0, 'label' => 'Tidak ada transaksi']];
        }

        $totalPembelian = $histories->sum('qty_pembelian');
        $totalDiterima = $histories->sum('qty_diterima');

        $fulfillmentRate = $totalPembelian > 0 ? ($totalDiterima / $totalPembelian) * 100 : 0;
        
        // Capped at 100% just in case
        if ($fulfillmentRate > 100) $fulfillmentRate = 100;

        $score = $this->mapScore('C4', $fulfillmentRate);

        return [
            'score' => $score,
            'data' => [
                'total_pembelian' => $totalPembelian,
                'total_diterima' => $totalDiterima,
                'fulfillment_rate' => round($fulfillmentRate, 2),
                'label' => "Fulfillment Rate: " . round($fulfillmentRate, 2) . "%"
            ]
        ];
    }

    /**
     * Hitung C5 - Pengiriman
     */
    protected function calculateC5(Collection $histories): array
    {
        if ($histories->isEmpty()) {
            return ['score' => 1, 'data' => ['avg_lead_time' => null, 'label' => 'Tidak ada transaksi']];
        }

        // Gunakan lead_time_hari jika valid >= 0
        $validLeadTimes = $histories->filter(function($h) {
            return $h->lead_time_hari !== null && $h->lead_time_hari >= 0;
        })->pluck('lead_time_hari');

        if ($validLeadTimes->isEmpty()) {
             // Fallback hitung manual jika kosong
             $validLeadTimes = $histories->filter(function($h) {
                 return $h->tanggal_pembelian && $h->tanggal_penerimaan;
             })->map(function($h) {
                 $start = Carbon::parse($h->tanggal_pembelian);
                 $end = Carbon::parse($h->tanggal_penerimaan);
                 return $start->diffInDays($end);
             });
        }

        if ($validLeadTimes->isEmpty()) {
            return ['score' => 1, 'data' => ['avg_lead_time' => null, 'label' => 'Data lead time tidak valid']];
        }

        $avgLeadTime = $validLeadTimes->average();
        $score = $this->mapScore('C5', $avgLeadTime);

        return [
            'score' => $score,
            'data' => [
                'valid_transactions' => $validLeadTimes->count(),
                'avg_lead_time' => round($avgLeadTime, 2),
                'label' => "Avg Lead Time: " . round($avgLeadTime, 2) . " hari"
            ]
        ];
    }

    /**
     * Map value to score 1-5 based on criterion_score_guidelines from database
     */
    protected function mapScore(string $criteriaCode, $value): int
    {
        $criterion = $this->criteria->get($criteriaCode);
        if (!$criterion || !$criterion->scoreGuidelines) {
            return 1; // Default
        }

        foreach ($criterion->scoreGuidelines as $guideline) {
            $min = $guideline->min_value;
            $max = $guideline->max_value;
            $op = $guideline->operator;

            $match = false;

            if ($op === '<' && $value < $max) $match = true;
            elseif ($op === '<=' && $value <= $max) $match = true;
            elseif ($op === '>' && $value > $min) $match = true;
            elseif ($op === '>=' && $value >= $min) $match = true;
            elseif ($op === 'between' && $value > $min && $value <= $max) $match = true;

            if ($match) {
                return $guideline->score;
            }
        }

        // Fallback jika tidak ada yang match tapi data valid (biasanya score 5 atau 1)
        if ($criteriaCode === 'C1' && $value >= 80) return 5;
        if ($criteriaCode === 'C2' && $value <= 0.9) return 5;
        if ($criteriaCode === 'C3' && $value >= 9) return 5;
        if ($criteriaCode === 'C4' && $value >= 99) return 5;
        if ($criteriaCode === 'C5' && $value <= 8.8) return 5;

        return 1;
    }
}
