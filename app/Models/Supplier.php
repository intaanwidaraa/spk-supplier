<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'masa_kerja_sama'           => 'integer',
        'tanggal_awal_kerja_sama'   => 'date',
    ];

    public function purchaseHistories(): HasMany
    {
        return $this->hasMany(
            PurchaseHistory::class,
            'supplier_id'
        );
    }

    /**
     * Many-to-many dengan Product melalui supplier_products pivot.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_supplier',
            'supplier_id',
            'product_id'
        )->withTimestamps();
    }

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'product_group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}