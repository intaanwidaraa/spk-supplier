<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('product_category')
                    ->label('Kategori Supplier')
                    ->options([
                        'Raw Material' => 'Raw Material',
                        'Packaging Material' => 'Packaging Material',
                    ])
                    ->live(),
                Select::make('product_group_id')
                    ->label('Kelompok Produk')
                    ->options(\App\Models\ProductGroup::pluck('nama_kelompok_produk', 'id'))
                    ->searchable()
                    ->live(),
            ])
            ->columns(2);
    }

    public function getColumns(): int | string | array
    {
        return 6;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\WelcomeWidget::class,
            \App\Filament\Widgets\StatOverviewWidget::class,
            \App\Filament\Widgets\SupplierCompositionChart::class,
            \App\Filament\Widgets\CriteriaAverageChart::class,
            \App\Filament\Widgets\LatestCalculationWidget::class,
            \App\Filament\Widgets\AttentionRequiredWidget::class,
            \App\Filament\Widgets\TopSupplierWidget::class,
            \App\Filament\Widgets\LatestCalculationHistoryWidget::class,
        ];
    }
}
