<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Kelola Produk';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?string $recordTitleAttribute = 'nama_produk';

    protected static ?int $navigationSort = 2;

    /**
     * Navigation dihandle oleh ProductGroupResource.
     * Resource ini masih aktif untuk akses langsung ke produk detail.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->description('Masukkan detail produk yang tersedia.')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\TextInput::make('kode_produk')
                            ->label('Kode Produk')
                            ->default(function (): string {
                                $latestProduct = Product::query()
                                    ->latest('id')
                                    ->first();

                                if (! $latestProduct) {
                                    return 'P-001';
                                }

                                $lastNumber = (int) substr(
                                    $latestProduct->kode_produk,
                                    2
                                );

                                return 'P-' . str_pad(
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
                                table: Product::class,
                                column: 'kode_produk',
                                ignoreRecord: true
                            ),

                        Forms\Components\TextInput::make('nama_produk')
                            ->label('Nama Produk')
                            ->placeholder('Contoh: Resin, Pigment')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('kategori_produk')
                            ->label('Kategori Produk')
                            ->options([
                                'Raw Material' => 'Raw Material',
                                'Packaging Material' => 'Packaging Material',
                            ])
                            ->native(false)
                            ->required(),

                        Forms\Components\Select::make('satuan')
                            ->label('Satuan')
                            ->options([
                                'Kg' => 'Kg',
                                'Pcs' => 'Pcs',
                                'Pack' => 'Pack',
                                'Roll' => 'Roll',
                                'Liter' => 'Liter',
                                'Ton' => 'Ton',
                                'Box' => 'Box',
                            ])
                            ->native(false)
                            ->searchable()
                            ->required(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Keterangan opsional produk')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('status')
                            ->label('Status Aktif')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_produk')
                    ->label('Kode Produk')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_produk')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('kategori_produk')
                    ->label('Kategori Produk')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Material' => 'warning',
                        'Packaging Material' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('satuan')
                    ->label('Satuan')
                    ->searchable(),

                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_produk')
                    ->label('Kategori Produk')
                    ->placeholder('Semua Kategori')
                    ->options([
                        'Raw Material' => 'Raw Material',
                        'Packaging Material' => 'Packaging Material',
                    ])
                    ->native(false),

                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->native(false),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::Dropdown)
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
                    ->tooltip('Edit Produk')
                    ->icon('heroicon-m-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus Produk')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada data produk')
            ->emptyStateDescription('Tambahkan produk baru untuk dikelola.')
            ->emptyStateIcon('heroicon-o-cube');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\ProductResource\Widgets\ProductStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
