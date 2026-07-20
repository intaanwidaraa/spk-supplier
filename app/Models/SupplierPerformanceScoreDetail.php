<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceScoreDetail extends Model
{
    use HasFactory;

    protected $table = 'supplier_performance_score_details';

    protected $guarded = [];

    protected $casts = [
        'raw_value'          => 'float',
        'auto_score'         => 'integer',
        'final_score'        => 'integer',
        'is_manual_override' => 'boolean',
        'overridden_at'      => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(SupplierPerformanceAssessment::class, 'supplier_performance_assessment_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criteria::class, 'criterion_id');
    }
}
