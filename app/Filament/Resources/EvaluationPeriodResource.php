<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationPeriodResource\Pages;
use App\Models\EvaluationPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EvaluationPeriodResource extends Resource
{
    protected static ?string $model = EvaluationPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Periode Evaluasi';

    protected static ?string $navigationGroup = 'Proses Evaluasi';

    protected static ?string $modelLabel = 'Periode Evaluasi';

    protected static ?string $pluralModelLabel = 'Periode Evaluasi';

    protected static ?string $slug = 'periode-evaluasi';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Periode Evaluasi')
                ->description('Tentukan nama, kategori, dan rentang waktu periode evaluasi.')
                ->schema([
                    Forms\Components\TextInput::make('evaluation_code')
                        ->label('Kode Evaluasi')
                        ->placeholder('Akan dibuat otomatis jika dikosongkan')
                        ->maxLength(50)
                        ->helperText('Contoh: EV-2025-RM-01. Biarkan kosong untuk generate otomatis.'),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Periode')
                        ->required()
                        ->placeholder('Contoh: Evaluasi Supplier Raw Material 2025')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('year')
                        ->label('Tahun')
                        ->numeric()
                        ->required()
                        ->default(now()->year)
                        ->minValue(2000)
                        ->maxValue(2099),

                    Forms\Components\Select::make('product_category')
                        ->label('Kategori Produk')
                        ->options([
                            'Raw Material'       => 'Raw Material',
                            'Packaging Material' => 'Packaging Material',
                        ])
                        ->native(false)
                        ->required(),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->native(false)
                        ->displayFormat('d M Y'),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->after('start_date'),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'Draft'   => 'Draft',
                            'Aktif'   => 'Aktif',
                            'Selesai' => 'Selesai',
                        ])
                        ->default('Draft')
                        ->native(false)
                        ->required(),

                    Forms\Components\Textarea::make('description')
                        ->label('Keterangan')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('evaluation_code')
                    ->label('Kode Evaluasi')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Periode')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('product_category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Material'       => 'warning',
                        'Packaging Material' => 'success',
                        default              => 'gray',
                    }),

                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif'   => 'success',
                        'Selesai' => 'info',
                        default   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('assessments_count')
                    ->label('Penilaian')
                    ->counts('assessments')
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_category')
                    ->label('Kategori')
                    ->options([
                        'Raw Material'       => 'Raw Material',
                        'Packaging Material' => 'Packaging Material',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['Draft' => 'Draft', 'Aktif' => 'Aktif', 'Selesai' => 'Selesai'])
                    ->native(false),

                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(
                        EvaluationPeriod::distinct()->orderByDesc('year')->pluck('year', 'year')->toArray()
                    )
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit Periode')
                    ->icon('heroicon-m-pencil-square'),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus Periode')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->visible(fn (EvaluationPeriod $record): bool => $record->status === 'Draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('year', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Belum ada periode evaluasi')
            ->emptyStateDescription('Buat periode evaluasi terlebih dahulu sebelum melakukan penilaian.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEvaluationPeriods::route('/'),
            'create' => Pages\CreateEvaluationPeriod::route('/create'),
            'edit'   => Pages\EditEvaluationPeriod::route('/{record}/edit'),
        ];
    }
}
