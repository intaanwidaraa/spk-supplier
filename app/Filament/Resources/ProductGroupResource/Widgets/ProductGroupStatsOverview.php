<?php

namespace App\Filament\Resources\ProductGroupResource\Widgets;

use App\Models\ProductGroup;
use App\Models\Product;
use Filament\Widgets\Widget;

class ProductGroupStatsOverview extends Widget
{
    protected static string $view = 'filament.resources.product-group-resource.widgets.product-group-stats-overview';

    protected int|string|array $columnSpan = 'full';

    public int $totalKelompok = 0;
    public int $totalDetail = 0;
    public int $totalPackagingKelompok = 0;
    public int $totalPackagingDetail = 0;
    public int $totalRawKelompok = 0;
    public int $totalRawDetail = 0;

    public function mount(): void
    {
        $this->totalKelompok = ProductGroup::query()->count();
        $this->totalDetail = Product::query()->count();
        
        $packagingIds = ProductGroup::query()->where('kategori_produk', 'Packaging Material')->pluck('id');
        $this->totalPackagingKelompok = $packagingIds->count();
        $this->totalPackagingDetail = Product::query()->whereIn('product_group_id', $packagingIds)->count();

        $rawIds = ProductGroup::query()->where('kategori_produk', 'Raw Material')->pluck('id');
        $this->totalRawKelompok = $rawIds->count();
        $this->totalRawDetail = Product::query()->whereIn('product_group_id', $rawIds)->count();
    }
}
