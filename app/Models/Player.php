<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    /** @use HasFactory<\Database\Factories\PlayerFactory> */
    use HasFactory;

    #[Fillable(['name', 'lastname', 'nickname', 'slug', 'code', 'photo', 'date_of_birth', 'nationality', 'height', 'weight', 'foot', 'position_id'])]
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function playerTeamSeasons()
    {
        return $this->hasMany(PlayerTeamSeason::class);
    }

    public function playerSeasonStats()
    {
        return $this->hasMany(PlayerSeasonStat::class);
    }
}
