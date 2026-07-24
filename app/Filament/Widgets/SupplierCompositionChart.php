<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use Filament\Widgets\ChartWidget;

class SupplierCompositionChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Supplier';
    protected int | string | array $columnSpan = 2;
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $rawMaterial = Supplier::where('kategori', 'Raw Material')->count();
        $packagingMaterial = Supplier::where('kategori', 'Packaging Material')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Total Supplier',
                    'data' => [$rawMaterial, $packagingMaterial],
                    'backgroundColor' => ['#f59e0b', '#3b82f6'],
                ],
            ],
            'labels' => ['Raw Material', 'Packaging Material'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
