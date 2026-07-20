<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('criterion_score_guidelines');
        Schema::create('criterion_score_guidelines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('criterion_id');
            $table->integer('score');
            $table->string('subcriteria')->nullable();
            $table->string('quantitative_parameter')->nullable();
            $table->text('formula_text')->nullable();
            $table->string('source_data')->nullable();
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->string('operator')->nullable();
            $table->timestamps();

            $table->foreign('criterion_id')
                  ->references('id')
                  ->on('criteria')
                  ->cascadeOnDelete();
                  
            $table->unique(['criterion_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_score_guidelines');
    }
};
