<?php

namespace App\Filament\Resources\ProductGroupResource\RelationManagers;

use App\Models\Product;
use App\Models\ProductGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Produk Detail';

    protected static ?string $recordTitleAttribute = 'nama_produk';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode_produk')
                ->label('Kode Produk')
                ->default(fn () => Product::generateNextCode())
                ->readOnly()
                ->dehydrated()
                ->unique(Product::class, 'kode_produk', ignoreRecord: true),

            Forms\Components\TextInput::make('nama_produk')
                ->label('Nama Produk Detail')
                ->placeholder('Contoh: SEAL JELLY GUM BUBBLE GUM (NEW)')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('kategori_produk')
                ->label('Kategori Produk')
                ->default(fn (RelationManager $livewire): string =>
                    $livewire->getOwnerRecord()->kategori_produk ?? ''
                )
                ->readOnly()
                ->dehydrated()
                ->columnSpan(1),

            Forms\Components\Select::make('satuan')
                ->label('Satuan')
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
                ->default(fn (RelationManager $livewire): ?string =>
                    $livewire->getOwnerRecord()->satuan_default ?? null
                )
                ->native(false)
                ->searchable()
                ->nullable()
                ->columnSpan(1),

            Forms\Components\Textarea::make('keterangan')
                ->label('Keterangan')
                ->rows(2)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('status')
                ->label('Status Aktif')
                ->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_produk')
            ->columns([
                Tables\Columns\TextColumn::make('kode_produk')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_produk')
                    ->label('Nama Produk Detail')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('satuan')
                    ->label('Satuan')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Produk Detail')
                    ->icon('heroicon-m-plus')
                    ->mutateFormDataUsing(function (array $data, RelationManager $livewire): array {
                        $data['product_group_id'] = $livewire->getOwnerRecord()->id;
                        $data['kategori_produk']  = $livewire->getOwnerRecord()->kategori_produk;
                        if (blank($data['kode_produk'])) {
                            $data['kode_produk'] = Product::generateNextCode();
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Edit')
                    ->icon('heroicon-m-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Hapus')
                    ->icon('heroicon-m-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('kode_produk')
            ->striped()
            ->emptyStateHeading('Belum ada produk detail')
            ->emptyStateDescription('Tambahkan produk detail pada kelompok produk ini.')
            ->emptyStateIcon('heroicon-o-tag');
    }
}
