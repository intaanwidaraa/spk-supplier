<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            $table->string('calculation_key')->nullable()->after('atribut');
            
            if (Schema::hasColumn('criteria', 'deskripsi_singkat')) {
                $table->renameColumn('deskripsi_singkat', 'short_description');
            } else {
                $table->text('short_description')->nullable()->after('calculation_key');
            }

            if (Schema::hasColumn('criteria', 'bobot_default')) {
                $table->dropColumn('bobot_default');
            }
        });
    }

    public function down(): void
    {
        Schema::table('criteria', function (Blueprint $table) {
            if (Schema::hasColumn('criteria', 'short_description')) {
                $table->renameColumn('short_description', 'deskripsi_singkat');
            }
            $table->dropColumn('calculation_key');
            $table->decimal('bobot_default', 5, 2)->default(0)->after('atribut');
        });
    }
};
