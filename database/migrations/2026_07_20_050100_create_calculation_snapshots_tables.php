<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Tabel utama perhitungan ───
        Schema::create('calculations', function (Blueprint $table) {
            $table->id();
            $table->string('calculation_code')->unique();      // CALC-RM-2026-001
            $table->string('calculation_name');                 // Evaluasi Supplier Perisa Januari 2026
            $table->string('supplier_category', 50);            // Raw Material / Packaging Material

            $table->unsignedBigInteger('product_group_id')->nullable();
            $table->foreign('product_group_id')
                ->references('id')->on('product_groups')
                ->nullOnDelete();

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->nullOnDelete();

            // Snapshot nama produk agar tidak berubah
            $table->string('product_group_name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_scope')->default('group'); // 'group' atau 'single'

            // Periode
            $table->string('period_type', 20);  // weekly, monthly, yearly, custom
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_label')->nullable(); // "Januari 2026", "2025", dsb

            // Statistik
            $table->unsignedInteger('total_candidates')->default(0);
            $table->unsignedInteger('total_selected')->default(0);

            // Status
            $table->enum('status', ['Draft', 'Selesai', 'Final'])->default('Draft');
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedBigInteger('calculated_by')->nullable();

            // RECA validation
            $table->decimal('total_reca_weight', 10, 8)->nullable();
            $table->boolean('reca_weight_valid')->default(false);

            $table->timestamps();

            $table->index('supplier_category');
            $table->index('status');
            $table->index('calculated_at');
        });

        // ─── Supplier yang menjadi kandidat (dipilih / tidak) ───
        Schema::create('calculation_suppliers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calculation_id');
            $table->foreign('calculation_id')
                ->references('id')->on('calculations')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('supplier_id');
            $table->foreign('supplier_id')
                ->references('id')->on('suppliers')
                ->cascadeOnDelete();

            // Snapshot data supplier saat perhitungan
            $table->string('supplier_code');
            $table->string('supplier_name');
            $table->string('supplier_status', 20);          // Aktif / Nonaktif
            $table->string('partnership_category', 20)->nullable();
            $table->unsignedSmallInteger('masa_kerja_sama')->nullable();
            $table->date('tanggal_awal_kerja_sama')->nullable();

            // Skor C1–C5
            $table->tinyInteger('c1_score')->nullable();
            $table->tinyInteger('c2_score')->nullable();
            $table->tinyInteger('c3_score')->nullable();
            $table->tinyInteger('c4_score')->nullable();
            $table->tinyInteger('c5_score')->nullable();

            // Data agregasi pembentuk skor (JSON)
            $table->json('c1_data')->nullable();
            $table->json('c2_data')->nullable();
            $table->json('c3_data')->nullable();
            $table->json('c4_data')->nullable();
            $table->json('c5_data')->nullable();

            // Apakah dipilih untuk perhitungan
            $table->boolean('is_selected')->default(false);
            $table->string('exclusion_reason')->nullable();
            $table->string('data_completeness', 20)->default('Lengkap'); // Lengkap / Belum Lengkap

            $table->unsignedInteger('transaction_count')->default(0);

            $table->timestamps();

            $table->unique(['calculation_id', 'supplier_id'], 'calc_supplier_unique');
        });

        // ─── Detail RECA per kriteria ───
        Schema::create('calculation_reca_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calculation_id');
            $table->foreign('calculation_id')
                ->references('id')->on('calculations')
                ->cascadeOnDelete();

            $table->string('criteria_code', 10);         // C1, C2, ...
            $table->string('criteria_name');
            $table->string('attribute', 10);             // BENEFIT / COST

            $table->decimal('geometric_mean', 18, 10)->nullable();
            $table->decimal('standard_value', 18, 10)->nullable();    // N_j
            $table->decimal('variation_value', 18, 10)->nullable();   // φ_j
            $table->decimal('deviation_value', 18, 10)->nullable();   // Ω_j
            $table->decimal('weight', 18, 10)->nullable();            // w_j
            $table->decimal('weight_percentage', 8, 4)->nullable();   // w_j * 100

            $table->unsignedSmallInteger('contribution_rank')->nullable();

            $table->timestamps();

            $table->unique(['calculation_id', 'criteria_code'], 'calc_reca_criteria_unique');
        });

        // ─── Detail RECA per supplier per kriteria ───
        Schema::create('calculation_reca_supplier_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calculation_id');
            $table->foreign('calculation_id', 'crsd_calc_fk')
                ->references('id')->on('calculations')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('supplier_id');

            $table->string('criteria_code', 10);

            $table->decimal('x_ij', 10, 6)->nullable();   // Nilai matriks keputusan
            $table->decimal('pv_ij', 18, 10)->nullable();  // Preference value
            $table->decimal('r_ij', 18, 10)->nullable();   // Normalized RECA

            $table->timestamps();

            $table->unique(
                ['calculation_id', 'supplier_id', 'criteria_code'],
                'crsd_unique'
            );
        });

        // ─── Ranking MAUT ───
        Schema::create('calculation_maut_rankings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calculation_id');
            $table->foreign('calculation_id')
                ->references('id')->on('calculations')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('supplier_id');
            $table->string('supplier_name');

            $table->decimal('final_score', 10, 6);
            $table->unsignedSmallInteger('rank');

            $table->json('normalized_scores')->nullable();
            $table->json('weighted_scores')->nullable();

            $table->string('recommendation')->nullable();

            $table->timestamps();

            $table->unique(['calculation_id', 'supplier_id'], 'calc_maut_supplier_unique');
        });

        // ─── Referensi transaksi pembentuk nilai ───
        Schema::create('calculation_source_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calculation_id');
            $table->foreign('calculation_id', 'cst_calc_fk')
                ->references('id')->on('calculations')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('supplier_id');

            $table->unsignedBigInteger('purchase_history_id')->nullable();
            $table->foreign('purchase_history_id', 'cst_ph_fk')
                ->references('id')->on('purchase_histories')
                ->nullOnDelete();

            // Snapshot data transaksi
            $table->string('nomor_po')->nullable();
            $table->date('tanggal_pembelian');
            $table->date('tanggal_penerimaan')->nullable();
            $table->string('nama_produk');
            $table->string('kode_produk')->nullable();
            $table->string('satuan', 30);
            $table->decimal('qty_pembelian', 18, 4);
            $table->decimal('qty_diterima', 18, 4)->default(0);
            $table->decimal('harga_satuan', 18, 4);
            $table->decimal('total_pembelian', 20, 4)->default(0);
            $table->integer('lead_time_hari')->nullable();
            $table->string('status_penerimaan')->nullable();

            $table->timestamps();

            $table->index(['calculation_id', 'supplier_id'], 'cst_calc_supplier_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculation_source_transactions');
        Schema::dropIfExists('calculation_maut_rankings');
        Schema::dropIfExists('calculation_reca_supplier_details');
        Schema::dropIfExists('calculation_reca_details');
        Schema::dropIfExists('calculation_suppliers');
        Schema::dropIfExists('calculations');
    }
};
