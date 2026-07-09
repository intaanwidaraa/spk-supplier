<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CriteriaRatingResource\Pages;
use App\Models\CriteriaRating;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class CriteriaRatingResource extends Resource
{
    protected static ?string $model =
        CriteriaRating::class;

    protected static ?string $navigationIcon =
        'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel =
        'Kelola Kriteria';
    
    protected static ?string $navigationGroup =
    'Master Data';

    protected static ?string $modelLabel =
        'Kriteria';

    protected static ?string $pluralModelLabel =
        'Kelola Kriteria';

    protected static ?string $recordTitleAttribute =
        'jenis_kriteria';

    protected static ?string $slug =
        'kelola-kriteria';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(
                    'Data Kriteria Penilaian'
                )
                    ->description(
                        'Isi kategori, atribut, skor, dan keterangan untuk setiap kriteria.'
                    )
                    ->schema([
                        /*
                         * Baris pertama: Jenis Kriteria dan Atribut
                         */
                        Forms\Components\TextInput::make(
                            'jenis_kriteria'
                        )
                            ->label('Jenis Kriteria')
                            ->placeholder(
                                'Contoh: Kualitas Produk'
                            )
                            ->helperText(
                                'Ketik nama kriteria secara langsung.'
                            )
                            ->required()
                            ->maxLength(150),

                        Forms\Components\ToggleButtons::make(
                            'atribut'
                        )
                            ->label('Atribut')
                            ->options([
                                'BENEFIT' => 'BENEFIT',
                                'COST' => 'COST',
                            ])
                            ->colors([
                                'BENEFIT' => 'success',
                                'COST' => 'danger',
                            ])
                            ->icons([
                                'BENEFIT' =>
                                    'heroicon-m-arrow-trending-up',

                                'COST' =>
                                    'heroicon-m-arrow-trending-down',
                            ])
                            ->grouped()
                            ->inline()
                            ->required(),

                        /*
                         * Baris kedua: Kategori dan Skor
                         */
                        Forms\Components\TextInput::make(
                            'kategori'
                        )
                            ->label('Kategori')
                            ->placeholder(
                                'Contoh: Sangat Baik'
                            )
                            ->helperText(
                                'Nama kategori dapat berbeda pada setiap kriteria.'
                            )
                            ->required()
                            ->maxLength(100),

                        Forms\Components\ToggleButtons::make(
                            'skor'
                        )
                            ->label('Skor')
                            ->options([
                                1 => '1',
                                2 => '2',
                                3 => '3',
                                4 => '4',
                                5 => '5',
                            ])
                            ->colors([
                                1 => 'danger',
                                2 => 'warning',
                                3 => 'gray',
                                4 => 'info',
                                5 => 'success',
                            ])
                            ->grouped()
                            ->inline()
                            ->required()
                            ->rules(
                                function (
                                    Get $get,
                                    ?CriteriaRating $record
                                ): array {
                                    $rule = Rule::unique(
                                        'criteria_ratings',
                                        'skor'
                                    )->where(
                                        fn ($query) =>
                                            $query->where(
                                                'jenis_kriteria',
                                                $get(
                                                    'jenis_kriteria'
                                                )
                                            )
                                    );

                                    if ($record?->exists) {
                                        $rule->ignore(
                                            $record->getKey()
                                        );
                                    }

                                    return [$rule];
                                }
                            )
                            ->validationMessages([
                                'unique' =>
                                    'Skor ini sudah digunakan untuk jenis kriteria tersebut.',
                            ]),

                        /*
                         * Baris ketiga: Keterangan penuh
                         */
                        Forms\Components\Textarea::make(
                            'keterangan'
                        )
                            ->label('Keterangan')
                            ->placeholder(
                                'Contoh: Minimal 4 dari setiap 5 jenis produk pernah dipesan ulang.'
                            )
                            ->helperText(
                                'Tuliskan parameter penilaian dengan kalimat yang jelas dan terukur.'
                            )
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder =>
                    $query
                        ->orderBy('jenis_kriteria')
                        ->orderByDesc('skor')
            )
            ->columns([
                Tables\Columns\TextColumn::make(
                    'jenis_kriteria'
                )
                    ->label('Jenis Kriteria')
                    ->icon(
                        'heroicon-m-adjustments-horizontal'
                    )
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make(
                    'atribut'
                )
                    ->label('Atribut')
                    ->badge()
                    ->color(
                        fn (string $state): string =>
                            match ($state) {
                                'BENEFIT' => 'success',
                                'COST' => 'danger',
                                default => 'gray',
                            }
                    )
                    ->icon(
                        fn (string $state): string =>
                            match ($state) {
                                'BENEFIT' =>
                                    'heroicon-m-arrow-trending-up',

                                'COST' =>
                                    'heroicon-m-arrow-trending-down',

                                default =>
                                    'heroicon-m-minus',
                            }
                    )
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'kategori'
                )
                    ->label('Kategori')
                    ->badge()
                    ->color(
                        fn (CriteriaRating $record): string =>
                            match ($record->skor) {
                                1 => 'danger',
                                2 => 'warning',
                                3 => 'gray',
                                4 => 'info',
                                5 => 'success',
                                default => 'gray',
                            }
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'skor'
                )
                    ->label('Skor')
                    ->badge()
                    ->color(
                        fn ($state): string =>
                            match ((int) $state) {
                                1 => 'danger',
                                2 => 'warning',
                                3 => 'gray',
                                4 => 'info',
                                5 => 'success',
                                default => 'gray',
                            }
                    )
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'keterangan'
                )
                    ->label('Keterangan')
                    ->limit(100)
                    ->wrap()
                    ->searchable()
                    ->tooltip(
                        fn (
                            CriteriaRating $record
                        ): string =>
                            $record->keterangan
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make(
                    'atribut'
                )
                    ->label('Atribut')
                    ->options([
                        'BENEFIT' => 'BENEFIT',
                        'COST' => 'COST',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make(
                    'skor'
                )
                    ->label('Skor')
                    ->options([
                        5 => 'Skor 5',
                        4 => 'Skor 4',
                        3 => 'Skor 3',
                        2 => 'Skor 2',
                        1 => 'Skor 1',
                    ])
                    ->native(false),
            ])
            ->filtersLayout(
                Tables\Enums\FiltersLayout::Dropdown
            )
            ->filtersTriggerAction(
                fn (
                    Tables\Actions\Action $action
                ) => $action
                    ->button()
                    ->label('Filter')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
            )
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit Kriteria')
                    ->icon(
                        'heroicon-m-pencil-square'
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus Kriteria')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(
                            'Hapus Data Terpilih'
                        ),
                ]),
            ])
            ->paginated([
                10,
                25,
                50,
            ])
            ->striped()
            ->emptyStateHeading(
                'Belum ada data kriteria'
            )
            ->emptyStateDescription(
                'Tambahkan kategori dan skor untuk setiap jenis kriteria.'
            )
            ->emptyStateIcon(
                'heroicon-o-adjustments-horizontal'
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListCriteriaRatings::route('/'),

            'create' =>
                Pages\CreateCriteriaRating::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditCriteriaRating::route(
                    '/{record}/edit'
                ),
        ];
    }
}