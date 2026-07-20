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

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Kelola Kriteria';
    
    protected static ?string $slug = 'kelola-kriteria';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Kriteria';

    protected static ?string $pluralModelLabel = 'Kriteria';

    protected static ?string $recordTitleAttribute = 'nama_kriteria';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Kriteria')
                    ->description('Kelola detail kriteria penilaian supplier.')
                    ->schema([
                        Forms\Components\TextInput::make('kode_kriteria')
                            ->label('Kode Kriteria')
                            ->default(fn (): string => Criteria::generateNextCode())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(table: Criteria::class, column: 'kode_kriteria', ignoreRecord: true),

                        Forms\Components\TextInput::make('nama_kriteria')
                            ->label('Jenis Kriteria / Nama Kriteria')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('atribut')
                            ->label('Atribut')
                            ->options([
                                'BENEFIT' => 'BENEFIT',
                                'COST' => 'COST',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('calculation_key')
                            ->label('Calculation Key')
                            ->maxLength(255)
                            ->helperText('Contoh: repeat_product_rate, partnership_duration'),
                            
                        Forms\Components\Textarea::make('short_description')
                            ->label('Deskripsi Singkat')
                            ->columnSpanFull()
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('Acuan Penilaian Skor')
                    ->description('Setiap kriteria memiliki skor 1 sampai 5 beserta parameternya.')
                    ->schema([
                        Forms\Components\Repeater::make('scoreGuidelines')
                            ->relationship('scoreGuidelines')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('score')
                                    ->label('Skor')
                                    ->numeric()
                                    ->required()
                                    ->readOnly(),
                                Forms\Components\TextInput::make('subcriteria')
                                    ->label('Subkriteria')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('quantitative_parameter')
                                    ->label('Parameter Kuantitatif')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('formula_text')
                                    ->label('Rumus / Cara Ukur')
                                    ->rows(2),
                                Forms\Components\TextInput::make('source_data')
                                    ->label('Sumber Data')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('min_value')
                                    ->label('Min Value')
                                    ->numeric(),
                                Forms\Components\TextInput::make('max_value')
                                    ->label('Max Value')
                                    ->numeric(),
                                Forms\Components\Select::make('operator')
                                    ->label('Operator')
                                    ->options([
                                        '<' => '<',
                                        '<=' => '<=',
                                        '=' => '=',
                                        '>=' => '>=',
                                        '>' => '>',
                                        'BETWEEN' => 'BETWEEN',
                                    ])
                                    ->native(false),
                            ])
                            ->columns(4)
                            ->minItems(5)
                            ->maxItems(5)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->default([
                                ['score' => 1],
                                ['score' => 2],
                                ['score' => 3],
                                ['score' => 4],
                                ['score' => 5],
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_kriteria')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_kriteria')
                    ->label('Jenis Kriteria')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('atribut')
                    ->label('Atribut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'BENEFIT' => 'success',
                        'COST' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('calculation_key')
                    ->label('Calculation Key')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('skor_badges')
                    ->label('Skor')
                    ->getStateUsing(fn (Criteria $record) => $record->scoreGuidelines->pluck('score')->toArray())
                    ->badge()
                    ->color(fn (string $state): string => match ((string)$state) {
                        '1' => 'danger',
                        '2' => 'warning',
                        '3' => 'gray',
                        '4' => 'info',
                        '5' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('keterangan_summary')
                    ->label('Keterangan Parameter')
                    ->getStateUsing(function (Criteria $record) {
                        return $record->scoreGuidelines->map(function ($guide) {
                            $param = $guide->subcriteria ?? $guide->quantitative_parameter ?? '-';
                            return "<strong>[{$guide->score}]</strong> {$param}";
                        })->implode('<br>');
                    })
                    ->html()
                    ->tooltip(function (Criteria $record) {
                        return $record->scoreGuidelines->map(function ($guide) {
                            $desc = $guide->subcriteria ?? $guide->quantitative_parameter;
                            return "Skor {$guide->score}: {$desc}";
                        })->implode("\n");
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('atribut')
                    ->label('Atribut')
                    ->options([
                        'BENEFIT' => 'BENEFIT',
                        'COST' => 'COST',
                    ])
                    ->native(false),
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('kode_kriteria')
            ->emptyStateHeading('Belum ada data kriteria')
            ->emptyStateDescription('Tambahkan kriteria yang digunakan dalam proses evaluasi supplier.')
            ->emptyStateIcon('heroicon-o-adjustments-horizontal');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCriterias::route('/'),
            'create' => Pages\CreateCriteria::route('/create'),
            'edit' => Pages\EditCriteria::route('/{record}/edit'),
        ];
    }
}