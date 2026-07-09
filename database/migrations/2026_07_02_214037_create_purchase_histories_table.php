<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_histories', function (Blueprint $table) {
            $table->id();

            /*
             * Supplier master.
             * Dibuat nullable agar data historis tetap tersimpan
             * meskipun supplier master suatu saat dihapus.
             */
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * Snapshot supplier agar riwayat lama tetap terbaca.
             */
            $table->string('supplier_name');
            $table->string('jenis_supplier');

            /*
             * Informasi transaksi.
             * Nomor PO tidak dibuat unique karena satu PO
             * dapat memiliki beberapa produk.
             */
            $table->string('nomor_po')->nullable();
            $table->string('nomor_penerimaan')->nullable();
            $table->date('tanggal_pembelian');

            /*
             * Informasi produk.
             */
            $table->string('kode_produk')->nullable();
            $table->string('nama_produk');
            $table->string('satuan', 30);

            /*
             * Informasi pembelian.
             */
            $table->decimal('qty_pembelian', 18, 4);
            $table->decimal('harga_satuan', 18, 4);
            $table->decimal('total_pembelian', 20, 4)
                ->default(0);

            /*
             * Informasi penerimaan.
             */
            $table->date('estimasi_tanggal_penerimaan')
                ->nullable();

            $table->date('tanggal_penerimaan')
                ->nullable();

            $table->decimal('qty_diterima', 18, 4)
                ->default(0);

            /*
             * Hasil kalkulasi otomatis.
             */
            $table->decimal('outstanding', 18, 4)
                ->default(0);

            // Disimpan dalam skala 0 sampai 100.
            $table->decimal('fulfillment_rate', 8, 4)
                ->default(0);

            $table->integer('lead_time_hari')
                ->nullable();

            $table->string('status_penerimaan')
                ->default('Belum Diterima');

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('tanggal_pembelian');
            $table->index('tanggal_penerimaan');
            $table->index('nomor_po');
            $table->index('nama_produk');
            $table->index('jenis_supplier');
            $table->index('status_penerimaan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_histories');
    }
};