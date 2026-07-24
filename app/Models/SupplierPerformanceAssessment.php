<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPerformanceAssessment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'assessment_date'  => 'date',
        'calculated_at'    => 'datetime',
        'is_auto_calculated' => 'boolean',
        'c1_score' => 'integer',
        'c2_score' => 'integer',
        'c3_score' => 'integer',
        'c4_score' => 'integer',
        'c5_score' => 'integer',
        'total_score' => 'float',
        'product_group_id' => 'integer',
    ];

    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProductGroup::class, 'product_group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function scoreDetails(): HasMany
    {
        return $this->hasMany(SupplierPerformanceScoreDetail::class, 'supplier_performance_assessment_id')
            ->orderBy('criterion_id');
    }

    /**
     * Calculate total score from C1-C5 (simple average for now).
     */
    public function calculateTotalScore(): float
    {
        $sum = ($this->c1_score ?? 0) + 
               ($this->c2_score ?? 0) + 
               ($this->c3_score ?? 0) + 
               ($this->c4_score ?? 0) + 
               ($this->c5_score ?? 0);

        return round($sum / 5, 4);
    }
}
