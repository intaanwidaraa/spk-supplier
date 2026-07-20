<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductGroupResource\Pages;
use App\Filament\Resources\ProductGroupResource\RelationManagers;
use App\Models\ProductGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductGroupResource extends Resource
{
    protected static ?string $model = ProductGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Kelola Produk';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Kelompok Produk';

    protected static ?string $pluralModelLabel = 'Kelola Produk';

    protected static ?string $recordTitleAttribute = 'nama_kelompok_produk';

    protected static ?string $slug = 'kelola-produk';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas Kelompok Produk')
                ->description('Masukkan nama kelompok produk dan kategorinya.')
                ->icon('heroicon-o-cube')
                ->schema([
                    Forms\Components\TextInput::make('kode_kelompok_produk')
                        ->label('Kode Kelompok Produk')
                        ->default(fn () => ProductGroup::generateNextCode())
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->unique(ProductGroup::class, 'kode_kelompok_produk', ignoreRecord: true),

                    Forms\Components\TextInput::make('nama_kelompok_produk')
                        ->label('Nama Kelompok Produk')
                        ->placeholder('Contoh: Printed Cup Sealer Film / Lid Seal')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('kategori_produk')
                        ->label('Kategori Produk')
                        ->options([
                            'Raw Material'       => 'Raw Material',
                            'Packaging Material' => 'Packaging Material',
                        ])
                        ->native(false)
                        ->required(),

                    Forms\Components\Select::make('satuan_default')
                        ->label('Satuan Default')
                        ->options([
                            'Kg'   => 'Kg',
                            'Pcs'  => 'Pcs',
                            'Pack' => 'Pack',
                            'Roll' => 'Roll',
                            'Liter'=> 'Liter',
                            'Ton'  => 'Ton',
                            'Box'  => 'Box',
                            'Rim'  => 'Rim',
                            'Set'  => 'Set',
                        ])
                        ->native(false)
                        ->searchable()
                        ->nullable(),

                    Forms\Components\Textarea::make('keterangan')
                        ->label('Keterangan')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('status')
                        ->label('Status Aktif')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_kelompok_produk')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_kelompok_produk')
                    ->label('Nama Kelompok Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('kategori_produk')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Raw Material'       => 'warning',
                        'Packaging Material' => 'success',
                        default              => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Detail Produk')
                    ->counts('products')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('satuan_default')
                    ->label('Satuan Default')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_produk')
                    ->label('Kategori Produk')
                    ->placeholder('Semua Kategori')
                    ->options([
                        'Raw Material'       => 'Raw Material',
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
                    ->tooltip('Edit Kelompok Produk')
                    ->icon('heroicon-m-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus Kelompok Produk')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('kode_kelompok_produk')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Belum ada kelompok produk')
            ->emptyStateDescription('Tambahkan kelompok produk untuk mulai mengelola detail produk.')
            ->emptyStateIcon('heroicon-o-cube');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductGroups::route('/'),
            'create' => Pages\CreateProductGroup::route('/create'),
            'edit'   => Pages\EditProductGroup::route('/{record}/edit'),
        ];
    }
}
