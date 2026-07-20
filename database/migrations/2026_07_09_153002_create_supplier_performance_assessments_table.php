<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_performance_assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluation_period_id')
                ->constrained('evaluation_periods')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            $table->string('product_category', 50);
            $table->date('assessment_date')->nullable();

            // Skor per kriteria (1-5)
            $table->tinyInteger('c1_score')->nullable();
            $table->tinyInteger('c2_score')->nullable();
            $table->tinyInteger('c3_score')->nullable();
            $table->tinyInteger('c4_score')->nullable();
            $table->tinyInteger('c5_score')->nullable();

            $table->decimal('total_score', 8, 4)->nullable();

            $table->enum('status', ['Draft', 'Final'])->default('Draft');
            $table->boolean('is_auto_calculated')->default(false);
            $table->timestamp('calculated_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Satu supplier tidak boleh punya duplikasi penilaian untuk produk + periode yang sama
            $table->unique(
                ['evaluation_period_id', 'product_id', 'supplier_id'],
                'assessment_period_product_supplier_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_assessments');
    }
};
