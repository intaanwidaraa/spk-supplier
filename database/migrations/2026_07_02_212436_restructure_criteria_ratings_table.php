<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Hapus foreign key terlebih dahulu sebelum
         * menghapus kolom criteria_id.
         */
        Schema::table('criteria_ratings', function (Blueprint $table) {
            $table->dropForeign(['criteria_id']);
        });

        Schema::table('criteria_ratings', function (Blueprint $table) {
            $table->dropUnique('criteria_rating_score_unique');
            $table->dropColumn('criteria_id');
        });

        Schema::table('criteria_ratings', function (Blueprint $table) {
            $table->string('jenis_kriteria', 150)
                ->after('id');

            $table->enum('atribut', [
                'COST',
                'BENEFIT',
            ])->after('jenis_kriteria');

            /*
             * Satu jenis kriteria hanya boleh memiliki
             * satu data untuk setiap skor.
             */
            $table->unique(
                ['jenis_kriteria', 'skor'],
                'criteria_rating_type_score_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('criteria_ratings', function (Blueprint $table) {
            $table->dropUnique(
                'criteria_rating_type_score_unique'
            );

            $table->dropColumn([
                'jenis_kriteria',
                'atribut',
            ]);
        });

        Schema::table('criteria_ratings', function (Blueprint $table) {
            $table->foreignId('criteria_id')
                ->nullable()
                ->after('id')
                ->constrained(table: 'criteria')
                ->nullOnDelete();

            $table->unique(
                ['criteria_id', 'skor'],
                'criteria_rating_score_unique'
            );
        });
    }
};