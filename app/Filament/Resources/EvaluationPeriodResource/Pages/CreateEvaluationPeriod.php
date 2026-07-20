<?php

namespace App\Filament\Resources\EvaluationPeriodResource\Pages;

use App\Filament\Resources\EvaluationPeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvaluationPeriod extends CreateRecord
{
    protected static string $resource = EvaluationPeriodResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
