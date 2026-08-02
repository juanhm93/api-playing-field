<?php

namespace App\Repositories\Contracts;

use App\Models\PlayerTeamSeason;
use Illuminate\Support\Collection;

interface PlayerTeamSeasonRepositoryInterface
{
    public function getByTeamSeason(int $teamSeasonId): Collection;

    public function existsInRoster(int $playerId, int $teamSeasonId): bool;

    public function findInRoster(int $playerId, int $teamSeasonId): ?PlayerTeamSeason;

    public function create(array $data): PlayerTeamSeason;

    public function update(PlayerTeamSeason $playerTeamSeason, array $data): PlayerTeamSeason;

    public function delete(PlayerTeamSeason $playerTeamSeason): bool;
}
