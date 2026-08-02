<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'code', 'logo', 'country_id'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    public function seasons()
    {
        return $this->belongsToMany(Season::class, 'team_season')
            ->withTimestamps();
    }

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function teamSeasons()
    {
        return $this->hasMany(TeamSeason::class);
    }

    public function playerTeamSeasons()
    {
        return $this->hasManyThrough(
            PlayerTeamSeason::class,
            TeamSeason::class,
            'team_id',
            'team_season_id',
            'id',
            'id'
        );
    }

    public function playerSeasonStats()
    {
        return $this->hasMany(PlayerSeasonStat::class);
    }

    public function lineups()
    {
        return $this->hasMany(Lineup::class);
    }
}
