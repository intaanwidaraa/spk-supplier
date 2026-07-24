<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculationSourceTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tanggal_pembelian'  => 'date',
        'tanggal_penerimaan' => 'date',
        'qty_pembelian'      => 'decimal:4',
        'qty_diterima'       => 'decimal:4',
        'harga_satuan'       => 'decimal:4',
        'total_pembelian'    => 'decimal:4',
        'lead_time_hari'     => 'integer',
    ];

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(Calculation::class, 'calculation_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseHistory(): BelongsTo
    {
        return $this->belongsTo(PurchaseHistory::class, 'purchase_history_id');
    }
}
