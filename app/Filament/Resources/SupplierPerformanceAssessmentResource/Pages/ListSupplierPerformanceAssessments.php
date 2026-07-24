<?php

namespace App\Filament\Resources\SupplierPerformanceAssessmentResource\Pages;

use App\Filament\Resources\SupplierPerformanceAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPerformanceAssessments extends ListRecords
{
    protected static string $resource = SupplierPerformanceAssessmentResource::class;

    public function mount(): void
    {
        if (auth()->check() && auth()->user()->isDirektur()) {
            redirect()->to(\App\Filament\Resources\CalculationHistoryResource::getUrl('index'))->send();
        }

        parent::mount();

        $exists = \App\Models\SupplierPerformanceAssessment::query()->exists();

        if (!$exists) {
            app(\App\Services\SupplierPerformanceCalculationService::class)
                ->calculateAndSyncAll();
        }
    }

    public function getTitle(): string
    {
        return 'Data Penilaian Kinerja Supplier';
    }

    public function getSubheading(): ?string
    {
        return 'Hitung otomatis skor C1–C5 berdasarkan data historis pembelian.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Manual')
                ->icon('heroicon-m-plus'),
        ];
    }
}

