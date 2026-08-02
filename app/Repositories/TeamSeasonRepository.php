<?php

namespace App\Repositories;

use App\Models\TeamSeason;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TeamSeasonRepository implements TeamSeasonRepositoryInterface
{
    public function findByTeamAndSeason(int $teamId, int $seasonId, ?int $leagueId = null): ?TeamSeason
    {
        $query = TeamSeason::query()
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId);

        if ($leagueId !== null) {
            $query->where('league_id', $leagueId);
        }

        return $query->first();
    }

    public function findByTeamAndSeasonOrFail(int $teamId, int $seasonId, ?int $leagueId = null): TeamSeason
    {
        $teamSeason = $this->findByTeamAndSeason($teamId, $seasonId, $leagueId);

        if ($teamSeason === null) {
            throw (new ModelNotFoundException)->setModel(TeamSeason::class);
        }

        return $teamSeason;
    }

    public function findOrCreate(array $attributes, array $values = []): TeamSeason
    {
        return TeamSeason::query()->firstOrCreate($attributes, $values);
    }

    public function loadForShow(TeamSeason $teamSeason): TeamSeason
    {
        return $teamSeason->load([
            'team.country',
            'season',
            'league',
            'lineup',
            'playerTeamSeasons.player.position',
        ]);
    }
}
