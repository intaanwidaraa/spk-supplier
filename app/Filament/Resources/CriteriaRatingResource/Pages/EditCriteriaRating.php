<?php

namespace App\Filament\Resources\CriteriaRatingResource\Pages;

use App\Filament\Resources\CriteriaRatingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCriteriaRating extends EditRecord
{
    protected static string $resource =
        CriteriaRatingResource::class;

    public function getTitle(): string
    {
        return 'Edit Kriteria';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Kriteria berhasil diperbarui';
    }
}