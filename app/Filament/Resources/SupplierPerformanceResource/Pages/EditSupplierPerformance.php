<?php

namespace App\Filament\Resources\SupplierPerformanceResource\Pages;

use App\Filament\Resources\SupplierPerformanceResource;
use App\Models\Criteria;
use App\Models\Supplier;
use App\Models\SupplierScore;
use Filament\Resources\Pages\EditRecord;

class EditSupplierPerformance extends EditRecord
{
    protected static string $resource =
        SupplierPerformanceResource::class;

    public function mount(int|string $record): void
    {
        $supplier = Supplier::query()->findOrFail($record);

        $criterionIds = Criteria::query()
            ->orderBy('kode_kriteria')
            ->pluck('id');

        foreach ($criterionIds as $criterionId) {
            SupplierScore::query()->firstOrCreate([
                'supplier_id' => $supplier->getKey(),
                'criterion_id' => $criterionId,
            ]);
        }

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Penilaian kinerja berhasil disimpan';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}