<?php

namespace App\Services;

use App\Models\Season;
use App\Models\Team;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeamSeasonService
{
    public function __construct(
        private readonly TeamSeasonRepositoryInterface $teamSeasonRepository,
    ) {}

    /**
     * Build the season squad payload for a team.
     *
     * @return array<string, mixed>
     */
    public function getSquad(Team $team, Season $season, ?string $format = null): array
    {
        $teamSeason = $this->teamSeasonRepository->findByTeamAndSeason($team->id, $season->id);

        if ($teamSeason === null) {
            throw new NotFoundHttpException('Team is not registered for the given season.');
        }

        $assignments = $teamSeason->playerTeamSeasons
            ->sortBy('number')
            ->values()
            ->map(fn ($assignment) => $this->mapAssignment($assignment));

        $payload = [
            'team' => $teamSeason->team,
            'season' => $teamSeason->season,
            'league' => $teamSeason->league,
            'lineup' => $teamSeason->lineup,
            'team_season_id' => $teamSeason->id,
        ];

        if ($this->isClearFormat($format)) {
            $payload['players'] = $assignments->values()->all();
        } else {
            $payload['starters'] = $assignments->where('is_started', true)->values()->all();
            $payload['substitutes'] = $assignments->where('is_started', false)->values()->all();
        }

        return $payload;
    }

    private function isClearFormat(?string $format): bool
    {
        return strtolower((string) $format) === 'clear';
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAssignment($assignment): array
    {
        return [
            'assignment_id' => $assignment->id,
            'number' => $assignment->number,
            'is_started' => (bool) $assignment->is_started,
            'player' => $assignment->player,
        ];
    }
}
