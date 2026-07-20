<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->id();
            $table->string('evaluation_code')->unique(); // EV-2025-RM
            $table->string('name');                      // Evaluasi Supplier Raw Material 2025
            $table->integer('year');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('product_category', ['Raw Material', 'Packaging Material']);
            $table->enum('status', ['Draft', 'Aktif', 'Selesai'])->default('Draft');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_periods');
    }
};
