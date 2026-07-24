<?php

namespace App\Filament\Resources\CriteriaResource\Widgets;

use App\Models\Criteria;
use App\Models\CriterionScoreGuideline;
use Filament\Widgets\Widget;

class CriteriaStatsOverview extends Widget
{
    protected static string $view = 'filament.resources.criteria-resource.widgets.criteria-stats-overview';

    protected int|string|array $columnSpan = 'full';

    public int $totalKriteria = 0;
    public int $totalBenefit = 0;
    public int $totalCost = 0;
    public int $totalParameter = 0;

    public function mount(): void
    {
        $this->totalKriteria = Criteria::query()->count();

        $this->totalBenefit = Criteria::query()
            ->where('atribut', 'BENEFIT')
            ->count();

        $this->totalCost = Criteria::query()
            ->where('atribut', 'COST')
            ->count();

        $this->totalParameter = CriterionScoreGuideline::query()
            ->count();
    }
}