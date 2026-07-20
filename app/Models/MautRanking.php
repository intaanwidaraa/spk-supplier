<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MautRanking extends Model
{
    protected $guarded = [];

    protected $casts = [
        'normalized_scores' => 'array',
        'weighted_scores' => 'array',
    ];
}
