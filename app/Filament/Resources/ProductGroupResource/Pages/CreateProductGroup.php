<?php

namespace App\Filament\Resources\ProductGroupResource\Pages;

use App\Filament\Resources\ProductGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductGroup extends CreateRecord
{
    protected static string $resource = ProductGroupResource::class;

    public function getTitle(): string
    {
        return 'Tambah Kelompok Produk';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
