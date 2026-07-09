<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierPerformanceResource\Pages;
use App\Models\Criteria;
use App\Models\Supplier;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierPerformanceResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon =
        'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel =
        'Penilaian Kinerja';

    protected static ?string $modelLabel =
        'Penilaian Kinerja';

    protected static ?string $pluralModelLabel =
        'Penilaian Kinerja Supplier';

    protected static ?string $slug =
        'penilaian-kinerja';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        $criteriaOptions = Criteria::query()
            ->orderBy('kode_kriteria')
            ->get()
            ->mapWithKeys(function (Criteria $criteria): array {
                $label = $criteria->kode_kriteria
                    . ' - '
                    . $criteria->nama_kriteria
                    . ' ('
                    . $criteria->sifat_kriteria
                    . ')';

                return [$criteria->id => $label];
            })
            ->toArray();

        return $form
            ->schema([
                Section::make('Data Supplier')
                    ->description(
                        'Supplier yang sedang diberikan nilai kinerja.'
                    )
                    ->schema([
                        TextInput::make('kode_supplier')
                            ->label('Kode Supplier')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('nama_supplier')
                            ->label('Nama Supplier')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('kategori')
                            ->label('Kategori Supplier')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Section::make('Penilaian Kinerja')
                    ->description(
                        'Isi nilai aktif supplier untuk setiap kriteria.'
                    )
                    ->schema([
                        Repeater::make('performanceScores')
                            ->label('')
                            ->relationship('performanceScores')
                            ->schema([
                                Select::make('criterion_id')
                                    ->label('Kriteria')
                                    ->options($criteriaOptions)
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                Radio::make('nilai')
                                    ->label('Nilai Kinerja')
                                    ->options([
                                        1 => '1 - Sangat Buruk',
                                        2 => '2 - Buruk',
                                        3 => '3 - Cukup',
                                        4 => '4 - Baik',
                                        5 => '5 - Sangat Baik',
                                    ])
                                    ->inline()
                                    ->required(),

                                Textarea::make('catatan')
                                    ->label('Catatan')
                                    ->placeholder(
                                        'Catatan tambahan jika diperlukan'
                                    )
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(
                                fn (array $state): ?string =>
                                    $criteriaOptions[
                                        $state['criterion_id'] ?? null
                                    ] ?? 'Kriteria'
                            )
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(1)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $totalCriteria = Criteria::query()->count();

        return $table
            ->columns([
                TextColumn::make('kode_supplier')
                    ->label('Kode Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_supplier')
                    ->label('Nama Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Material' => 'success',
                        'Packaging Material' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('progress_penilaian')
                    ->label('Nilai Terisi')
                    ->getStateUsing(
                        fn (Supplier $record): string =>
                            $record->nilai_terisi_count
                            . ' / '
                            . $totalCriteria
                    )
                    ->badge()
                    ->color(
                        fn (Supplier $record): string =>
                            $record->nilai_terisi_count === $totalCriteria
                                ? 'success'
                                : (
                                    $record->nilai_terisi_count > 0
                                        ? 'warning'
                                        : 'gray'
                                )
                    ),

                TextColumn::make('status_penilaian')
                    ->label('Status')
                    ->getStateUsing(
                        fn (Supplier $record): string => match (true) {
                            $record->nilai_terisi_count === $totalCriteria
                                => 'Lengkap',

                            $record->nilai_terisi_count > 0
                                => 'Belum Lengkap',

                            default => 'Belum Diisi',
                        }
                    )
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Lengkap' => 'success',
                        'Belum Lengkap' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make(
                    'performance_scores_max_updated_at'
                )
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori Supplier')
                    ->options([
                        'Raw Material' => 'Raw Material',
                        'Packaging Material' => 'Packaging Material',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('Isi / Ubah Nilai')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([])
            ->defaultSort('nama_supplier');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'performanceScores as nilai_terisi_count'
                    => fn (Builder $query): Builder =>
                        $query->whereNotNull('nilai'),
            ])
            ->withMax('performanceScores', 'updated_at');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListSupplierPerformances::route('/'),

            'edit' =>
                Pages\EditSupplierPerformance::route(
                    '/{record}/edit'
                ),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}