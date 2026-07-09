<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('jenis_produk')
                ->nullable()
                ->after('kategori');

            $table->unsignedSmallInteger('masa_kerja_sama')
                ->nullable()
                ->after('jenis_produk');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_produk',
                'masa_kerja_sama',
            ]);
        });
    }
};