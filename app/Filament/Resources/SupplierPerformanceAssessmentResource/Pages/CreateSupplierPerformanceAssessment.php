<?php

namespace App\Filament\Resources\SupplierPerformanceAssessmentResource\Pages;

use App\Filament\Resources\SupplierPerformanceAssessmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierPerformanceAssessment extends CreateRecord
{
    protected static string $resource = SupplierPerformanceAssessmentResource::class;

    public function getTitle(): string
    {
        return 'Tambah Penilaian Manual';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calculate total_score before saving
        $scores = array_filter([
            $data['c1_score'] ?? null,
            $data['c2_score'] ?? null,
            $data['c3_score'] ?? null,
            $data['c4_score'] ?? null,
            $data['c5_score'] ?? null,
        ], fn ($s) => $s !== null);

        $data['total_score']       = empty($scores) ? null : round(array_sum($scores) / count($scores), 4);
        $data['is_auto_calculated'] = false;
        $data['assessment_date']   = now()->toDateString();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
