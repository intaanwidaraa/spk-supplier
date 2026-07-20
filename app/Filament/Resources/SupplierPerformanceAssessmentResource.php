<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierPerformanceAssessmentResource\Pages;
use App\Models\EvaluationPeriod;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Supplier;
use App\Models\SupplierPerformanceAssessment;
use App\Services\PerformanceCalculationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierPerformanceAssessmentResource extends Resource
{
    protected static ?string $model = SupplierPerformanceAssessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Data Penilaian Kinerja Supplier';

    protected static ?string $navigationGroup = 'Proses Evaluasi';

    protected static ?string $modelLabel = 'Penilaian Kinerja';

    protected static ?string $pluralModelLabel = 'Data Penilaian Kinerja Supplier';

    protected static ?string $slug = 'supplier-performance-assessments';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Evaluasi')
                ->description('Pilih periode evaluasi, kategori produk, kelompok produk, dan supplier.')
                ->schema([
                    Forms\Components\Select::make('evaluation_period_id')
                        ->label('Periode Evaluasi')
                        ->options(EvaluationPeriod::orderByDesc('year')->pluck('evaluation_code', 'id'))
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('product_category')
                        ->label('Kategori Produk')
                        ->options([
                            'Raw Material'       => 'Raw Material',
                            'Packaging Material' => 'Packaging Material',
                        ])
                        ->native(false)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set) {
                            $set('product_group_id', null);
                            $set('product_id', null);
                        }),

                    Forms\Components\Select::make('product_group_id')
                        ->label('Kelompok Produk')
                        ->options(function (Get $get) {
                            $cat = $get('product_category');
                            return $cat
                                ? ProductGroup::where('kategori_produk', $cat)->pluck('nama_kelompok_produk', 'id')
                                : ProductGroup::pluck('nama_kelompok_produk', 'id');
                        })
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('product_id', null)),

                    Forms\Components\Select::make('product_id')
                        ->label('Detail Produk (Opsional)')
                        ->options(function (Get $get) {
                            $groupId = $get('product_group_id');
                            return $groupId
                                ? Product::where('product_group_id', $groupId)->pluck('nama_produk', 'id')
                                : [];
                        })
                        ->searchable()
                        ->native(false)
                        ->nullable()
                        ->helperText('Kosongkan untuk menghitung semua produk dalam kelompok.'),

                    Forms\Components\Select::make('supplier_id')
                        ->label('Supplier')
                        ->options(function (Get $get) {
                            $cat = $get('product_category');
                            return $cat
                                ? Supplier::where('kategori', $cat)->pluck('nama_supplier', 'id')
                                : Supplier::pluck('nama_supplier', 'id');
                        })
                        ->searchable()
                        ->native(false)
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('Skor Penilaian')
                ->description('Skor dihitung otomatis. Admin dapat mengedit jika diperlukan.')
                ->schema([
                    Forms\Components\TextInput::make('c1_score')
                        ->label('C1 - Kualitas Produk')
                        ->numeric()->minValue(1)->maxValue(5)->integer(),
                    Forms\Components\TextInput::make('c2_score')
                        ->label('C2 - Harga')
                        ->numeric()->minValue(1)->maxValue(5)->integer(),
                    Forms\Components\TextInput::make('c3_score')
                        ->label('C3 - Masa Kerja Sama')
                        ->numeric()->minValue(1)->maxValue(5)->integer(),
                    Forms\Components\TextInput::make('c4_score')
                        ->label('C4 - Ketepatan Kuantitas')
                        ->numeric()->minValue(1)->maxValue(5)->integer(),
                    Forms\Components\TextInput::make('c5_score')
                        ->label('C5 - Ketepatan Waktu')
                        ->numeric()->minValue(1)->maxValue(5)->integer(),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(['Draft' => 'Draft', 'Final' => 'Final'])
                        ->native(false)
                        ->default('Draft'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan')
                        ->columnSpanFull(),
                ])->columns(5),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Material'       => 'warning',
                        'Packaging Material' => 'success',
                        default              => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('c1_score')
                    ->label('C1')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A')
                    ->color(fn ($state): string => self::scoreColor($state))
                    ->tooltip('C1 - Kualitas Produk'),

                Tables\Columns\TextColumn::make('c2_score')
                    ->label('C2')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A')
                    ->color(fn ($state): string => self::scoreColor($state))
                    ->tooltip('C2 - Harga'),

                Tables\Columns\TextColumn::make('c3_score')
                    ->label('C3')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A')
                    ->color(fn ($state): string => self::scoreColor($state))
                    ->tooltip('C3 - Masa Kerja Sama'),

                Tables\Columns\TextColumn::make('c4_score')
                    ->label('C4')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A')
                    ->color(fn ($state): string => self::scoreColor($state))
                    ->tooltip('C4 - Ketepatan Kuantitas'),

                Tables\Columns\TextColumn::make('c5_score')
                    ->label('C5')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ?? 'N/A')
                    ->color(fn ($state): string => self::scoreColor($state))
                    ->tooltip('C5 - Pengiriman'),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Total')
                    ->numeric(2)
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'info',
                        $state >= 2 => 'warning',
                        default     => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Otomatis' => 'info',
                        'Manual'   => 'warning',
                        'Final'    => 'success',
                        default    => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-m-magnifying-glass')
                    ->color('info')
                    ->modalHeading(fn (SupplierPerformanceAssessment $record): string =>
                        'Detail Perhitungan: ' . $record->supplier?->nama_supplier)
                    ->modalContent(fn (SupplierPerformanceAssessment $record) =>
                        view('filament.assessment-detail', ['assessment' => $record])
                    )
                    ->modalWidth('4xl'),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit Penilaian')
                    ->icon('heroicon-m-pencil-square'),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->visible(fn (SupplierPerformanceAssessment $record): bool =>
                        $record->status !== 'Final'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('refreshPerhitungan')
                    ->label('Refresh Perhitungan')
                    ->icon('heroicon-m-arrow-path')
                    ->color('primary')
                    ->action(function (): void {
                        try {
                            app(\App\Services\SupplierPerformanceCalculationService::class)
                                ->calculateAndSyncAll();

                            \Filament\Notifications\Notification::make()
                                ->title('Data penilaian kinerja supplier berhasil diperbarui.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal menghitung penilaian')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Data Terpilih'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Belum ada data penilaian')
            ->emptyStateDescription('Pilih filter periode evaluasi lalu klik "Refresh Perhitungan".')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSupplierPerformanceAssessments::route('/'),
            'create' => Pages\CreateSupplierPerformanceAssessment::route('/create'),
            'edit'   => Pages\EditSupplierPerformanceAssessment::route('/{record}/edit'),
        ];
    }

    private static function scoreColor(mixed $state): string
    {
        return match ((int) $state) {
            5       => 'success',
            4       => 'info',
            3       => 'gray',
            2       => 'warning',
            1       => 'danger',
            default => 'gray',
        };
    }
}
