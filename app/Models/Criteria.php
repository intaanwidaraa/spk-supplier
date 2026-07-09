<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Criteria extends Model
{
    use HasFactory;

    /**
     * Migration membuat tabel bernama "criteria".
     */
    protected $table = 'criteria';

    /**
     * Izinkan seluruh kolom diisi melalui Filament.
     */
    protected $guarded = [];

    /**
     * Casting kolom.
     */
    protected $casts = [
        'bobot_default' => 'float',
    ];

    /**
     * Membuat ID kriteria otomatis ketika data disimpan.
     */
    protected static function booted(): void
    {
        static::creating(function (Criteria $criteria): void {
            if (blank($criteria->kode_kriteria)) {
                $criteria->kode_kriteria = static::generateNextCode();
            }
        });
    }

    /**
     * Menghasilkan kode berikutnya:
     * K01, K02, K03, dan seterusnya.
     */
    public static function generateNextCode(): string
    {
        $lastCode = static::query()
            ->latest('id')
            ->value('kode_kriteria');

        if (! $lastCode) {
            return 'K01';
        }

        $lastNumber = (int) substr($lastCode, 1);
        $nextNumber = $lastNumber + 1;

        return 'K' . str_pad(
            (string) $nextNumber,
            2,
            '0',
            STR_PAD_LEFT
        );
    }

    

    
}