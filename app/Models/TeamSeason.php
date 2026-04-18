<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamSeason extends Model
{
    protected $table = 'team_season';

    protected $fillable = [
        'team_id',
        'season_id',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function playerTeamSeasons(): HasMany
    {
        return $this->hasMany(PlayerTeamSeason::class);
    }
}
