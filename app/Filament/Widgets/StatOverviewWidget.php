<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use App\Models\Calculation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatOverviewWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $totalSuppliers = Supplier::count();
        $rawMaterialSuppliers = Supplier::where('kategori', 'Raw Material')->count();
        $packagingSuppliers = Supplier::where('kategori', 'Packaging Material')->count();
        
        $totalCalculations = Calculation::count();
        $completedCalculations = Calculation::whereIn('status', ['Final', 'Selesai'])->count();
        $pendingCalculations = Calculation::whereNotIn('status', ['Final', 'Selesai'])->count();

        $calculationDesc = "Selesai: {$completedCalculations} | Belum Selesai: {$pendingCalculations}";

        return [
            Stat::make('Total Supplier', $totalSuppliers)
                ->description('Seluruh supplier terdaftar')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make('Supplier Raw Material', $rawMaterialSuppliers)
                ->description('Supplier bahan baku utama')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Supplier Packaging Material', $packagingSuppliers)
                ->description('Supplier bahan kemasan')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning'),

            Stat::make('Total Riwayat Perhitungan', $totalCalculations)
                ->description($calculationDesc)
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),
        ];
    }
}
