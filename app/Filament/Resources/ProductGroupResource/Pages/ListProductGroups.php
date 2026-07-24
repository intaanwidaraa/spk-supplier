<?php

namespace App\Filament\Resources\ProductGroupResource\Pages;

use App\Filament\Resources\ProductGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductGroups extends ListRecords
{
    protected static string $resource = ProductGroupResource::class;

    public function getTitle(): string
    {
        return 'Daftar Kelompok Produk';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola kelompok produk. Klik Edit untuk menambahkan produk detail di dalam kelompok.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kelompok Produk')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\ProductGroupResource\Widgets\ProductGroupStatsOverview::class,
        ];
    }
}
