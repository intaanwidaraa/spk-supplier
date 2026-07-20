<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationPeriod extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'year'       => 'integer',
    ];

    public function assessments(): HasMany
    {
        return $this->hasMany(SupplierPerformanceAssessment::class, 'evaluation_period_id');
    }

    /**
     * Auto-generate evaluation_code if not provided.
     */
    protected static function booted(): void
    {
        static::creating(function (EvaluationPeriod $model): void {
            if (blank($model->evaluation_code)) {
                $suffix = match ($model->product_category) {
                    'Raw Material'       => 'RM',
                    'Packaging Material' => 'PM',
                    default              => 'EV',
                };
                $year   = $model->year ?? now()->year;
                $count  = static::where('product_category', $model->product_category)
                    ->where('year', $year)
                    ->count() + 1;
                $model->evaluation_code = "EV-{$year}-{$suffix}-" . str_pad($count, 2, '0', STR_PAD_LEFT);
            }
        });
    }
}
