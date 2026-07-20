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
        Schema::dropIfExists('criteria_ratings');

        Schema::table('criteria', function (Blueprint $table) {
            $table->renameColumn('sifat_kriteria', 'atribut');
            $table->text('deskripsi_singkat')->nullable()->after('nama_kriteria');
            $table->float('bobot_default')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            $table->renameColumn('atribut', 'sifat_kriteria');
            $table->dropColumn('deskripsi_singkat');
            $table->float('bobot_default')->nullable(false)->change();
        });
    }
};
