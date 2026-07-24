<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\CalculationMautRanking;
use App\Models\Calculation;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class TopSupplierWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Supplier Terbaik';
    protected int | string | array $columnSpan = 3;
    protected static ?int $sort = 7;

    public function table(Table $table): Table
    {
        $kategori = $this->filters['product_category'] ?? null;
        $kelompok = $this->filters['product_group_id'] ?? null;

        $latestCalcQuery = Calculation::whereIn('status', ['Final', 'Selesai']);
        
        if ($kategori) {
            $latestCalcQuery->where('supplier_category', $kategori);
        }
        
        if ($kelompok) {
            $latestCalcQuery->where('product_group_id', $kelompok);
        }
        
        $latestCalc = $latestCalcQuery->latest()->first();
        
        $query = CalculationMautRanking::query()->where('calculation_id', -1);
        if ($latestCalc) {
            $query = CalculationMautRanking::query()
                ->where('calculation_id', $latestCalc->id)
                ->orderBy('rank')
                ->limit(5);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('Peringkat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '2', '3' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('supplier_name')
                    ->label('Nama Supplier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('calculation.supplier_category')
                    ->label('Kategori')
                    ->badge(),
                Tables\Columns\TextColumn::make('final_score')
                    ->label('Nilai Akhir')
                    ->numeric(
                        decimalPlaces: 4,
                        decimalSeparator: ',',
                        thousandsSeparator: '.',
                    ),
                Tables\Columns\TextColumn::make('recommendation')
                    ->label('Rekomendasi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sangat Direkomendasikan' => 'success',
                        'Direkomendasikan' => 'info',
                        'Dipertimbangkan' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum tersedia hasil perankingan.');
    }
}
