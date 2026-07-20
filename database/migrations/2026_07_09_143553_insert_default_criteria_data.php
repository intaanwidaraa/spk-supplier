<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus data kriteria lama jika ada
        Schema::disableForeignKeyConstraints();
        DB::table('criteria')->truncate();
        DB::table('criterion_score_guidelines')->truncate();
        Schema::enableForeignKeyConstraints();

        $criteria = [
            [
                'kode_kriteria' => 'C1',
                'nama_kriteria' => 'Kualitas Produk',
                'atribut' => 'BENEFIT',
                'deskripsi_singkat' => 'Menilai konsistensi penerimaan dan pembelian ulang produk dari supplier.',
            ],
            [
                'kode_kriteria' => 'C2',
                'nama_kriteria' => 'Harga',
                'atribut' => 'COST',
                'deskripsi_singkat' => 'Menilai tingkat kewajaran harga supplier dibandingkan harga pembanding pada produk, satuan, dan periode yang sama.',
            ],
            [
                'kode_kriteria' => 'C3',
                'nama_kriteria' => 'Masa Kerja Sama',
                'atribut' => 'BENEFIT',
                'deskripsi_singkat' => 'Menilai lama dan keberlanjutan hubungan kerja sama supplier dengan perusahaan.',
            ],
            [
                'kode_kriteria' => 'C4',
                'nama_kriteria' => 'Ketepatan Kuantitas',
                'atribut' => 'BENEFIT',
                'deskripsi_singkat' => 'Menilai kesesuaian jumlah barang yang diterima dengan jumlah barang yang dipesan.',
            ],
            [
                'kode_kriteria' => 'C5',
                'nama_kriteria' => 'Ketepatan Waktu Pengiriman',
                'atribut' => 'BENEFIT',
                'deskripsi_singkat' => 'Menilai kecepatan dan ketepatan supplier dalam mengirimkan barang sesuai waktu penerimaan.',
            ],
        ];

        foreach ($criteria as $c) {
            $c['created_at'] = now();
            $c['updated_at'] = now();
            $id = DB::table('criteria')->insertGetId($c);

            $scores = [
                ['skor' => 1, 'kategori' => 'Sangat Buruk', 'keterangan' => 'Belum ada deskripsi spesifik.'],
                ['skor' => 2, 'kategori' => 'Buruk', 'keterangan' => 'Belum ada deskripsi spesifik.'],
                ['skor' => 3, 'kategori' => 'Cukup', 'keterangan' => 'Belum ada deskripsi spesifik.'],
                ['skor' => 4, 'kategori' => 'Baik', 'keterangan' => 'Belum ada deskripsi spesifik.'],
                ['skor' => 5, 'kategori' => 'Sangat Baik', 'keterangan' => 'Belum ada deskripsi spesifik.'],
            ];

            foreach ($scores as $s) {
                $s['criteria_id'] = $id;
                $s['created_at'] = now();
                $s['updated_at'] = now();
                DB::table('criterion_score_guidelines')->insert($s);
            }
        }
    }

    public function down(): void
    {
        DB::table('criterion_score_guidelines')->truncate();
        DB::table('criteria')->truncate();
    }
};
