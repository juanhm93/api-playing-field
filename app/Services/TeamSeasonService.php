<?php

namespace App\Services;

use App\Models\PlayerTeamSeason;
use App\Models\TeamSeason;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class TeamSeasonService
{
    public function __construct(
        private readonly TeamSeasonRepositoryInterface $teamSeasonRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getTeamSquadBySeason(int $teamId, int $seasonId, ?int $leagueId = null, string $format = 'default'): array
    {
        $matches = $this->teamSeasonRepository->findByTeamAndSeason($teamId, $seasonId, $leagueId);

        if ($matches->isEmpty()) {
            throw (new ModelNotFoundException)->setModel(TeamSeason::class);
        }

        if ($matches->count() > 1 && $leagueId === null) {
            throw new InvalidArgumentException(
                'Multiple team-season entries found. Pass league_id to disambiguate.'
            );
        }

        $teamSeason = $this->teamSeasonRepository->findWithSquad($matches->first()->id);

        if ($teamSeason === null) {
            throw (new ModelNotFoundException)->setModel(TeamSeason::class);
        }

        return $this->formatSquadResponse($teamSeason, $format);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSquadResponse(TeamSeason $teamSeason, string $format): array
    {
        $assignments = $teamSeason->playerTeamSeasons->map(fn (PlayerTeamSeason $row) => $this->mapAssignment($row));

        $base = [
            'team_season_id' => $teamSeason->id,
            'team' => $teamSeason->team,
            'season' => $teamSeason->season,
            'league' => $teamSeason->league,
            'lineup' => $teamSeason->lineup,
        ];

        if ($format === 'clear') {
            return [
                ...$base,
                'players' => $assignments->values()->all(),
            ];
        }

        return [
            ...$base,
            'starters' => $assignments->where('is_started', true)->values()->all(),
            'substitutes' => $assignments->where('is_started', false)->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAssignment(PlayerTeamSeason $assignment): array
    {
        return [
            'assignment_id' => $assignment->id,
            'number' => $assignment->number,
            'is_started' => (bool) $assignment->is_started,
            'player' => $assignment->player,
        ];
    }
}
