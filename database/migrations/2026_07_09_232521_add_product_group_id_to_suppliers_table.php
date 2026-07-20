<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unsignedBigInteger('product_group_id')->nullable()->after('kategori');
            
            $table->foreign('product_group_id', 'supplier_product_group_fk')
                ->references('id')
                ->on('product_groups')
                ->nullOnDelete();

            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign('supplier_product_group_fk');
            $table->dropColumn('product_group_id');
        });
    }
};
