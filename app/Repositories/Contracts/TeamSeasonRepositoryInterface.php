<?php

namespace App\Repositories\Contracts;

use App\Models\TeamSeason;
use Illuminate\Database\Eloquent\Collection;

interface TeamSeasonRepositoryInterface
{
    public function findByTeamAndSeason(int $teamId, int $seasonId): ?TeamSeason;

    public function findOrCreate(array $attributes): TeamSeason;

    /**
     * @return Collection<int, TeamSeason>
     */
    public function listForFilters(?int $teamId = null, ?int $seasonId = null): Collection;
}
