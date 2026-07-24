<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseHistoryResource\Pages;
use App\Models\PurchaseHistory;
use App\Models\Supplier;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseHistoryResource extends Resource
{
    protected static ?string $model =
        PurchaseHistory::class;

    protected static ?string $navigationIcon =
        'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel =
        'Data Historis Pembelian';

    protected static ?string $navigationGroup =
    'Analisis dan Perhitungan';

    protected static ?string $modelLabel =
        'Data Historis Pembelian';

    protected static ?string $pluralModelLabel =
        'Data Historis Pembelian';

    protected static ?string $recordTitleAttribute =
        'nama_produk';

    protected static ?string $slug =
        'data-historis-pembelian';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                /*
                 * BAGIAN 1 — TRANSAKSI
                 */
                Forms\Components\Section::make(
                    'Informasi Transaksi'
                )
                    ->description(
                        'Masukkan informasi pembelian dan supplier.'
                    )
                    ->icon(
                        'heroicon-o-document-text'
                    )
                    ->schema([
                        Forms\Components\TextInput::make(
                            'nomor_po'
                        )
                            ->label('Nomor PO')
                            ->placeholder(
                                'Contoh: POBG-22010072'
                            )
                            ->maxLength(100),

                        Forms\Components\DatePicker::make(
                            'tanggal_pembelian'
                        )
                            ->label('Tanggal Pembelian')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make(
                            'supplier_id'
                        )
                            ->label('Supplier')
                            ->relationship(
                                name: 'supplier',
                                titleAttribute: 'nama_supplier'
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Supplier $record): string =>
                                    $record->kode_supplier
                                    . ' - '
                                    . $record->nama_supplier
                            )
                            ->searchable([
                                'kode_supplier',
                                'nama_supplier',
                            ])
                            ->preload()
                            ->native(false)
                            ->live()
                            ->required(),

                        Forms\Components\Placeholder::make(
                            'jenis_supplier_tampilan'
                        )
                            ->label('Jenis Supplier')
                            ->content(
                                fn (Get $get): string =>
                                    Supplier::query()
                                        ->find(
                                            $get('supplier_id')
                                        )
                                        ?->kategori
                                    ?? 'Pilih supplier terlebih dahulu'
                            ),

                        Forms\Components\TextInput::make(
                            'nomor_penerimaan'
                        )
                            ->label('Nomor Penerimaan')
                            ->placeholder(
                                'Contoh: GR01-22010001'
                            )
                            ->maxLength(100),
                    ])
                    ->columns(2),

                /*
                 * BAGIAN 2 — PRODUK DAN PEMBELIAN
                 */
                Forms\Components\Section::make(
                    'Informasi Produk dan Pembelian'
                )
                    ->description(
                        'Masukkan produk, kuantitas, dan harga pembelian.'
                    )
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\TextInput::make(
                            'kode_produk'
                        )
                            ->label('Kode Produk')
                            ->placeholder(
                                'Contoh: P-06-045'
                            )
                            ->maxLength(100),

                        Forms\Components\TextInput::make(
                            'nama_produk'
                        )
                            ->label('Nama Produk')
                            ->placeholder(
                                'Contoh: Duplek Donald Jelly Gum'
                            )
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make(
                            'satuan'
                        )
                            ->label('Satuan')
                            ->options([
                                'Kg' => 'Kg',
                                'Gram' => 'Gram',
                                'Pcs' => 'Pcs',
                                'Roll' => 'Roll',
                                'Dus' => 'Dus',
                                'Ball' => 'Ball',
                                'Pack' => 'Pack',
                                'Liter' => 'Liter',
                                'Meter' => 'Meter',
                                'Unit' => 'Unit',
                            ])
                            ->searchable()
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make(
                            'qty_pembelian'
                        )
                            ->label('Qty Pembelian')
                            ->numeric()
                            ->minValue(0.0001)
                            ->step(0.0001)
                            ->live(onBlur: true)
                            ->required(),

                        Forms\Components\TextInput::make(
                            'harga_satuan'
                        )
                            ->label('Harga Satuan')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->step(0.0001)
                            ->live(onBlur: true)
                            ->required(),

                        Forms\Components\Placeholder::make(
                            'total_pembelian_tampilan'
                        )
                            ->label('Total Pembelian')
                            ->content(
                                fn (Get $get): string =>
                                    self::formatRupiah(
                                        (float) (
                                            $get('qty_pembelian')
                                            ?? 0
                                        )
                                        *
                                        (float) (
                                            $get('harga_satuan')
                                            ?? 0
                                        )
                                    )
                            ),
                    ])
                    ->columns(2),

                /*
                 * BAGIAN 3 — PENERIMAAN
                 */
                Forms\Components\Section::make(
                    'Informasi Penerimaan'
                )
                    ->description(
                        'Masukkan tanggal dan kuantitas barang yang diterima.'
                    )
                    ->icon(
                        'heroicon-o-inbox-arrow-down'
                    )
                    ->schema([
                        Forms\Components\DatePicker::make(
                            'estimasi_tanggal_penerimaan'
                        )
                            ->label(
                                'Estimasi Tanggal Penerimaan'
                            )
                            ->native(false)
                            ->displayFormat('d M Y'),

                        Forms\Components\DatePicker::make(
                            'tanggal_penerimaan'
                        )
                            ->label(
                                'Tanggal Penerimaan Aktual'
                            )
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->live(),

                        Forms\Components\TextInput::make(
                            'qty_diterima'
                        )
                            ->label('Qty Diterima')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.0001)
                            ->live(onBlur: true)
                            ->required(),

                        Forms\Components\Placeholder::make(
                            'outstanding_tampilan'
                        )
                            ->label(
                                'Selisih / Outstanding'
                            )
                            ->content(
                                fn (Get $get): string =>
                                    self::formatQuantity(
                                        (float) (
                                            $get('qty_pembelian')
                                            ?? 0
                                        )
                                        -
                                        (float) (
                                            $get('qty_diterima')
                                            ?? 0
                                        )
                                    )
                            ),

                        Forms\Components\Placeholder::make(
                            'fulfillment_tampilan'
                        )
                            ->label('Fulfillment Rate')
                            ->content(
                                function (Get $get): string {
                                    $qtyPembelian =
                                        (float) (
                                            $get(
                                                'qty_pembelian'
                                            )
                                            ?? 0
                                        );

                                    $qtyDiterima =
                                        (float) (
                                            $get(
                                                'qty_diterima'
                                            )
                                            ?? 0
                                        );

                                    if ($qtyPembelian <= 0) {
                                        return '0%';
                                    }

                                    return number_format(
                                        (
                                            $qtyDiterima
                                            /
                                            $qtyPembelian
                                        ) * 100,
                                        2,
                                        ',',
                                        '.'
                                    ) . '%';
                                }
                            ),

                        Forms\Components\Placeholder::make(
                            'lead_time_tampilan'
                        )
                            ->label('Lead Time')
                            ->content(
                                function (Get $get): string {
                                    $tanggalPembelian =
                                        $get(
                                            'tanggal_pembelian'
                                        );

                                    $tanggalPenerimaan =
                                        $get(
                                            'tanggal_penerimaan'
                                        );

                                    if (
                                        ! $tanggalPembelian
                                        ||
                                        ! $tanggalPenerimaan
                                    ) {
                                        return 'Belum dapat dihitung';
                                    }

                                    $leadTime = Carbon::parse(
                                        $tanggalPembelian
                                    )->diffInDays(
                                        Carbon::parse(
                                            $tanggalPenerimaan
                                        ),
                                        false
                                    );

                                    return $leadTime . ' hari';
                                }
                            ),

                        Forms\Components\Placeholder::make(
                            'status_tampilan'
                        )
                            ->label('Status Penerimaan')
                            ->content(
                                fn (Get $get): string =>
                                    self::calculateStatus(
                                        (float) (
                                            $get(
                                                'qty_pembelian'
                                            )
                                            ?? 0
                                        ),
                                        (float) (
                                            $get(
                                                'qty_diterima'
                                            )
                                            ?? 0
                                        )
                                    )
                            ),

                        Forms\Components\Textarea::make(
                            'catatan'
                        )
                            ->label('Catatan Penerimaan')
                            ->placeholder(
                                'Masukkan catatan apabila terdapat kekurangan, kelebihan, atau kendala penerimaan.'
                            )
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_pembelian')
                    ->label('Tanggal PO')
                    ->date('d M Y')
                    ->sortable()
                    ->width('100px'),

                Tables\Columns\TextColumn::make('nomor_po')
                    ->label('Nomor PO')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->description(fn (PurchaseHistory $record): string => $record->jenis_supplier ?? '')
                    ->icon('heroicon-m-building-office-2')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->lineClamp(2),

                Tables\Columns\TextColumn::make('nama_produk')
                    ->label('Produk')
                    ->description(fn (PurchaseHistory $record): string => collect([$record->kode_produk, $record->satuan])->filter()->implode(' • '))
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->lineClamp(3)
                    ->tooltip(fn (PurchaseHistory $record): string => $record->nama_produk),

                Tables\Columns\TextColumn::make('qty_pembelian')
                    ->label('Qty Pembelian')
                    ->formatStateUsing(fn ($state, PurchaseHistory $record): string => self::formatQuantity($state) . ' ' . $record->satuan)
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('harga_satuan')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state): string => self::formatRupiah($state))
                    ->description(fn (PurchaseHistory $record): string => 'Total: ' . self::formatRupiah($record->total_pembelian))
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty_diterima')
                    ->label('Penerimaan')
                    ->formatStateUsing(fn ($state, PurchaseHistory $record): string => self::formatQuantity($state) . ' ' . $record->satuan)
                    ->description(fn (PurchaseHistory $record): string => $record->tanggal_penerimaan ? $record->tanggal_penerimaan->format('d M Y') : 'Belum diterima')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fulfillment_rate')
                    ->label('Pemenuhan')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.') . '%')
                    ->badge()
                    ->color(fn (string $state): string => (float)$state >= 100 ? 'success' : ((float)$state > 0 ? 'warning' : 'gray'))
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lead_time_hari')
                    ->label('Lead Time')
                    ->formatStateUsing(fn ($state): string => filled($state) ? $state . ' hr' : '-')
                    ->badge()
                    ->color('gray')
                    ->size('sm')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_penerimaan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Diterima Lengkap' => 'success',
                        'Diterima Sebagian' => 'warning',
                        'Kelebihan Penerimaan' => 'info',
                        'Belum Diterima' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'nomor_penerimaan'
                )
                    ->label('Nomor Penerimaan')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make(
                    'outstanding'
                )
                    ->label('Outstanding')
                    ->formatStateUsing(
                        fn (
                            $state,
                            PurchaseHistory $record
                        ): string =>
                            self::formatQuantity(
                                $state
                            )
                            . ' '
                            . $record->satuan
                    )
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make(
                    'estimasi_tanggal_penerimaan'
                )
                    ->label('Estimasi Penerimaan')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make(
                    'supplier_id'
                )
                    ->label('Supplier')
                    ->relationship(
                        'supplier',
                        'nama_supplier'
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make(
                    'jenis_supplier'
                )
                    ->label('Jenis Supplier')
                    ->options([
                        'Raw Material' =>
                            'Raw Material',

                        'Packaging Material' =>
                            'Packaging Material',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make(
                    'status_penerimaan'
                )
                    ->label('Status Penerimaan')
                    ->options([
                        'Belum Diterima' =>
                            'Belum Diterima',

                        'Diterima Sebagian' =>
                            'Diterima Sebagian',

                        'Diterima Lengkap' =>
                            'Diterima Lengkap',

                        'Kelebihan Penerimaan' =>
                            'Kelebihan Penerimaan',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make(
                    'periode_pembelian'
                )
                    ->label('Periode Pembelian')
                    ->form([
                        Forms\Components\DatePicker::make(
                            'tanggal_mulai'
                        )
                            ->label('Tanggal Mulai')
                            ->native(false),

                        Forms\Components\DatePicker::make(
                            'tanggal_selesai'
                        )
                            ->label('Tanggal Selesai')
                            ->native(false),
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            return $query
                                ->when(
                                    $data[
                                        'tanggal_mulai'
                                    ] ?? null,
                                    fn (
                                        Builder $query,
                                        $date
                                    ): Builder =>
                                        $query->whereDate(
                                            'tanggal_pembelian',
                                            '>=',
                                            $date
                                        )
                                )
                                ->when(
                                    $data[
                                        'tanggal_selesai'
                                    ] ?? null,
                                    fn (
                                        Builder $query,
                                        $date
                                    ): Builder =>
                                        $query->whereDate(
                                            'tanggal_pembelian',
                                            '<=',
                                            $date
                                        )
                                );
                        }
                    ),
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
                    ->tooltip(
                        'Edit Data Historis'
                    )
                    ->icon(
                        'heroicon-m-pencil-square'
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip(
                        'Hapus Data Historis'
                    )
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
            ->defaultSort(
                'tanggal_pembelian',
                'desc'
            )
            ->paginated([
                10,
                25,
                50,
                100,
            ])
            ->striped()
            ->emptyStateHeading(
                'Belum ada data historis'
            )
            ->emptyStateDescription(
                'Tambahkan transaksi pembelian dan penerimaan supplier.'
            )
            ->emptyStateIcon(
                'heroicon-o-clipboard-document-list'
            );
    }

    private static function calculateStatus(
        float $qtyPembelian,
        float $qtyDiterima
    ): string {
        if ($qtyDiterima <= 0) {
            return 'Belum Diterima';
        }

        if ($qtyDiterima < $qtyPembelian) {
            return 'Diterima Sebagian';
        }

        if ($qtyDiterima > $qtyPembelian) {
            return 'Kelebihan Penerimaan';
        }

        return 'Diterima Lengkap';
    }

    private static function formatRupiah(
        mixed $value
    ): string {
        return 'Rp'
            . number_format(
                (float) $value,
                0,
                ',',
                '.'
            );
    }

    private static function formatQuantity(
        mixed $value
    ): string {
        return rtrim(
            rtrim(
                number_format(
                    (float) $value,
                    4,
                    ',',
                    '.'
                ),
                '0'
            ),
            ','
        );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\PurchaseHistoryResource\Widgets\PurchaseHistoryStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListPurchaseHistories::route('/'),

            'create' =>
                Pages\CreatePurchaseHistory::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditPurchaseHistory::route(
                    '/{record}/edit'
                ),
        ];
    }
}