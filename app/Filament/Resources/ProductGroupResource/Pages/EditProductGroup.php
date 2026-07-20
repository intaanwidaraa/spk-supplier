<?php

namespace App\Filament\Resources\ProductGroupResource\Pages;

use App\Filament\Resources\ProductGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductGroup extends EditRecord
{
    protected static string $resource = ProductGroupResource::class;

    public function getTitle(): string
    {
        return 'Edit Kelompok Produk: ' . ($this->record->nama_kelompok_produk ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
