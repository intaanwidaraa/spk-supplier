<?php

namespace App\Filament\Resources\PurchaseHistoryResource\Widgets;

use App\Models\PurchaseHistory;
use Filament\Widgets\Widget;

class PurchaseHistoryStatsOverview extends Widget
{
    protected static string $view = 'filament.resources.purchase-history-resource.widgets.purchase-history-stats-overview';

    protected int|string|array $columnSpan = 'full';

    public int $totalTransaksi = 0;
    public int $totalSupplier = 0;
    public string $totalNilai = 'Rp0';
    public string $avgLeadTime = '0 hari';

    public function mount(): void
    {
        $this->totalTransaksi = PurchaseHistory::query()->count();
        
        $this->totalSupplier = PurchaseHistory::query()
            ->whereNotNull('supplier_id')
            ->distinct()
            ->count('supplier_id');
            
        $sum = PurchaseHistory::query()
            ->selectRaw('
                COALESCE(
                    SUM(
                        CASE
                            WHEN total_pembelian IS NOT NULL
                                 AND total_pembelian > 0
                            THEN total_pembelian
                            ELSE COALESCE(qty_pembelian, 0)
                                 * COALESCE(harga_satuan, 0)
                        END
                    ),
                    0
                ) AS total
            ')
            ->value('total');

        $this->totalNilai = $this->formatRupiah((float) $sum);

        $avg = PurchaseHistory::query()
            ->whereNotNull('lead_time_hari')
            ->where('lead_time_hari', '>=', 0)
            ->avg('lead_time_hari');

        $this->avgLeadTime = $avg !== null
            ? number_format((float) $avg, 1, ',', '.') . ' hari'
            : 'Belum tersedia';
    }
    
    private function formatRupiah(float|int|null $number): string
    {
        $number = (float) ($number ?? 0);

        if ($number >= 1_000_000_000_000) {
            return 'Rp' . number_format(
                $number / 1_000_000_000_000,
                1,
                ',',
                '.'
            ) . ' T';
        }

        if ($number >= 1_000_000_000) {
            return 'Rp' . number_format(
                $number / 1_000_000_000,
                1,
                ',',
                '.'
            ) . ' M';
        }

        if ($number >= 1_000_000) {
            return 'Rp' . number_format(
                $number / 1_000_000,
                1,
                ',',
                '.'
            ) . ' Jt';
        }

        if ($number >= 1_000) {
            return 'Rp' . number_format(
                $number / 1_000,
                1,
                ',',
                '.'
            ) . ' Rb';
        }

        return 'Rp' . number_format($number, 0, ',', '.');
    }
}
