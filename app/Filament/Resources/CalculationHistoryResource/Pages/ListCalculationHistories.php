<?php

namespace App\Filament\Resources\CalculationHistoryResource\Pages;

use App\Filament\Resources\CalculationHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCalculationHistories extends ListRecords
{
    protected static string $resource = CalculationHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
