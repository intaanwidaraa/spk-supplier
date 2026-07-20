<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('supplier_performance_score_details');
        Schema::create('supplier_performance_score_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('supplier_performance_assessment_id');
            $table->foreign('supplier_performance_assessment_id', 'spsd_assessment_fk')
                ->references('id')
                ->on('supplier_performance_assessments')
                ->cascadeOnDelete();

            $table->foreignId('criterion_id')
                ->constrained('criteria')
                ->cascadeOnDelete();

            // Nilai data mentah dari histori
            $table->decimal('raw_value', 18, 4)->nullable();
            $table->string('raw_value_label')->nullable(); // e.g. "3 repeat orders"

            // Skor hasil kalkulasi otomatis
            $table->tinyInteger('auto_score')->nullable();

            // Skor akhir (bisa sudah dioverride admin)
            $table->tinyInteger('final_score')->nullable();

            // Kategori skor teks (e.g. "Sangat Baik")
            $table->string('score_category')->nullable();

            // Deskripsi rumus/parameter
            $table->text('calculation_description')->nullable();

            // Override manual
            $table->boolean('is_manual_override')->default(false);
            $table->text('override_reason')->nullable();
            $table->unsignedBigInteger('overridden_by')->nullable(); // user_id
            $table->timestamp('overridden_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['supplier_performance_assessment_id', 'criterion_id'],
                'score_detail_assessment_criterion_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_score_details');
    }
};
