<?php

namespace App\Repositories\Contracts;

use App\Models\PlayerTeamSeason;
use Illuminate\Database\Eloquent\Collection;

interface PlayerTeamSeasonRepositoryInterface
{
    /**
     * @return Collection<int, PlayerTeamSeason>
     */
    public function list(array $filters = []): Collection;

    public function findById(int $id): ?PlayerTeamSeason;

    public function findByPlayerAndTeamSeason(int $playerId, int $teamSeasonId): ?PlayerTeamSeason;

    public function findByPlayerTeamAndSeason(int $playerId, int $teamId, int $seasonId): ?PlayerTeamSeason;

    public function create(array $data): PlayerTeamSeason;

    public function update(PlayerTeamSeason $assignment, array $data): PlayerTeamSeason;

    public function delete(PlayerTeamSeason $assignment): bool;
}
