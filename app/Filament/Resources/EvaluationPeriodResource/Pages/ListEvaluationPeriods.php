<?php

namespace App\Filament\Resources\EvaluationPeriodResource\Pages;

use App\Filament\Resources\EvaluationPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvaluationPeriods extends ListRecords
{
    protected static string $resource = EvaluationPeriodResource::class;

    public function getTitle(): string
    {
        return 'Periode Evaluasi';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola periode waktu evaluasi supplier berdasarkan kategori produk.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Periode Evaluasi')
                ->icon('heroicon-m-plus'),
        ];
    }
}
