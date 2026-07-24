<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculationHistoryResource\Pages;
use App\Models\Calculation;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class CalculationHistoryResource extends Resource
{
    protected static ?string $model = Calculation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationGroup = 'Analisis dan Perhitungan';
    
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return auth()->user()->isDirektur() ? 'Penilaian Supplier' : 'Riwayat Perhitungan';
    }

    public static function getModelLabel(): string
    {
        return 'Riwayat Perhitungan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Riwayat Perhitungan Penilaian Supplier';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Daftar riwayat proses evaluasi dan perhitungan kinerja supplier yang telah dilakukan oleh Administrator.')
            ->columns([
                Tables\Columns\TextColumn::make('calculation_code')
                    ->label('Kode Perhitungan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('calculation_name')
                    ->label('Nama Perhitungan')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Tanggal Perhitungan')
                    ->date('d F Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier_category')
                    ->label('Kategori Supplier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Material' => 'info',
                        'Packaging Material' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('productGroup.nama_kelompok_produk')
                    ->label('Kelompok Produk')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Semua Kelompok'),

                Tables\Columns\TextColumn::make('total_selected')
                    ->label('Jumlah Supplier')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Final' => 'success',
                        'Selesai' => 'success',
                        'Draft' => 'gray',
                        default => 'warning',
                    })
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Waktu Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('period_start', 'desc')
            ->filters([
                SelectFilter::make('supplier_category')
                    ->label('Kategori Supplier')
                    ->options([
                        'Raw Material' => 'Raw Material',
                        'Packaging Material' => 'Packaging Material',
                    ]),
                    
                SelectFilter::make('product_group_id')
                    ->label('Kelompok Produk')
                    ->relationship('productGroup', 'nama_kelompok_produk')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('status')
                    ->label('Status Perhitungan')
                    ->options([
                        'Final' => 'Final',
                        'Draft' => 'Draft',
                    ]),
                    
                Filter::make('tanggal_perhitungan')
                    ->form([
                        DatePicker::make('dari')
                            ->label('Tanggal Mulai'),
                        DatePicker::make('sampai')
                            ->label('Tanggal Selesai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('period_start', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('period_start', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('Lihat Hasil')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Calculation $record): string => url('/admin/laporan-terpusat?jenis_laporan=evaluasi&calculation_id=' . $record->id))
                    ->visible(fn (Calculation $record) => $record->status === 'Final' || $record->status === 'Selesai'),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculationHistories::route('/'),
        ];
    }
}
