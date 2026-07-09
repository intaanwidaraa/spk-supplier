<?php

namespace App\Filament\Resources\CriteriaRatingResource\Pages;

use App\Filament\Resources\CriteriaRatingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCriteriaRating extends CreateRecord
{
    protected static string $resource =
        CriteriaRatingResource::class;

    public function getTitle(): string
    {
        return 'Tambah Kriteria';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kriteria berhasil ditambahkan';
    }
}