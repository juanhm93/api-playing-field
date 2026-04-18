<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

class Season extends Model
{
    /** @use HasFactory<\Database\Factories\SeasonFactory> */
    use HasFactory;
    #[Fillable(['name', 'slug', 'code', 'start_date', 'end_date'])]

    public function teamSeasons()
    {
        return $this->hasMany(TeamSeason::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_season')
            ->withTimestamps();
    }
}
