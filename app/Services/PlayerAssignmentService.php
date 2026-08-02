<?php

namespace App\Services;

use App\Models\Player;
use App\Models\PlayerTeamSeason;
use App\Repositories\Contracts\PlayerTeamSeasonRepositoryInterface;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PlayerAssignmentService
{
    public function __construct(
        private readonly PlayerTeamSeasonRepositoryInterface $assignmentRepository,
        private readonly TeamSeasonRepositoryInterface $teamSeasonRepository,
    ) {}

    /**
     * @return Collection<int, PlayerTeamSeason>
     */
    public function list(array $filters = []): Collection
    {
        return $this->assignmentRepository->list($filters);
    }

    public function show(int $id): PlayerTeamSeason
    {
        $assignment = $this->assignmentRepository->findById($id);

        if ($assignment === null) {
            throw new NotFoundHttpException('Player team season assignment not found.');
        }

        return $assignment;
    }

    /**
     * First-time (or new) assignment of a player to a team for a season.
     */
    public function assign(array $data): PlayerTeamSeason
    {
        return DB::transaction(function () use ($data) {
            $teamSeason = $this->resolveTeamSeason($data);

            $existing = $this->assignmentRepository->findByPlayerAndTeamSeason(
                (int) $data['player_id'],
                $teamSeason->id
            );

            if ($existing !== null) {
                throw new ConflictHttpException('Player is already assigned to this team for the season.');
            }

            $sameSeasonElsewhere = $this->assignmentRepository->list([
                'player_id' => $data['player_id'],
                'season_id' => $data['season_id'],
            ]);

            if ($sameSeasonElsewhere->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'player_id' => ['Player already has a team assignment for this season. Use the transfer endpoint instead.'],
                ]);
            }

            $assignment = $this->assignmentRepository->create([
                'player_id' => $data['player_id'],
                'team_season_id' => $teamSeason->id,
                'number' => $data['number'] ?? 0,
                'is_started' => $data['is_started'] ?? false,
            ]);

            $this->syncPlayerCurrentTeam((int) $data['player_id'], (int) $data['team_id']);

            return $assignment;
        });
    }

    public function update(int $id, array $data): PlayerTeamSeason
    {
        $assignment = $this->show($id);

        $payload = array_filter([
            'number' => $data['number'] ?? null,
            'is_started' => array_key_exists('is_started', $data) ? (bool) $data['is_started'] : null,
        ], fn ($value) => $value !== null);

        return $this->assignmentRepository->update($assignment, $payload);
    }

    public function remove(int $id): PlayerTeamSeason
    {
        $assignment = $this->show($id);

        $this->assignmentRepository->delete($assignment);

        return $assignment;
    }

    /**
     * Move a player from one team to another within the same season.
     */
    public function transfer(array $data): PlayerTeamSeason
    {
        if ((int) $data['from_team_id'] === (int) $data['to_team_id']) {
            throw ValidationException::withMessages([
                'to_team_id' => ['Destination team must be different from the origin team.'],
            ]);
        }

        return DB::transaction(function () use ($data) {
            $origin = $this->assignmentRepository->findByPlayerTeamAndSeason(
                (int) $data['player_id'],
                (int) $data['from_team_id'],
                (int) $data['season_id']
            );

            if ($origin === null) {
                throw new NotFoundHttpException('Player is not assigned to the origin team for this season.');
            }

            $destinationTeamSeason = $this->resolveTeamSeason([
                'team_id' => $data['to_team_id'],
                'season_id' => $data['season_id'],
                'league_id' => $data['league_id'] ?? $origin->teamSeason->league_id,
                'lineup_id' => $data['lineup_id'] ?? $origin->teamSeason->lineup_id,
            ]);

            $alreadyThere = $this->assignmentRepository->findByPlayerAndTeamSeason(
                (int) $data['player_id'],
                $destinationTeamSeason->id
            );

            if ($alreadyThere !== null) {
                throw new ConflictHttpException('Player is already assigned to the destination team for this season.');
            }

            $this->assignmentRepository->delete($origin);

            $assignment = $this->assignmentRepository->create([
                'player_id' => $data['player_id'],
                'team_season_id' => $destinationTeamSeason->id,
                'number' => $data['number'] ?? $origin->number,
                'is_started' => $data['is_started'] ?? false,
            ]);

            $this->syncPlayerCurrentTeam((int) $data['player_id'], (int) $data['to_team_id']);

            return $assignment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveTeamSeason(array $data)
    {
        $existing = $this->teamSeasonRepository->findByTeamAndSeason(
            (int) $data['team_id'],
            (int) $data['season_id']
        );

        if ($existing !== null) {
            return $existing;
        }

        if (empty($data['league_id']) || empty($data['lineup_id'])) {
            throw ValidationException::withMessages([
                'league_id' => ['league_id and lineup_id are required when the team is not yet registered for the season.'],
                'lineup_id' => ['league_id and lineup_id are required when the team is not yet registered for the season.'],
            ]);
        }

        return $this->teamSeasonRepository->findOrCreate([
            'team_id' => $data['team_id'],
            'season_id' => $data['season_id'],
            'league_id' => $data['league_id'],
            'lineup_id' => $data['lineup_id'],
        ]);
    }

    private function syncPlayerCurrentTeam(int $playerId, int $teamId): void
    {
        Player::query()->whereKey($playerId)->update(['team_id' => $teamId]);
    }
}
