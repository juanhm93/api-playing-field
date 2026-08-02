<?php

namespace App\Repositories\Contracts;

use App\Models\TeamSeason;
use Illuminate\Database\Eloquent\Collection;

interface TeamSeasonRepositoryInterface
{
    /**
     * @return Collection<int, TeamSeason>
     */
    public function findByTeamAndSeason(int $teamId, int $seasonId, ?int $leagueId = null): Collection;

    public function findWithSquad(int $teamSeasonId): ?TeamSeason;

    public function findById(int $id): ?TeamSeason;
}
