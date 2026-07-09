<?php

namespace App\Filament\Resources\SupplierPerformanceResource\Pages;

use App\Filament\Resources\SupplierPerformanceResource;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPerformances extends ListRecords
{
    protected static string $resource =
        SupplierPerformanceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}