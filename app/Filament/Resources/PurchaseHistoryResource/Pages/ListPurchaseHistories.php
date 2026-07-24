<?php

namespace App\Filament\Resources\PurchaseHistoryResource\Pages;

use App\Filament\Resources\PurchaseHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseHistories extends ListRecords
{
    protected static string $resource =
        PurchaseHistoryResource::class;

    public function getTitle(): string
    {
        return 'Data Historis Pembelian & Penerimaan';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola riwayat transaksi pembelian, penerimaan, dan pemenuhan pesanan supplier.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data Historis')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\PurchaseHistoryResource\Widgets\PurchaseHistoryStatsOverview::class,
        ];
    }
}