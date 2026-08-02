<?php

namespace App\Services;

use App\Models\Lineup;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Repositories\Contracts\PlayerTeamSeasonRepositoryInterface;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;

class TeamSeasonService
{
    public function __construct(
        private readonly TeamSeasonRepositoryInterface $teamSeasonRepository,
        private readonly PlayerTeamSeasonRepositoryInterface $playerTeamSeasonRepository,
    ) {}

    public function getTeamBySeason(Team $team, Season $season, ?int $leagueId, ?string $format): array
    {
        $teamSeason = $this->teamSeasonRepository->findByTeamAndSeasonOrFail(
            $team->id,
            $season->id,
            $leagueId,
        );

        $teamSeason = $this->teamSeasonRepository->loadForShow($teamSeason);

        return $this->buildResponse($teamSeason, $format === 'clear');
    }

    private function buildResponse(TeamSeason $teamSeason, bool $clearFormat): array
    {
        $roster = $this->playerTeamSeasonRepository->getByTeamSeason($teamSeason->id);
        $formattedPlayers = $roster->map(fn ($entry) => $this->formatPlayer($entry));

        $base = [
            'team' => [
                'id' => $teamSeason->team->id,
                'name' => $teamSeason->team->name,
                'slug' => $teamSeason->team->slug,
                'code' => $teamSeason->team->code,
                'logo' => $teamSeason->team->logo,
                'country' => $teamSeason->team->country,
            ],
            'season' => $teamSeason->season,
            'league' => $teamSeason->league,
            'lineup' => $teamSeason->lineup,
        ];

        if ($clearFormat) {
            $base['players'] = $formattedPlayers->values()->all();

            return $base;
        }

        [$starters, $substitutes] = $formattedPlayers->partition(
            fn (array $player) => $player['is_started'] === true
        );

        $base['starters'] = $starters->values()->all();
        $base['substitutes'] = $substitutes->values()->all();

        return $base;
    }

    private function formatPlayer($playerTeamSeason): array
    {
        $player = $playerTeamSeason->player;

        return [
            'id' => $player->id,
            'name' => $player->name,
            'lastname' => $player->lastname,
            'nickname' => $player->nickname,
            'slug' => $player->slug,
            'photo' => $player->photo,
            'number' => $playerTeamSeason->number,
            'is_started' => $playerTeamSeason->is_started,
            'position' => $player->position,
        ];
    }
}
