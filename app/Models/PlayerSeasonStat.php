<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

class PlayerSeasonStat extends Model
{
    /** @use HasFactory<\Database\Factories\PlayerSeasonStatFactory> */
    use HasFactory;

    #[Fillable(['player_id', 'season_id', 'team_id', 'number', 'goals', 'assists', 'goals_conceded', 'yellow_cards', 'red_cards', 'minutes_played', 'games_played', 'games_started', 'games_substituted', 'games_unplayed', 'games_suspended', 'games_disqualified', 'games_cancelled'])]
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
