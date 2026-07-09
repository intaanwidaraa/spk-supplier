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
        Schema::create('suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('kode_supplier')->unique(); // Untuk S-001, S-002
        $table->string('nama_supplier'); // PT. Sumber Baja Perkasa
        $table->string('kategori'); // Raw Material, Packaging
        $table->text('alamat'); 
        $table->string('kontak'); // Bpk. Budi (0812-...)
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
