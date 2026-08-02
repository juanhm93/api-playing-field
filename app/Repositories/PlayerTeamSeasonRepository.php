<?php

namespace App\Repositories;

use App\Models\PlayerTeamSeason;
use App\Repositories\Contracts\PlayerTeamSeasonRepositoryInterface;
use Illuminate\Support\Collection;

class PlayerTeamSeasonRepository implements PlayerTeamSeasonRepositoryInterface
{
    public function getByTeamSeason(int $teamSeasonId): Collection
    {
        return PlayerTeamSeason::query()
            ->where('team_season_id', $teamSeasonId)
            ->with('player.position')
            ->orderByDesc('is_started')
            ->orderBy('number')
            ->get();
    }

    public function existsInRoster(int $playerId, int $teamSeasonId): bool
    {
        return PlayerTeamSeason::query()
            ->where('player_id', $playerId)
            ->where('team_season_id', $teamSeasonId)
            ->exists();
    }

    public function findInRoster(int $playerId, int $teamSeasonId): ?PlayerTeamSeason
    {
        return PlayerTeamSeason::query()
            ->where('player_id', $playerId)
            ->where('team_season_id', $teamSeasonId)
            ->with('player.position')
            ->first();
    }

    public function create(array $data): PlayerTeamSeason
    {
        return PlayerTeamSeason::query()->create($data);
    }

    public function update(PlayerTeamSeason $playerTeamSeason, array $data): PlayerTeamSeason
    {
        $playerTeamSeason->update($data);

        return $playerTeamSeason->fresh(['player.position']);
    }

    public function delete(PlayerTeamSeason $playerTeamSeason): bool
    {
        return (bool) $playerTeamSeason->delete();
    }
}
