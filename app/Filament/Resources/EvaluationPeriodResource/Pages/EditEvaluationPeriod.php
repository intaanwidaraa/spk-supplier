<?php

namespace App\Filament\Resources\EvaluationPeriodResource\Pages;

use App\Filament\Resources\EvaluationPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvaluationPeriod extends EditRecord
{
    protected static string $resource = EvaluationPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
