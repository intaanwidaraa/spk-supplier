<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculationRecaDetail extends Model
{
    protected $guarded = [];

    protected $casts = [
        'geometric_mean'     => 'decimal:10',
        'standard_value'     => 'decimal:10',
        'variation_value'    => 'decimal:10',
        'deviation_value'    => 'decimal:10',
        'weight'             => 'decimal:10',
        'weight_percentage'  => 'decimal:4',
        'contribution_rank'  => 'integer',
    ];

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(Calculation::class, 'calculation_id');
    }
}
