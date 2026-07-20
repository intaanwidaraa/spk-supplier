<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationResult extends Model
{
    protected $guarded = [];

    public function recaWeights()
    {
        return $this->hasMany(RecaWeight::class);
    }

    public function mautRankings()
    {
        return $this->hasMany(MautRanking::class);
    }
}
