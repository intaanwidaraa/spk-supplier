<?php

namespace App\Filament\Resources\CriteriaResource\Pages;

use App\Filament\Resources\CriteriaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCriteria extends EditRecord
{
    protected static string $resource =
        CriteriaResource::class;

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
        return 'Kriteria berhasil diperbarui';
    }
}