<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lineup extends Model
{
    protected $fillable = [
        'name',
    ];

    public function teamSeasons(): HasMany
    {
        return $this->hasMany(TeamSeason::class);
    }
}
