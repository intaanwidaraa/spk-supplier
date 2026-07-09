<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('criterion_id')
                ->constrained('criteria')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('nilai')->nullable();

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->unique(
                ['supplier_id', 'criterion_id'],
                'supplier_criterion_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_scores');
    }
};