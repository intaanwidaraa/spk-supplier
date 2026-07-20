<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_performance_assessments', function (Blueprint $table) {
            // Add product_group_id — can be null if assessment is at detail-product level
            $table->unsignedBigInteger('product_group_id')->nullable()->after('evaluation_period_id');
            $table->foreign('product_group_id', 'spa_product_group_fk')
                ->references('id')
                ->on('product_groups')
                ->nullOnDelete();

            // Make product_id nullable (assessment may be at group level only)
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_performance_assessments', function (Blueprint $table) {
            $table->dropForeign('spa_product_group_fk');
            $table->dropColumn('product_group_id');
        });
    }
};
