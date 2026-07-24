<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\PurchaseHistory;
use App\Filament\Pages\PerhitunganSupplier;
use Carbon\Carbon;

class SupplierFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
    }

    public function test_filter_kategori_only()
    {
        // Buat 27 supplier packaging material
        Supplier::factory()->count(27)->create([
            'kategori' => 'Packaging Material',
            'status_kerja_sama' => 'Aktif',
        ]);
        
        // Buat 5 supplier raw material (sebagai noise)
        Supplier::factory()->count(5)->create([
            'kategori' => 'Raw Material',
            'status_kerja_sama' => 'Aktif',
        ]);

        Livewire::test(PerhitunganSupplier::class)
            ->set('filterData.supplier_category', 'Packaging Material')
            ->set('filterData.period_type', 'monthly')
            ->set('filterData.period_start', '2025-01-01')
            ->set('filterData.period_end', '2025-12-31')
            ->call('tampilkanDataSupplier')
            ->assertCount('candidates', 27);
    }

    public function test_filter_kategori_dan_kelompok_produk()
    {
        $group = ProductGroup::factory()->create(['nama_kelompok_produk' => 'Toples / Jar Plastik', 'kategori_produk' => 'Packaging Material']);
        $product = Product::factory()->create(['product_group_id' => $group->id]);

        // 7 supplier terhubung ke kelompok produk
        $suppliers = Supplier::factory()->count(7)->create([
            'kategori' => 'Packaging Material',
            'status_kerja_sama' => 'Aktif',
        ]);
        
        foreach ($suppliers as $s) {
            $s->products()->attach($product->id);
        }

        // 5 supplier tidak terhubung
        Supplier::factory()->count(5)->create([
            'kategori' => 'Packaging Material',
            'status_kerja_sama' => 'Aktif',
        ]);

        Livewire::test(PerhitunganSupplier::class)
            ->set('filterData.supplier_category', 'Packaging Material')
            ->set('filterData.product_group_id', $group->id)
            ->set('filterData.period_type', 'monthly')
            ->set('filterData.period_start', '2025-01-01')
            ->set('filterData.period_end', '2025-12-31')
            ->call('tampilkanDataSupplier')
            ->assertCount('candidates', 7);
    }

    public function test_supplier_dengan_beberapa_produk_toples_tetap_tampil_satu_kali()
    {
        $group = ProductGroup::factory()->create(['nama_kelompok_produk' => 'Toples / Jar Plastik', 'kategori_produk' => 'Packaging Material']);
        $product1 = Product::factory()->create(['product_group_id' => $group->id]);
        $product2 = Product::factory()->create(['product_group_id' => $group->id]);

        $supplier = Supplier::factory()->create([
            'kategori' => 'Packaging Material',
            'status_kerja_sama' => 'Aktif',
        ]);
        
        // Relasikan ke 2 produk di kelompok yang sama
        $supplier->products()->attach([$product1->id, $product2->id]);

        Livewire::test(PerhitunganSupplier::class)
            ->set('filterData.supplier_category', 'Packaging Material')
            ->set('filterData.product_group_id', $group->id)
            ->set('filterData.period_type', 'monthly')
            ->set('filterData.period_start', '2025-01-01')
            ->set('filterData.period_end', '2025-12-31')
            ->call('tampilkanDataSupplier')
            ->assertCount('candidates', 1);
    }

    public function test_filter_produk_detail_hanya_menampilkan_supplier_produk_tersebut()
    {
        $group = ProductGroup::factory()->create(['nama_kelompok_produk' => 'Toples / Jar Plastik', 'kategori_produk' => 'Packaging Material']);
        $product1 = Product::factory()->create(['product_group_id' => $group->id]);
        $product2 = Product::factory()->create(['product_group_id' => $group->id]);

        // Supplier 1 hanya punya Product 1
        $supplier1 = Supplier::factory()->create(['kategori' => 'Packaging Material', 'status_kerja_sama' => 'Aktif']);
        $supplier1->products()->attach($product1->id);

        // Supplier 2 hanya punya Product 2
        $supplier2 = Supplier::factory()->create(['kategori' => 'Packaging Material', 'status_kerja_sama' => 'Aktif']);
        $supplier2->products()->attach($product2->id);

        Livewire::test(PerhitunganSupplier::class)
            ->set('filterData.supplier_category', 'Packaging Material')
            ->set('filterData.product_group_id', $group->id)
            ->set('filterData.product_id', $product1->id)
            ->set('filterData.period_type', 'monthly')
            ->set('filterData.period_start', '2025-01-01')
            ->set('filterData.period_end', '2025-12-31')
            ->call('tampilkanDataSupplier')
            ->assertCount('candidates', 1);
    }
}
