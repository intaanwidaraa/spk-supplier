<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\Widgets\SupplierStatsOverview;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Kelola Data Supplier';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $modelLabel = 'Supplier';
    protected static ?string $pluralModelLabel = 'Supplier';
    protected static ?string $recordTitleAttribute = 'nama_supplier';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas Supplier')
                ->description('Masukkan identitas dan jenis produk yang disediakan supplier.')
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Forms\Components\TextInput::make('kode_supplier')
                        ->label('ID Supplier')
                        ->default(function (): string {
                            $latestSupplier = Supplier::query()->latest('id')->first();
                            if (! $latestSupplier) return 'S-001';
                            $lastNumber = (int) substr($latestSupplier->kode_supplier, 2);
                            return 'S-' . str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
                        })
                        ->readOnly()
                        ->dehydrated()
                        ->required()
                        ->unique(table: Supplier::class, column: 'kode_supplier', ignoreRecord: true),

                    Forms\Components\TextInput::make('nama_supplier')
                        ->label('Nama Supplier')
                        ->placeholder('Contoh: PT Makmur Jaya')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('kategori')
                        ->label('Jenis Supplier')
                        ->options([
                            'Raw Material' => 'Raw Material',
                            'Packaging Material' => 'Packaging Material',
                        ])
                        ->native(false)
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (callable $set) {
                            $set('product_group_id', null);
                            $set('product_details_select', []);
                        })
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('Produk Supplier')
                ->description('Pilih kelompok produk dan produk detail yang disediakan supplier.')
                ->icon('heroicon-o-cube')
                ->schema([
                    Forms\Components\Select::make('product_group_id')
                        ->label('Kelompok Produk')
                        ->options(function (Forms\Get $get) {
                            $kategori = $get('kategori');
                            if (! $kategori) return [];
                            return ProductGroup::where('kategori_produk', $kategori)
                                ->where('status', true)
                                ->pluck('nama_kelompok_produk', 'id');
                        })
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->disabled(fn (Forms\Get $get): bool => blank($get('kategori')))
                        ->afterStateUpdated(fn (callable $set) => $set('product_details_select', []))
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('kode_kelompok_produk')
                                ->label('Kode')
                                ->default(fn () => ProductGroup::generateNextCode())
                                ->readOnly()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('nama_kelompok_produk')
                                ->label('Nama Kelompok')
                                ->required(),
                            Forms\Components\TextInput::make('kategori_produk')
                                ->label('Kategori Produk')
                                ->default(fn (Forms\Get $get) => $get('../../kategori'))
                                ->readOnly()
                                ->dehydrated(),
                            Forms\Components\Select::make('satuan_default')
                                ->label('Satuan Default')
                                ->options([
                                    'Kg' => 'Kg', 'Pcs' => 'Pcs', 'Pack' => 'Pack', 'Roll' => 'Roll', 'Liter' => 'Liter', 'Ton' => 'Ton', 'Box' => 'Box', 'Rim' => 'Rim', 'Set' => 'Set'
                                ])->native(false),
                            Forms\Components\Textarea::make('keterangan'),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $group = ProductGroup::create($data);
                            return $group->id;
                        }),

                    Forms\Components\Select::make('product_details_select')
                        ->label('Produk Detail')
                        ->options(function (Forms\Get $get) {
                            $groupId = $get('product_group_id');
                            if (! $groupId) return [];
                            return \App\Models\Product::where('product_group_id', $groupId)
                                ->where('status', true)
                                ->pluck('nama_produk', 'id');
                        })
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->disabled(fn (Forms\Get $get): bool => blank($get('product_group_id')))
                        ->required()
                        ->dehydrated(false)
                        ->createOptionForm([
                            Forms\Components\TextInput::make('kode_produk')
                                ->label('Kode')
                                ->default(fn () => Product::generateNextCode())
                                ->readOnly()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('nama_produk')
                                ->label('Nama Produk Detail')
                                ->required(),
                            Forms\Components\TextInput::make('kategori_produk')
                                ->label('Kategori Produk')
                                ->default(fn (Forms\Get $get) => $get('../../kategori'))
                                ->readOnly()
                                ->dehydrated(),
                            Forms\Components\Select::make('product_group_id')
                                ->label('Kelompok Produk')
                                ->options(fn () => ProductGroup::pluck('nama_kelompok_produk', 'id'))
                                ->default(fn (Forms\Get $get) => $get('../../product_group_id'))
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\Select::make('satuan')
                                ->label('Satuan')
                                ->options([
                                    'Kg' => 'Kg', 'Pcs' => 'Pcs', 'Pack' => 'Pack', 'Roll' => 'Roll', 'Liter' => 'Liter', 'Ton' => 'Ton', 'Box' => 'Box', 'Rim' => 'Rim', 'Set' => 'Set'
                                ])->native(false),
                            Forms\Components\Textarea::make('keterangan'),
                        ]),
                ])->columns(2),

            Forms\Components\Section::make('Informasi Kerja Sama')
                ->description('Masukkan lama kerja sama dan informasi kontak supplier.')
                ->icon('heroicon-o-clock')
                ->schema([
                    Forms\Components\Select::make('status_kerja_sama')
                        ->label('Status Kerja Sama')
                        ->options([
                            'Aktif'    => 'Aktif',
                            'Nonaktif' => 'Nonaktif',
                        ])
                        ->default('Aktif')
                        ->native(false)
                        ->required(),

                    Forms\Components\TextInput::make('masa_kerja_sama')
                        ->label('Masa Kerja Sama')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('Tahun')
                        ->placeholder('Contoh: 5')
                        ->helperText('Isi jumlah tahun kerja sama dengan supplier. Akan menjadi fallback jika tanggal awal kerja sama tidak diisi.')
                        ->required(),

                    Forms\Components\DatePicker::make('tanggal_awal_kerja_sama')
                        ->label('Tanggal Awal Kerja Sama')
                        ->native(false)
                        ->displayFormat('d M Y')
                        ->helperText('Opsional. Jika diisi, durasi kerja sama akan dihitung otomatis.'),

                    Forms\Components\Select::make('partnership_category')
                        ->label('Kategori Kemitraan')
                        ->options([
                            'Strategic'     => 'Strategic',
                            'Transactional' => 'Transactional',
                        ])
                        ->native(false)
                        ->helperText('Opsional. Mempengaruhi perhitungan skor C3.'),

                    Forms\Components\TextInput::make('kontak')
                        ->label('Kontak Supplier')
                        ->placeholder('Contoh: Bpk. Budi (0812-3456-7890)')
                        ->tel()
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('alamat')
                        ->label('Alamat Lengkap')
                        ->placeholder('Masukkan alamat lengkap supplier')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_supplier')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_supplier')
                    ->label('Supplier')
                    ->icon('heroicon-m-building-office-2')
                    ->description(fn (Supplier $record): string => $record->kontak ? 'Kontak: ' . $record->kontak : 'Kontak belum diisi')
                    ->searchable(['nama_supplier', 'kontak'])
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Jenis Supplier')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Raw Material' => 'warning',
                        'Packaging Material' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('kelompok_produk')
                    ->label('Kelompok Produk')
                    ->getStateUsing(function (Supplier $record): array {
                        return $record->products
                            ->pluck('productGroup.nama_kelompok_produk')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();
                    })
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('Belum diisi')
                    ->wrap(),

                Tables\Columns\TextColumn::make('produk_detail')
                    ->label('Produk Detail')
                    ->getStateUsing(function (Supplier $record): array {
                        return $record->products
                            ->pluck('nama_produk')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();
                    })
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('Belum diisi')
                    ->wrap(),

                Tables\Columns\TextColumn::make('masa_kerja_sama')
                    ->label('Masa Kerja Sama')
                    ->icon('heroicon-m-clock')
                    ->formatStateUsing(fn ($state): string => filled($state) ? $state . ' tahun' : 'Belum diisi')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 10 => 'success',
                        $state >= 5  => 'info',
                        $state >= 1  => 'warning',
                        default      => 'danger',
                    })
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Jenis Supplier')
                    ->options([
                        'Raw Material' => 'Raw Material',
                        'Packaging Material' => 'Packaging Material',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit Supplier')
                    ->icon('heroicon-m-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus Supplier')
                    ->icon('heroicon-m-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('kode_supplier')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Belum ada data supplier')
            ->emptyStateDescription('Tambahkan data supplier untuk memulai.')
            ->emptyStateIcon('heroicon-o-building-storefront');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'products.productGroup',
            ]);
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
            'index'  => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit'   => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}