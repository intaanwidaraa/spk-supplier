<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1: Add product_group_id FK to products table.
     * Phase 2: Migrate old product data (kelompok produk) to product_groups.
     * Phase 3: Null out suppliers.product_id since old products are now group-level.
     */
    public function up(): void
    {
        // Step 1: Add product_group_id column to products (nullable for now)
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_group_id')->nullable()->after('id');
            $table->foreign('product_group_id')->references('id')->on('product_groups')->nullOnDelete();
        });

        // Step 2: Migrate existing products (which are actually group-level) to product_groups
        $existingProducts = DB::table('products')->orderBy('id')->get();

        $groupCodeCounter = 1;
        foreach ($existingProducts as $product) {
            $groupCode = 'KP-' . str_pad($groupCodeCounter, 3, '0', STR_PAD_LEFT);

            DB::table('product_groups')->insertOrIgnore([
                'kode_kelompok_produk' => $groupCode,
                'nama_kelompok_produk' => $product->nama_produk,
                'kategori_produk'      => $product->kategori_produk,
                'satuan_default'       => $product->satuan ?? null,
                'keterangan'           => $product->keterangan ?? null,
                'status'               => $product->status,
                'created_at'           => $product->created_at,
                'updated_at'           => $product->updated_at,
            ]);

            $groupCodeCounter++;
        }

        // Step 3: Null out suppliers.product_id (old relation is now deprecated)
        // The new many-to-many relation will use supplier_products table
        DB::table('suppliers')->update(['product_id' => null]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_group_id']);
            $table->dropColumn('product_group_id');
        });

        // Note: Rollback cannot restore migrated data. Manual restore needed.
        DB::table('product_groups')->truncate();
    }
};
