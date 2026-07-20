<?php

namespace App\Filament\Resources\SupplierPerformanceAssessmentResource\Pages;

use App\Filament\Resources\SupplierPerformanceAssessmentResource;
use App\Models\SupplierPerformanceScoreDetail;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSupplierPerformanceAssessment extends EditRecord
{
    protected static string $resource = SupplierPerformanceAssessmentResource::class;

    public function getTitle(): string
    {
        return 'Edit Penilaian: ' . ($this->record->supplier?->nama_supplier ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status !== 'Final'),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // Recalculate total score
        $scores = array_filter([
            $record->c1_score,
            $record->c2_score,
            $record->c3_score,
            $record->c4_score,
            $record->c5_score,
        ], fn ($s) => $s !== null);

        $total = empty($scores) ? 0 : round(array_sum($scores) / count($scores), 4);
        $record->update(['total_score' => $total]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
