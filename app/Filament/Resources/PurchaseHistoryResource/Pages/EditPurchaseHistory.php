<?php

namespace App\Filament\Resources\PurchaseHistoryResource\Pages;

use App\Filament\Resources\PurchaseHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseHistory extends EditRecord
{
    protected static string $resource =
        PurchaseHistoryResource::class;

    public function getTitle(): string
    {
        return 'Edit Data Historis';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data historis berhasil diperbarui';
    }
}