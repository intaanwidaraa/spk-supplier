<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected array $selectedProductIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Because product_details_select is dehydrated(false), it might not be in $data.
        // We fetch it from the Livewire component state ($this->data) instead.
        $this->selectedProductIds = $this->data['product_details_select'] ?? $data['product_details_select'] ?? [];
        unset($data['product_details_select']);

        if (!empty($this->selectedProductIds)) {
            $data['product_id'] = $this->selectedProductIds[0];
        } else {
            $data['product_id'] = null;
        }

        // Auto fill jenis_produk
        if (count($this->selectedProductIds) === 1) {
            $product = \App\Models\Product::find($this->selectedProductIds[0]);
            $data['jenis_produk'] = $product?->nama_produk;
        } elseif (count($this->selectedProductIds) > 1) {
            $group = \App\Models\ProductGroup::find($data['product_group_id'] ?? null);
            $data['jenis_produk'] = $group?->nama_kelompok_produk ?? 'Multiple Products';
        } else {
            $group = \App\Models\ProductGroup::find($data['product_group_id'] ?? null);
            $data['jenis_produk'] = $group?->nama_kelompok_produk;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->products()->sync($this->selectedProductIds);
    }
}
