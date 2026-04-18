<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

class League extends Model
{
    /** @use HasFactory<\Database\Factories\LeagueFactory> */
    use HasFactory;
    #[Fillable(['name', 'slug', 'code', 'logo', 'country_id'])]
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function divisions()
    {
        return $this->hasMany(Division::class);
    }
}
