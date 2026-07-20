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
        Schema::create('criterion_score_guidelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->integer('skor');
            $table->string('kategori');
            $table->text('keterangan');
            $table->timestamps();
            
            $table->unique(['criteria_id', 'skor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criterion_score_guidelines');
    }
};
