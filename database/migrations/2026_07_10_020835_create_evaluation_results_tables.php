<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_period_id');
            $table->string('supplier_category');
            $table->unsignedBigInteger('product_group_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->timestamp('calculated_at')->useCurrent();
            $table->string('status')->default('Selesai');
            $table->timestamps();

            // Foreign keys
            // $table->foreign('evaluation_period_id')->references('id')->on('evaluation_periods')->onDelete('cascade');
        });

        Schema::create('reca_weights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_result_id');
            $table->string('criteria_code');
            $table->string('criteria_name')->nullable();
            $table->decimal('weight', 10, 4);
            $table->timestamps();

            $table->foreign('evaluation_result_id')->references('id')->on('evaluation_results')->onDelete('cascade');
        });

        Schema::create('maut_rankings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_result_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('supplier_name');
            $table->decimal('final_score', 10, 4);
            $table->integer('rank');
            $table->json('normalized_scores')->nullable();
            $table->json('weighted_scores')->nullable();
            $table->timestamps();

            $table->foreign('evaluation_result_id')->references('id')->on('evaluation_results')->onDelete('cascade');
            // $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maut_rankings');
        Schema::dropIfExists('reca_weights');
        Schema::dropIfExists('evaluation_results');
    }
};
