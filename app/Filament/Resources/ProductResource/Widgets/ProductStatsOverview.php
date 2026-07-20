<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Produk', Product::count())
                ->description('Total keseluruhan produk')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Raw Material', Product::where('kategori_produk', 'Raw Material')->count())
                ->description('Produk bahan baku')
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),

            Stat::make('Packaging Material', Product::where('kategori_produk', 'Packaging Material')->count())
                ->description('Produk bahan kemasan')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),
        ];
    }
}
