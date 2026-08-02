<?php

namespace App\Services;

use App\Models\Lineup;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Repositories\Contracts\PlayerTeamSeasonRepositoryInterface;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PlayerAssignmentService
{
    public function __construct(
        private readonly TeamSeasonRepositoryInterface $teamSeasonRepository,
        private readonly PlayerTeamSeasonRepositoryInterface $playerTeamSeasonRepository,
    ) {}

    public function list(Team $team, Season $season, ?int $leagueId): Collection
    {
        $teamSeason = $this->teamSeasonRepository->findByTeamAndSeasonOrFail(
            $team->id,
            $season->id,
            $leagueId,
        );

        return $this->playerTeamSeasonRepository
            ->getByTeamSeason($teamSeason->id)
            ->map(fn ($entry) => $this->formatAssignment($entry));
    }

    public function show(Team $team, Season $season, Player $player, ?int $leagueId): array
    {
        $teamSeason = $this->teamSeasonRepository->findByTeamAndSeasonOrFail(
            $team->id,
            $season->id,
            $leagueId,
        );

        $assignment = $this->playerTeamSeasonRepository->findInRoster($player->id, $teamSeason->id);

        if ($assignment === null) {
            throw (new ModelNotFoundException)->setModel(Player::class, [$player->id]);
        }

        return $this->formatAssignment($assignment);
    }

    public function assign(Team $team, Season $season, array $data): array
    {
        return DB::transaction(function () use ($team, $season, $data) {
            $teamSeason = $this->resolveTeamSeason($team, $season, $data);

            if ($this->playerTeamSeasonRepository->existsInRoster($data['player_id'], $teamSeason->id)) {
                throw new ConflictHttpException('Player is already assigned to this team season roster.');
            }

            $assignment = $this->playerTeamSeasonRepository->create([
                'player_id' => $data['player_id'],
                'team_season_id' => $teamSeason->id,
                'number' => $data['number'],
                'is_started' => $data['is_started'] ?? false,
            ]);

            Player::query()
                ->whereKey($data['player_id'])
                ->update(['team_id' => $team->id]);

            return $this->formatAssignment($assignment->load('player.position'));
        });
    }

    public function update(Team $team, Season $season, Player $player, array $data, ?int $leagueId): array
    {
        $teamSeason = $this->teamSeasonRepository->findByTeamAndSeasonOrFail(
            $team->id,
            $season->id,
            $leagueId,
        );

        $assignment = $this->playerTeamSeasonRepository->findInRoster($player->id, $teamSeason->id);

        if ($assignment === null) {
            throw (new ModelNotFoundException)->setModel(Player::class, [$player->id]);
        }

        $assignment = $this->playerTeamSeasonRepository->update($assignment, $data);

        return $this->formatAssignment($assignment);
    }

    public function remove(Team $team, Season $season, Player $player, ?int $leagueId): void
    {
        DB::transaction(function () use ($team, $season, $player, $leagueId) {
            $teamSeason = $this->teamSeasonRepository->findByTeamAndSeasonOrFail(
                $team->id,
                $season->id,
                $leagueId,
            );

            $assignment = $this->playerTeamSeasonRepository->findInRoster($player->id, $teamSeason->id);

            if ($assignment === null) {
                throw (new ModelNotFoundException)->setModel(Player::class, [$player->id]);
            }

            $this->playerTeamSeasonRepository->delete($assignment);

            $stillInSeason = $player->playerTeamSeasons()
                ->whereHas('teamSeason', fn ($query) => $query->where('season_id', $season->id))
                ->exists();

            if (! $stillInSeason && $player->team_id === $team->id) {
                $player->update(['team_id' => null]);
            }
        });
    }

    private function resolveTeamSeason(Team $team, Season $season, array $data): TeamSeason
    {
        $lineupId = $data['lineup_id'] ?? Lineup::query()->value('id');

        if ($lineupId === null) {
            $lineup = Lineup::query()->create(['name' => '4-4-2']);
            $lineupId = $lineup->id;
        }

        return $this->teamSeasonRepository->findOrCreate(
            [
                'team_id' => $team->id,
                'season_id' => $season->id,
                'league_id' => $data['league_id'],
            ],
            ['lineup_id' => $lineupId],
        );
    }

    private function formatAssignment($playerTeamSeason): array
    {
        $player = $playerTeamSeason->player;

        return [
            'id' => $playerTeamSeason->id,
            'player_id' => $player->id,
            'number' => $playerTeamSeason->number,
            'is_started' => $playerTeamSeason->is_started,
            'player' => [
                'id' => $player->id,
                'name' => $player->name,
                'lastname' => $player->lastname,
                'nickname' => $player->nickname,
                'slug' => $player->slug,
                'photo' => $player->photo,
                'position' => $player->position,
            ],
        ];
    }
}
