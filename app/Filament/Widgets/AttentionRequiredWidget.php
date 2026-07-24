<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Supplier;
use App\Models\Calculation;
use App\Models\SupplierPerformanceAssessment;

class AttentionRequiredWidget extends Widget
{
    protected static string $view = 'filament.widgets.attention-required-widget';
    protected int | string | array $columnSpan = 3;
    protected static ?int $sort = 6;

    protected function getViewData(): array
    {
        // Supplier tanpa penilaian
        $assessedSupplierIds = SupplierPerformanceAssessment::pluck('supplier_id')->unique();
        $unassessedSuppliersCount = Supplier::whereNotIn('id', $assessedSupplierIds)->count();

        // Riwayat draft/gagal
        $draftCalculationsCount = Calculation::whereIn('status', ['Draft', 'Gagal'])->count();
        
        $hasAttention = $unassessedSuppliersCount > 0 || $draftCalculationsCount > 0;

        return [
            'unassessedSuppliersCount' => $unassessedSuppliersCount,
            'draftCalculationsCount' => $draftCalculationsCount,
            'hasAttention' => $hasAttention,
        ];
    }
}
