<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'lastname', 'nickname', 'slug', 'code', 'photo', 'date_of_birth', 'nationality', 'height', 'weight', 'foot', 'position_id', 'team_id'])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
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
