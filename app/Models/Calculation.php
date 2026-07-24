<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calculation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'period_start'      => 'date',
        'period_end'        => 'date',
        'calculated_at'     => 'datetime',
        'total_candidates'  => 'integer',
        'total_selected'    => 'integer',
        'total_reca_weight' => 'decimal:8',
        'reca_weight_valid' => 'boolean',
    ];

    // ─── Relationships ───

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'product_group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function calculationSuppliers(): HasMany
    {
        return $this->hasMany(CalculationSupplier::class, 'calculation_id');
    }

    public function selectedSuppliers(): HasMany
    {
        return $this->hasMany(CalculationSupplier::class, 'calculation_id')
            ->where('is_selected', true);
    }

    public function recaDetails(): HasMany
    {
        return $this->hasMany(CalculationRecaDetail::class, 'calculation_id')
            ->orderBy('contribution_rank');
    }

    public function recaSupplierDetails(): HasMany
    {
        return $this->hasMany(CalculationRecaSupplierDetail::class, 'calculation_id');
    }

    public function mautRankings(): HasMany
    {
        return $this->hasMany(CalculationMautRanking::class, 'calculation_id')
            ->orderBy('rank');
    }

    public function sourceTransactions(): HasMany
    {
        return $this->hasMany(CalculationSourceTransaction::class, 'calculation_id');
    }

    // ─── Auto-generate code ───

    protected static function booted(): void
    {
        static::creating(function (Calculation $model): void {
            if (blank($model->calculation_code)) {
                $model->calculation_code = static::generateNextCode(
                    $model->supplier_category
                );
            }
        });
    }

    public static function generateNextCode(string $category): string
    {
        $suffix = match ($category) {
            'Raw Material'       => 'RM',
            'Packaging Material' => 'PM',
            default              => 'XX',
        };

        $year = now()->year;

        $count = static::where('supplier_category', $category)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return "CALC-{$suffix}-{$year}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
