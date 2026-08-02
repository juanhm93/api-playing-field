<?php

namespace App\Models;

use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'code', 'start_date', 'end_date'])]
class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

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
