<?php

namespace App\Repositories;

use App\Models\PlayerTeamSeason;
use App\Repositories\Contracts\PlayerTeamSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PlayerTeamSeasonRepository implements PlayerTeamSeasonRepositoryInterface
{
    public function list(array $filters = []): Collection
    {
        $query = PlayerTeamSeason::query()
            ->with([
                'player.position',
                'teamSeason.team',
                'teamSeason.season',
                'teamSeason.league',
                'teamSeason.lineup',
            ]);

        if (! empty($filters['player_id'])) {
            $query->where('player_id', $filters['player_id']);
        }

        if (! empty($filters['team_season_id'])) {
            $query->where('team_season_id', $filters['team_season_id']);
        }

        if (! empty($filters['team_id'])) {
            $query->whereHas('teamSeason', fn ($q) => $q->where('team_id', $filters['team_id']));
        }

        if (! empty($filters['season_id'])) {
            $query->whereHas('teamSeason', fn ($q) => $q->where('season_id', $filters['season_id']));
        }

        return $query->orderBy('id')->get();
    }

    public function findById(int $id): ?PlayerTeamSeason
    {
        return PlayerTeamSeason::query()
            ->with([
                'player.position',
                'teamSeason.team',
                'teamSeason.season',
                'teamSeason.league',
                'teamSeason.lineup',
            ])
            ->find($id);
    }

    public function findByPlayerAndTeamSeason(int $playerId, int $teamSeasonId): ?PlayerTeamSeason
    {
        return PlayerTeamSeason::query()
            ->where('player_id', $playerId)
            ->where('team_season_id', $teamSeasonId)
            ->first();
    }

    public function findByPlayerTeamAndSeason(int $playerId, int $teamId, int $seasonId): ?PlayerTeamSeason
    {
        return PlayerTeamSeason::query()
            ->where('player_id', $playerId)
            ->whereHas('teamSeason', function ($query) use ($teamId, $seasonId) {
                $query->where('team_id', $teamId)->where('season_id', $seasonId);
            })
            ->with(['player.position', 'teamSeason.team', 'teamSeason.season'])
            ->first();
    }

    public function create(array $data): PlayerTeamSeason
    {
        $assignment = PlayerTeamSeason::query()->create($data);

        return $assignment->load([
            'player.position',
            'teamSeason.team',
            'teamSeason.season',
            'teamSeason.league',
            'teamSeason.lineup',
        ]);
    }

    public function update(PlayerTeamSeason $assignment, array $data): PlayerTeamSeason
    {
        $assignment->update($data);

        return $assignment->fresh([
            'player.position',
            'teamSeason.team',
            'teamSeason.season',
            'teamSeason.league',
            'teamSeason.lineup',
        ]);
    }

    public function delete(PlayerTeamSeason $assignment): bool
    {
        return (bool) $assignment->delete();
    }
}
