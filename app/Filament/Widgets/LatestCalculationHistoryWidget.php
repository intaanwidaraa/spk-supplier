<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Calculation;
use Filament\Tables\Actions\Action;

class LatestCalculationHistoryWidget extends BaseWidget
{
    protected static ?string $heading = 'Riwayat Perhitungan Terbaru';
    protected int | string | array $columnSpan = 3;
    protected static ?int $sort = 8;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Calculation::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('calculation_code')
                    ->label('Kode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier_category')
                    ->label('Kategori')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_selected')
                    ->label('Jml Supplier')
                    ->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Final', 'Selesai' => 'success',
                        'Draft' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('lihat')
                    ->label('Lihat Hasil')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Calculation $record): string => url('/admin/calculation-histories/' . $record->id))
            ])
            ->paginated(false);
    }
}
