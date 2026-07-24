<?php

namespace App\Filament\Resources\CriteriaResource\Pages;

use App\Filament\Resources\CriteriaResource;
use App\Filament\Resources\CriteriaResource\Widgets\CriteriaStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCriterias extends ListRecords
{
    protected static string $resource =
        CriteriaResource::class;

    public function getTitle(): string
    {
        return 'Daftar Kriteria Penilaian';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola bobot dan parameter kriteria untuk evaluasi supplier.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kriteria Baru')
                ->icon('heroicon-m-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\CriteriaResource\Widgets\CriteriaStatsOverview::class,
        ];
    }
}