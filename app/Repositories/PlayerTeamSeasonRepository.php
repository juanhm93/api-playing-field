<?php

namespace App\Repositories;

use App\Models\PlayerTeamSeason;
use App\Repositories\Contracts\PlayerTeamSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class PlayerTeamSeasonRepository implements PlayerTeamSeasonRepositoryInterface
{
    public function list(array $filters = []): Collection
    {
        $query = PlayerTeamSeason::query()
            ->with(['player.position', 'teamSeason.team', 'teamSeason.season', 'teamSeason.league', 'teamSeason.lineup']);

        if (! empty($filters['player_id'])) {
            $query->where('player_id', $filters['player_id']);
        }

        if (! empty($filters['team_season_id'])) {
            $query->where('team_season_id', $filters['team_season_id']);
        }

        if (! empty($filters['team_id']) || ! empty($filters['season_id']) || ! empty($filters['league_id'])) {
            $query->whereHas('teamSeason', function ($q) use ($filters) {
                if (! empty($filters['team_id'])) {
                    $q->where('team_id', $filters['team_id']);
                }
                if (! empty($filters['season_id'])) {
                    $q->where('season_id', $filters['season_id']);
                }
                if (! empty($filters['league_id'])) {
                    $q->where('league_id', $filters['league_id']);
                }
            });
        }

        return $query->orderBy('number')->get();
    }

    public function findById(int $id): ?PlayerTeamSeason
    {
        return PlayerTeamSeason::query()
            ->with(['player.position', 'teamSeason.team', 'teamSeason.season', 'teamSeason.league', 'teamSeason.lineup'])
            ->find($id);
    }

    public function findByPlayerAndTeamSeason(int $playerId, int $teamSeasonId): ?PlayerTeamSeason
    {
        return PlayerTeamSeason::query()
            ->where('player_id', $playerId)
            ->where('team_season_id', $teamSeasonId)
            ->first();
    }

    public function findByPlayerAndSeason(int $playerId, int $seasonId): Collection
    {
        return PlayerTeamSeason::query()
            ->where('player_id', $playerId)
            ->whereHas('teamSeason', fn ($q) => $q->where('season_id', $seasonId))
            ->get();
    }

    public function create(array $data): PlayerTeamSeason
    {
        return PlayerTeamSeason::query()->create($data);
    }

    public function update(PlayerTeamSeason $assignment, array $data): PlayerTeamSeason
    {
        $assignment->update($data);

        return $assignment->fresh(['player.position', 'teamSeason.team', 'teamSeason.season', 'teamSeason.league', 'teamSeason.lineup']);
    }

    public function delete(PlayerTeamSeason $assignment): bool
    {
        return (bool) $assignment->delete();
    }

    public function jerseyNumbersForTeamSeason(int $teamSeasonId, ?int $exceptAssignmentId = null): SupportCollection
    {
        $query = PlayerTeamSeason::query()
            ->where('team_season_id', $teamSeasonId)
            ->where('number', '>', 0);

        if ($exceptAssignmentId !== null) {
            $query->where('id', '!=', $exceptAssignmentId);
        }

        return $query->pluck('number');
    }
}
