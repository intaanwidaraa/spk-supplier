<?php

namespace App\Filament\Resources\CriteriaResource\Pages;

use App\Filament\Resources\CriteriaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCriteria extends CreateRecord
{
    protected static string $resource =
        CriteriaResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kriteria berhasil ditambahkan';
    }
}