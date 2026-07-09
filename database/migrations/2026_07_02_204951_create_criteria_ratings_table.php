<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteria_ratings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('criteria_id')
                ->constrained(table: 'criteria')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('kategori', 100);

            $table->unsignedTinyInteger('skor');

            $table->text('keterangan');

            $table->timestamps();

            /*
             * Satu kriteria tidak boleh mempunyai
             * skor yang sama lebih dari satu kali.
             */
            $table->unique(
                ['criteria_id', 'skor'],
                'criteria_rating_score_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria_ratings');
    }
};