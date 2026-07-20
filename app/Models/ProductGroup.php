<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductGroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_group_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'product_group_id');
    }

    protected static function booted(): void
    {
        static::creating(function (ProductGroup $model): void {
            if (blank($model->kode_kelompok_produk)) {
                $model->kode_kelompok_produk = static::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $last = static::query()->latest('id')->value('kode_kelompok_produk');

        if (!$last) {
            return 'KP-001';
        }

        $number = (int) substr($last, 3);
        return 'KP-' . str_pad((string) ($number + 1), 3, '0', STR_PAD_LEFT);
    }
}
