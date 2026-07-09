<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\Widgets\SupplierStatsOverview;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon =
        'heroicon-o-building-storefront';

    protected static ?string $navigationLabel =
        'Kelola Data Supplier';

    protected static ?string $navigationGroup =
    'Master Data';
    
    protected static ?string $modelLabel =
        'Supplier';

    protected static ?string $pluralModelLabel =
        'Supplier';

    protected static ?string $recordTitleAttribute =
        'nama_supplier';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(
                    'Identitas Supplier'
                )
                    ->description(
                        'Masukkan identitas dan jenis produk yang disediakan supplier.'
                    )
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\TextInput::make(
                            'kode_supplier'
                        )
                            ->label('ID Supplier')
                            ->default(function (): string {
                                $latestSupplier = Supplier::query()
                                    ->latest('id')
                                    ->first();

                                if (! $latestSupplier) {
                                    return 'S-001';
                                }

                                $lastNumber = (int) substr(
                                    $latestSupplier->kode_supplier,
                                    2
                                );

                                return 'S-' . str_pad(
                                    (string) ($lastNumber + 1),
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                );
                            })
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(
                                table: Supplier::class,
                                column: 'kode_supplier',
                                ignoreRecord: true
                            ),

                        Forms\Components\TextInput::make(
                            'nama_supplier'
                        )
                            ->label('Nama Supplier')
                            ->placeholder(
                                'Contoh: PT Makmur Jaya'
                            )
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make(
                            'kategori'
                        )
                            ->label('Jenis Supplier')
                            ->options([
                                'Raw Material' =>
                                    'Raw Material',

                                'Packaging Material' =>
                                    'Packaging Material',
                            ])
                            ->native(false)
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make(
                            'jenis_produk'
                        )
                            ->label('Jenis Produk')
                            ->placeholder(
                                'Contoh: Resin, Pigment, Carton Box'
                            )
                            ->helperText(
                                'Isi produk utama yang disediakan oleh supplier.'
                            )
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Kerja Sama')
                    ->description(
                        'Masukkan lama kerja sama dan informasi kontak supplier.'
                    )
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\TextInput::make(
                            'masa_kerja_sama'
                        )
                            ->label('Masa Kerja Sama')
                            ->integer()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('Tahun')
                            ->placeholder('Contoh: 5')
                            ->helperText(
                                'Isi jumlah tahun kerja sama dengan supplier.'
                            )
                            ->required(),

                        Forms\Components\TextInput::make(
                            'kontak'
                        )
                            ->label('Kontak Supplier')
                            ->placeholder(
                                'Contoh: Bpk. Budi (0812-3456-7890)'
                            )
                            ->tel()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make(
                            'alamat'
                        )
                            ->label('Alamat Lengkap')
                            ->placeholder(
                                'Masukkan alamat lengkap supplier'
                            )
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(
                    'kode_supplier'
                )
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'nama_supplier'
                )
                    ->label('Supplier')
                    ->icon(
                        'heroicon-m-building-office-2'
                    )
                    ->description(
                        fn (Supplier $record): string =>
                            $record->kontak
                                ? 'Kontak: ' . $record->kontak
                                : 'Kontak belum diisi'
                    )
                    ->searchable([
                        'nama_supplier',
                        'kontak',
                    ])
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make(
                    'kategori'
                )
                    ->label('Jenis Supplier')
                    ->badge()
                    ->color(
                        fn (?string $state): string =>
                            match ($state) {
                                'Raw Material' => 'warning',

                                'Packaging Material' =>
                                    'success',

                                default => 'gray',
                            }
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'jenis_produk'
                )
                    ->label('Jenis Produk')
                    ->icon('heroicon-m-cube')
                    ->limit(35)
                    ->tooltip(
                        fn (Supplier $record): ?string =>
                            $record->jenis_produk
                    )
                    ->placeholder('Belum diisi')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make(
                    'masa_kerja_sama'
                )
                    ->label('Masa Kerja Sama')
                    ->icon('heroicon-m-clock')
                    ->formatStateUsing(
                        fn ($state): string =>
                            filled($state)
                                ? $state . ' tahun'
                                : 'Belum diisi'
                    )
                    ->badge()
                    ->color(
                        fn ($state): string =>
                            match (true) {
                                blank($state) => 'gray',
                                (int) $state >= 5 => 'success',
                                (int) $state >= 3 => 'info',
                                default => 'warning',
                            }
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->icon('heroicon-m-map-pin')
                    ->placeholder('Belum diisi')
                    ->searchable()
                    ->wrap()
                    ->limit(45)
                    ->tooltip(
                        fn (Supplier $record): ?string => $record->alamat
                    ),

                Tables\Columns\TextColumn::make(
                    'created_at'
                )
                    ->label('Tanggal Ditambahkan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Jenis Supplier')
                    ->placeholder('Semua Jenis')
                    ->options([
                        'Raw Material' => 'Raw Material',
                        'Packaging Material' => 'Packaging Material',
                    ])
                    ->native(false),
            ])
            ->filtersLayout(
                Tables\Enums\FiltersLayout::Dropdown
            )
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
            )
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit Supplier')
                    ->icon(
                        'heroicon-m-pencil-square'
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus Supplier')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Supplier Terpilih'),
                ]),
            ])
            ->defaultSort(
                'nama_supplier',
                'asc'
            )
            ->paginated([
                10,
                25,
                50,
            ])
            ->striped()
            ->emptyStateHeading(
                'Belum ada data supplier'
            )
            ->emptyStateDescription(
                'Tambahkan supplier untuk memulai proses penilaian dan evaluasi.'
            )
            ->emptyStateIcon(
                'heroicon-o-building-storefront'
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [
            SupplierStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListSuppliers::route('/'),

            'create' =>
                Pages\CreateSupplier::route('/create'),

            'edit' =>
                Pages\EditSupplier::route(
                    '/{record}/edit'
                ),
        ];
    }
}