<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'product_group_id');
    }

    /**
     * Many-to-many with Supplier via supplier_products pivot.
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(
            Supplier::class,
            'product_supplier',
            'product_id',
            'supplier_id'
        )->withTimestamps();
    }

    protected static function booted(): void
    {
        static::creating(function (Product $model): void {
            if (blank($model->kode_produk)) {
                $model->kode_produk = static::generateNextCode();
            }
            // Auto-inherit kategori_produk from product group if not set
            if (blank($model->kategori_produk) && $model->product_group_id) {
                $group = ProductGroup::find($model->product_group_id);
                $model->kategori_produk = $group?->kategori_produk;
            }
        });
    }

    public static function generateNextCode(): string
    {
        $last = static::query()->latest('id')->value('kode_produk');

        if (!$last) {
            return 'P-001';
        }

        $number = (int) substr($last, 2);
        return 'P-' . str_pad((string) ($number + 1), 3, '0', STR_PAD_LEFT);
    }
}
