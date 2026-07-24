<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->enum('status_kerja_sama', ['Aktif', 'Nonaktif'])
                ->default('Aktif')
                ->after('kontak');

            $table->date('tanggal_awal_kerja_sama')
                ->nullable()
                ->after('status_kerja_sama');

            $table->enum('partnership_category', ['Strategic', 'Transactional'])
                ->nullable()
                ->after('tanggal_awal_kerja_sama');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'status_kerja_sama',
                'tanggal_awal_kerja_sama',
                'partnership_category',
            ]);
        });
    }
};
