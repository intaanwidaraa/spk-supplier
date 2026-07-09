<?php

namespace App\Filament\Resources\CriteriaResource\Widgets;

use App\Models\Criteria;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CriteriaStatsOverview extends StatsOverviewWidget
{
    /**
     * Widget memenuhi lebar halaman.
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * Nonaktifkan polling otomatis.
     */
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totalCriteria = Criteria::query()->count();

        $totalBobot = (float) Criteria::query()
            ->sum('bobot_default');

        $isValid = abs($totalBobot - 100) < 0.00001;

        if ($isValid) {
            $status = 'Bobot Valid';
            $description =
                'Total bobot tepat 100%';
            $statusColor = 'success';
            $statusIcon =
                'heroicon-m-check-circle';
        } elseif ($totalBobot < 100) {
            $status = 'Bobot Belum Valid';
            $description =
                'Masih kurang '
                . $this->formatWeight(
                    100 - $totalBobot
                )
                . '%';

            $statusColor = 'warning';
            $statusIcon =
                'heroicon-m-exclamation-triangle';
        } else {
            $status = 'Bobot Tidak Valid';
            $description =
                'Melebihi '
                . $this->formatWeight(
                    $totalBobot - 100
                )
                . '%';

            $statusColor = 'danger';
            $statusIcon =
                'heroicon-m-x-circle';
        }

        return [
            Stat::make(
                'Total Kriteria',
                $totalCriteria
            ),

            Stat::make(
                'Total Bobot',
                $this->formatWeight(
                    $totalBobot
                ) . '%'
            ),

            Stat::make(
                'Status Validasi',
                $status
            )
                ->description($description)
                ->descriptionIcon($statusIcon)
                ->color($statusColor),
        ];
    }

    private function formatWeight(
        float $value
    ): string {
        return rtrim(
            rtrim(
                number_format(
                    $value,
                    2,
                    ',',
                    '.'
                ),
                '0'
            ),
            ','
        );
    }
}