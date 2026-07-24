<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculationSupplier extends Model
{
    protected $guarded = [];

    protected $casts = [
        'c1_score'                  => 'integer',
        'c2_score'                  => 'integer',
        'c3_score'                  => 'integer',
        'c4_score'                  => 'integer',
        'c5_score'                  => 'integer',
        'c1_data'                   => 'array',
        'c2_data'                   => 'array',
        'c3_data'                   => 'array',
        'c4_data'                   => 'array',
        'c5_data'                   => 'array',
        'is_selected'               => 'boolean',
        'transaction_count'         => 'integer',
        'masa_kerja_sama'           => 'integer',
        'tanggal_awal_kerja_sama'   => 'date',
    ];

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(Calculation::class, 'calculation_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Check if all C1–C5 scores are filled.
     */
    public function isDataComplete(): bool
    {
        return $this->c1_score !== null
            && $this->c2_score !== null
            && $this->c3_score !== null
            && $this->c4_score !== null
            && $this->c5_score !== null;
    }

    /**
     * Get list of missing criteria.
     */
    public function getMissingCriteria(): array
    {
        $missing = [];
        if ($this->c1_score === null) $missing[] = 'C1';
        if ($this->c2_score === null) $missing[] = 'C2';
        if ($this->c3_score === null) $missing[] = 'C3';
        if ($this->c4_score === null) $missing[] = 'C4';
        if ($this->c5_score === null) $missing[] = 'C5';
        return $missing;
    }
}
