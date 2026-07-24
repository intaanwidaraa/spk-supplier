<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculationMautRanking extends Model
{
    protected $guarded = [];

    protected $casts = [
        'final_score'       => 'decimal:6',
        'rank'              => 'integer',
        'normalized_scores' => 'array',
        'weighted_scores'   => 'array',
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
