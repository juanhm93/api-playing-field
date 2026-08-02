<?php

namespace App\Repositories\Contracts;

use App\Models\TeamSeason;

interface TeamSeasonRepositoryInterface
{
    public function findByTeamAndSeason(int $teamId, int $seasonId, ?int $leagueId = null): ?TeamSeason;

    public function findByTeamAndSeasonOrFail(int $teamId, int $seasonId, ?int $leagueId = null): TeamSeason;

    public function findOrCreate(array $attributes, array $values = []): TeamSeason;

    public function loadForShow(TeamSeason $teamSeason): TeamSeason;
}
