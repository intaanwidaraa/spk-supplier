<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseHistory extends Model
{
    use HasFactory;

    protected $table = 'purchase_histories';

    protected $guarded = [];

    protected $casts = [
        'tanggal_pembelian' => 'date',
        'estimasi_tanggal_penerimaan' => 'date',
        'tanggal_penerimaan' => 'date',

        'qty_pembelian' => 'decimal:4',
        'harga_satuan' => 'decimal:4',
        'total_pembelian' => 'decimal:4',
        'qty_diterima' => 'decimal:4',
        'outstanding' => 'decimal:4',
        'fulfillment_rate' => 'decimal:4',

        'lead_time_hari' => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id'
        );
    }

    protected static function booted(): void
    {
        static::saving(function (PurchaseHistory $history): void {
            /*
             * Ambil nama dan jenis supplier dari master.
             */
            if ($history->supplier_id) {
                $supplier = Supplier::query()
                    ->find($history->supplier_id);

                if ($supplier) {
                    $history->supplier_name =
                        $supplier->nama_supplier;

                    $history->jenis_supplier =
                        $supplier->kategori;
                }
            }

            $qtyPembelian =
                (float) ($history->qty_pembelian ?? 0);

            $hargaSatuan =
                (float) ($history->harga_satuan ?? 0);

            $qtyDiterima =
                (float) ($history->qty_diterima ?? 0);

            /*
             * Total pembelian.
             */
            $history->total_pembelian = round(
                $qtyPembelian * $hargaSatuan,
                4
            );

            /*
             * Outstanding dapat bernilai negatif
             * jika barang diterima melebihi pesanan.
             */
            $history->outstanding = round(
                $qtyPembelian - $qtyDiterima,
                4
            );

            /*
             * Fulfillment rate dalam persen.
             */
            $history->fulfillment_rate =
                $qtyPembelian > 0
                    ? round(
                        ($qtyDiterima / $qtyPembelian) * 100,
                        4
                    )
                    : 0;

            /*
             * Lead time aktual:
             * tanggal penerimaan dikurangi tanggal pembelian.
             */
            if (
                $history->tanggal_pembelian &&
                $history->tanggal_penerimaan
            ) {
                $tanggalPembelian = Carbon::parse(
                    $history->tanggal_pembelian
                );

                $tanggalPenerimaan = Carbon::parse(
                    $history->tanggal_penerimaan
                );

                $history->lead_time_hari = (int)
                    $tanggalPembelian->diffInDays(
                        $tanggalPenerimaan,
                        false
                    );
            } else {
                $history->lead_time_hari = null;
            }

            /*
             * Status penerimaan.
             */
            if ($qtyDiterima <= 0) {
                $history->status_penerimaan =
                    'Belum Diterima';
            } elseif ($qtyDiterima < $qtyPembelian) {
                $history->status_penerimaan =
                    'Diterima Sebagian';
            } elseif ($qtyDiterima > $qtyPembelian) {
                $history->status_penerimaan =
                    'Kelebihan Penerimaan';
            } else {
                $history->status_penerimaan =
                    'Diterima Lengkap';
            }
        });
    }
}