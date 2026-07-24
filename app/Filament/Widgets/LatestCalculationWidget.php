<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Calculation;
use App\Models\User;

class LatestCalculationWidget extends Widget
{
    protected static string $view = 'filament.widgets.latest-calculation-widget';
    protected int | string | array $columnSpan = 3;
    protected static ?int $sort = 5;

    protected function getViewData(): array
    {
        $calculation = Calculation::with(['mautRankings' => function($q) {
            $q->orderBy('rank')->take(1);
        }])->latest()->first();
        
        $user = null;
        if ($calculation && $calculation->calculated_by) {
            $user = User::find($calculation->calculated_by);
        }

        return [
            'calculation' => $calculation,
            'user' => $user,
        ];
    }
}
