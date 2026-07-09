<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CriteriaResource\Pages;
use App\Filament\Resources\CriteriaResource\Widgets\CriteriaStatsOverview;
use App\Models\Criteria;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;

class CriteriaResource extends Resource
{
    protected static ?string $model = Criteria::class;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'master-kriteria';
    protected static ?string $navigationIcon =
        'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel =
        'Kelola Kriteria';

    protected static ?string $modelLabel =
        'Kriteria';

    protected static ?string $pluralModelLabel =
        'Kriteria';

    protected static ?string $recordTitleAttribute =
        'nama_kriteria';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(
                    'Data Kriteria Penilaian'
                )
                    ->description(
                        'Kelola parameter, sifat, dan bobot kriteria untuk evaluasi supplier.'
                    )
                    ->schema([
                        Forms\Components\TextInput::make(
                            'kode_kriteria'
                        )
                            ->label('ID Kriteria')
                            ->default(
                                fn (): string =>
                                    Criteria::generateNextCode()
                            )
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(
                                table: Criteria::class,
                                column: 'kode_kriteria',
                                ignoreRecord: true
                            ),

                        Forms\Components\TextInput::make(
                            'nama_kriteria'
                        )
                            ->label('Nama Kriteria')
                            ->placeholder(
                                'Contoh: Harga Penawaran'
                            )
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make(
                            'sifat_kriteria'
                        )
                            ->label('Sifat Kriteria')
                            ->options([
                                'COST' => 'COST',
                                'BENEFIT' => 'BENEFIT',
                            ])
                            ->helperText(
                                'COST: semakin kecil semakin baik. BENEFIT: semakin besar semakin baik.'
                            )
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make(
                            'bobot_default'
                        )
                            ->label('Bobot Default')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->placeholder('Contoh: 25')
                            ->required()
                            ->rules(
                                fn (
                                    ?Criteria $record
                                ): array => [
                                    function (
                                        string $attribute,
                                        mixed $value,
                                        Closure $fail
                                    ) use ($record): void {
                                        $query = Criteria::query();

                                        if ($record?->exists) {
                                            $query->where(
                                                'id',
                                                '!=',
                                                $record->getKey()
                                            );
                                        }

                                        $existingTotal = (float)
                                            $query->sum(
                                                'bobot_default'
                                            );

                                        $newTotal =
                                            $existingTotal
                                            + (float) $value;

                                        if ($newTotal > 100.00001) {
                                            $fail(
                                                'Total bobot seluruh kriteria tidak boleh melebihi 100%. Total setelah disimpan: '
                                                . number_format(
                                                    $newTotal,
                                                    2
                                                )
                                                . '%.'
                                            );
                                        }
                                    },
                                ]
                            ),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(
                    'kode_kriteria'
                )
                    ->label('ID Kriteria')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'nama_kriteria'
                )
                    ->label('Nama Kriteria')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make(
                    'sifat_kriteria'
                )
                    ->label('Sifat Kriteria')
                    ->badge()
                    ->alignCenter()
                    ->color(
                        fn (string $state): string =>
                            match ($state) {
                                'COST' => 'danger',
                                'BENEFIT' => 'success',
                                default => 'gray',
                            }
                    ),

                Tables\Columns\TextColumn::make(
                    'bobot_default'
                )
                    ->label('Bobot Default (%)')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->alignCenter()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label(
                                'Total Bobot Keseluruhan'
                            )
                            ->numeric(decimalPlaces: 2)
                            ->suffix('%')
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make(
                    'sifat_kriteria'
                )
                    ->label('Sifat Kriteria')
                    ->options([
                        'COST' => 'COST',
                        'BENEFIT' => 'BENEFIT',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit Kriteria')
                    ->icon('heroicon-m-pencil-square'),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus Kriteria')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([])
            ->defaultSort('kode_kriteria')
            ->emptyStateHeading(
                'Belum ada data kriteria'
            )
            ->emptyStateDescription(
                'Tambahkan kriteria yang digunakan dalam proses evaluasi supplier.'
            )
            ->emptyStateIcon(
                'heroicon-o-adjustments-horizontal'
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Daftarkan widget statistik milik resource.
     */
    public static function getWidgets(): array
    {
        return [
            CriteriaStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListCriterias::route('/'),

            'create' =>
                Pages\CreateCriteria::route('/create'),

            'edit' =>
                Pages\EditCriteria::route(
                    '/{record}/edit'
                ),
        ];
    }
    public static function shouldRegisterNavigation(): bool
{
    return false;
}
}