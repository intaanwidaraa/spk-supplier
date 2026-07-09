<?php

namespace App\Filament\Resources\CriteriaRatingResource\Pages;

use App\Filament\Resources\CriteriaRatingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCriteriaRatings extends ListRecords
{
    protected static string $resource =
        CriteriaRatingResource::class;

    public function getTitle(): string
    {
        return 'Kelola Kriteria';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola kategori, skor, dan keterangan untuk setiap jenis kriteria.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kriteria')
                ->icon('heroicon-m-plus'),
        ];
    }
}