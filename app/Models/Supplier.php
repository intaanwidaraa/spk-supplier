<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    // Ini kuncinya: Buka gembok keamanan biar form Filament bisa nyimpen data ke tabel
    protected $guarded = [];

    public function performanceScores(): HasMany
    {
    return $this->hasMany(SupplierScore::class, 'supplier_id')
        ->orderBy('criterion_id');
    }

    protected $casts = [
        'masa_kerja_sama' => 'integer',
    ];

    public function purchaseHistories(): HasMany
    {
        return $this->hasMany(
            PurchaseHistory::class,
            'supplier_id'
        );
    }
}