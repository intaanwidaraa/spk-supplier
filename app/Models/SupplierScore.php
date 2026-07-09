<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierScore extends Model
{
    protected $table = 'supplier_scores';

    protected $fillable = [
        'supplier_id',
        'criterion_id',
        'nilai',
        'catatan',
    ];

    protected $casts = [
        'nilai' => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criteria::class, 'criterion_id');
    }
}