<?php

namespace App\Filament\Resources\SupplierResource\Widgets;

use App\Models\Supplier;
use Filament\Widgets\Widget;

class SupplierStatsOverview extends Widget
{
    protected static string $view =
        'filament.resources.supplier-resource.widgets.supplier-stats-overview';

    protected int|string|array $columnSpan = 'full';

    public int $totalSupplier = 0;

    public int $totalRawMaterial = 0;

    public int $totalPackagingMaterial = 0;

    public function mount(): void
    {
        $this->totalSupplier = Supplier::query()->count();

        $this->totalRawMaterial = Supplier::query()
            ->where('kategori', 'Raw Material')
            ->count();

        $this->totalPackagingMaterial = Supplier::query()
            ->where('kategori', 'Packaging Material')
            ->count();
    }
}