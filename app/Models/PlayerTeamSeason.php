<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerTeamSeason extends Model
{
    protected $table = 'player_team_season';

    protected $fillable = [
        'player_id',
        'team_season_id',
        'number',
        'is_started',
    ];

    protected function casts(): array
    {
        return [
            'is_started' => 'boolean',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function teamSeason(): BelongsTo
    {
        return $this->belongsTo(TeamSeason::class);
    }
}
