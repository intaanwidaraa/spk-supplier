<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_groups', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kelompok_produk')->unique(); // KP-001, KP-002
            $table->string('nama_kelompok_produk');
            $table->enum('kategori_produk', ['Raw Material', 'Packaging Material']);
            $table->string('satuan_default')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index('kategori_produk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_groups');
    }
};
