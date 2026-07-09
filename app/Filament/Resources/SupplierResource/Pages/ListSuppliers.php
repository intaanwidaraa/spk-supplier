<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Filament\Resources\SupplierResource\Widgets\SupplierStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    protected static string $resource =
        SupplierResource::class;

    public function getTitle(): string
    {
        return 'Daftar Supplier';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola identitas, jenis produk, dan masa kerja sama supplier.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Supplier Baru')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SupplierStatsOverview::class,
        ];
    }
}