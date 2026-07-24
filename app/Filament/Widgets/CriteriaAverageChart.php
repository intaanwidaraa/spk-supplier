<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SupplierPerformanceAssessment;
use App\Models\Criteria;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class CriteriaAverageChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Rata-Rata Penilaian Supplier';
    protected int | string | array $columnSpan = 4;
    protected static ?int $sort = 4;
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $kategori = $this->filters['product_category'] ?? null;
        $kelompok = $this->filters['product_group_id'] ?? null;

        $query = SupplierPerformanceAssessment::query();

        if ($kategori) {
            $query->where('product_category', $kategori);
        }
        
        if ($kelompok) {
            $query->where('product_group_id', $kelompok);
        }

        $avgC1 = (float) $query->avg('c1_score');
        $avgC2 = (float) $query->avg('c2_score');
        $avgC3 = (float) $query->avg('c3_score');
        $avgC4 = (float) $query->avg('c4_score');
        $avgC5 = (float) $query->avg('c5_score');

        $criteria = Criteria::pluck('nama_kriteria', 'kode_kriteria');

        return [
            'datasets' => [
                [
                    'label' => 'Rata-Rata Skor',
                    'data' => [$avgC1, $avgC2, $avgC3, $avgC4, $avgC5],
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => [
                $criteria['C1'] ?? 'C1',
                $criteria['C2'] ?? 'C2',
                $criteria['C3'] ?? 'C3',
                $criteria['C4'] ?? 'C4',
                $criteria['C5'] ?? 'C5',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
