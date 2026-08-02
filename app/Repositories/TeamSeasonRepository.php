<?php

namespace App\Repositories;

use App\Models\TeamSeason;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TeamSeasonRepository implements TeamSeasonRepositoryInterface
{
    public function findByTeamAndSeason(int $teamId, int $seasonId): ?TeamSeason
    {
        return TeamSeason::query()
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->with([
                'team.country',
                'season',
                'league',
                'lineup',
                'playerTeamSeasons.player.position',
            ])
            ->first();
    }

    public function findOrCreate(array $attributes): TeamSeason
    {
        return TeamSeason::query()->firstOrCreate(
            [
                'team_id' => $attributes['team_id'],
                'season_id' => $attributes['season_id'],
                'league_id' => $attributes['league_id'],
            ],
            [
                'lineup_id' => $attributes['lineup_id'],
            ]
        );
    }

    public function listForFilters(?int $teamId = null, ?int $seasonId = null): Collection
    {
        $query = TeamSeason::query()->with(['team', 'season', 'league', 'lineup']);

        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        }

        if ($seasonId !== null) {
            $query->where('season_id', $seasonId);
        }

        return $query->orderBy('id')->get();
    }
}
