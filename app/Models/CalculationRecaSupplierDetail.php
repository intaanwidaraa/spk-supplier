<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculationRecaSupplierDetail extends Model
{
    protected $guarded = [];

    protected $casts = [
        'x_ij'  => 'decimal:6',
        'pv_ij' => 'decimal:10',
        'r_ij'  => 'decimal:10',
    ];

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(Calculation::class, 'calculation_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
