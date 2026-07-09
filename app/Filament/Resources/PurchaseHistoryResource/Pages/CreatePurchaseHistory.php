<?php

namespace App\Filament\Resources\PurchaseHistoryResource\Pages;

use App\Filament\Resources\PurchaseHistoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseHistory extends CreateRecord
{
    protected static string $resource =
        PurchaseHistoryResource::class;

    public function getTitle(): string
    {
        return 'Tambah Data Historis';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Data historis berhasil ditambahkan';
    }
}