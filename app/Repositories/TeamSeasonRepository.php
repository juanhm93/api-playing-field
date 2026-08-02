<?php

namespace App\Repositories;

use App\Models\TeamSeason;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TeamSeasonRepository implements TeamSeasonRepositoryInterface
{
    public function findByTeamAndSeason(int $teamId, int $seasonId, ?int $leagueId = null): Collection
    {
        $query = TeamSeason::query()
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId);

        if ($leagueId !== null) {
            $query->where('league_id', $leagueId);
        }

        return $query->get();
    }

    public function findWithSquad(int $teamSeasonId): ?TeamSeason
    {
        return TeamSeason::query()
            ->with([
                'team.country',
                'season',
                'league',
                'lineup',
                'playerTeamSeasons' => fn ($q) => $q->orderBy('number'),
                'playerTeamSeasons.player.position',
            ])
            ->find($teamSeasonId);
    }

    public function findById(int $id): ?TeamSeason
    {
        return TeamSeason::query()->find($id);
    }
}
