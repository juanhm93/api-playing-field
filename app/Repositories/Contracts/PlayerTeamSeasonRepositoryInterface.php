<?php

namespace App\Repositories\Contracts;

use App\Models\PlayerTeamSeason;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface PlayerTeamSeasonRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, PlayerTeamSeason>
     */
    public function list(array $filters = []): Collection;

    public function findById(int $id): ?PlayerTeamSeason;

    public function findByPlayerAndTeamSeason(int $playerId, int $teamSeasonId): ?PlayerTeamSeason;

    /**
     * @return Collection<int, PlayerTeamSeason>
     */
    public function findByPlayerAndSeason(int $playerId, int $seasonId): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PlayerTeamSeason;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PlayerTeamSeason $assignment, array $data): PlayerTeamSeason;

    public function delete(PlayerTeamSeason $assignment): bool;

    /**
     * @return SupportCollection<int, int>
     */
    public function jerseyNumbersForTeamSeason(int $teamSeasonId, ?int $exceptAssignmentId = null): SupportCollection;
}
