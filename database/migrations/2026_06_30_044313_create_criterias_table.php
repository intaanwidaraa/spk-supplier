<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('criteria', function (Blueprint $table) {
        $table->id();
        $table->string('kode_kriteria')->unique(); // Untuk K01, K02
        $table->string('nama_kriteria'); // Harga Penawaran, Kualitas...
        $table->enum('sifat_kriteria', ['COST', 'BENEFIT']); // Cuma bisa diisi dua ini
        $table->float('bobot_default'); // 25, 30, 20...
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};
