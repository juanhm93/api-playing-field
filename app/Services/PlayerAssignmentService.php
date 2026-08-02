<?php

namespace App\Services;

use App\Models\Player;
use App\Models\PlayerTeamSeason;
use App\Models\TeamSeason;
use App\Repositories\Contracts\PlayerTeamSeasonRepositoryInterface;
use App\Repositories\Contracts\TeamSeasonRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlayerAssignmentService
{
    public function __construct(
        private readonly PlayerTeamSeasonRepositoryInterface $assignmentRepository,
        private readonly TeamSeasonRepositoryInterface $teamSeasonRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        $rows = $this->assignmentRepository->list($filters);

        return [
            'success' => $rows->isNotEmpty(),
            'message' => 'Assignments fetched successfully',
            'data' => $rows,
            'status' => 200,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(int $id): array
    {
        $assignment = $this->assignmentRepository->findById($id);

        if ($assignment === null) {
            throw (new ModelNotFoundException)->setModel(PlayerTeamSeason::class, [$id]);
        }

        return [
            'success' => true,
            'message' => 'Assignment fetched successfully',
            'data' => $assignment,
            'status' => 200,
        ];
    }

    /**
     * Primera asignación de un jugador a un equipo en una temporada.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function assign(array $data): array
    {
        $teamSeason = $this->requireTeamSeason((int) $data['team_season_id']);
        $player = $this->requirePlayer((int) $data['player_id']);

        if ($this->assignmentRepository->findByPlayerAndTeamSeason($player->id, $teamSeason->id)) {
            throw new InvalidArgumentException('Player is already assigned to this team-season.');
        }

        $existingInSeason = $this->assignmentRepository->findByPlayerAndSeason($player->id, $teamSeason->season_id);
        if ($existingInSeason->isNotEmpty()) {
            throw new InvalidArgumentException(
                'Player already has a squad assignment in this season. Use the transfer endpoint instead.'
            );
        }

        $this->assertUniqueJersey($teamSeason->id, (int) ($data['number'] ?? 0));

        $assignment = DB::transaction(function () use ($data, $player, $teamSeason) {
            $created = $this->assignmentRepository->create([
                'player_id' => $player->id,
                'team_season_id' => $teamSeason->id,
                'number' => (int) ($data['number'] ?? 0),
                'is_started' => (bool) ($data['is_started'] ?? false),
            ]);

            $player->update(['team_id' => $teamSeason->team_id]);

            return $this->assignmentRepository->findById($created->id);
        });

        return [
            'success' => true,
            'message' => 'Player assigned successfully',
            'data' => $assignment,
            'status' => 201,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $assignment = $this->assignmentRepository->findById($id);

        if ($assignment === null) {
            throw (new ModelNotFoundException)->setModel(PlayerTeamSeason::class, [$id]);
        }

        if (array_key_exists('number', $data)) {
            $this->assertUniqueJersey(
                $assignment->team_season_id,
                (int) $data['number'],
                $assignment->id
            );
        }

        $payload = [];
        if (array_key_exists('number', $data)) {
            $payload['number'] = (int) $data['number'];
        }
        if (array_key_exists('is_started', $data)) {
            $payload['is_started'] = (bool) $data['is_started'];
        }

        $updated = $this->assignmentRepository->update($assignment, $payload);

        return [
            'success' => true,
            'message' => 'Assignment updated successfully',
            'data' => $updated,
            'status' => 200,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function unassign(int $id): array
    {
        $assignment = $this->assignmentRepository->findById($id);

        if ($assignment === null) {
            throw (new ModelNotFoundException)->setModel(PlayerTeamSeason::class, [$id]);
        }

        DB::transaction(function () use ($assignment) {
            $player = $assignment->player;
            $teamId = $assignment->teamSeason?->team_id;

            $this->assignmentRepository->delete($assignment);

            if ($player && $teamId !== null && (int) $player->team_id === (int) $teamId) {
                $player->update(['team_id' => null]);
            }
        });

        return [
            'success' => true,
            'message' => 'Player unassigned successfully',
            'data' => null,
            'status' => 200,
        ];
    }

    /**
     * Transfiere un jugador de un team_season a otro.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transfer(array $data): array
    {
        $player = $this->requirePlayer((int) $data['player_id']);
        $from = $this->requireTeamSeason((int) $data['from_team_season_id']);
        $to = $this->requireTeamSeason((int) $data['to_team_season_id']);

        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Source and destination team-season must be different.');
        }

        $current = $this->assignmentRepository->findByPlayerAndTeamSeason($player->id, $from->id);
        if ($current === null) {
            throw new InvalidArgumentException('Player is not assigned to the source team-season.');
        }

        if ($this->assignmentRepository->findByPlayerAndTeamSeason($player->id, $to->id)) {
            throw new InvalidArgumentException('Player is already assigned to the destination team-season.');
        }

        $number = array_key_exists('number', $data) ? (int) $data['number'] : (int) $current->number;
        $isStarted = array_key_exists('is_started', $data) ? (bool) $data['is_started'] : false;

        $this->assertUniqueJersey($to->id, $number);

        $created = DB::transaction(function () use ($player, $current, $to, $number, $isStarted) {
            $this->assignmentRepository->delete($current);

            $newAssignment = $this->assignmentRepository->create([
                'player_id' => $player->id,
                'team_season_id' => $to->id,
                'number' => $number,
                'is_started' => $isStarted,
            ]);

            $player->update(['team_id' => $to->team_id]);

            return $this->assignmentRepository->findById($newAssignment->id);
        });

        return [
            'success' => true,
            'message' => 'Player transferred successfully',
            'data' => $created,
            'status' => 200,
        ];
    }

    private function requireTeamSeason(int $id): TeamSeason
    {
        $teamSeason = $this->teamSeasonRepository->findById($id);

        if ($teamSeason === null) {
            throw (new ModelNotFoundException)->setModel(TeamSeason::class, [$id]);
        }

        return $teamSeason;
    }

    private function requirePlayer(int $id): Player
    {
        $player = Player::query()->find($id);

        if ($player === null) {
            throw (new ModelNotFoundException)->setModel(Player::class, [$id]);
        }

        return $player;
    }

    private function assertUniqueJersey(int $teamSeasonId, int $number, ?int $exceptAssignmentId = null): void
    {
        if ($number <= 0) {
            return;
        }

        $taken = $this->assignmentRepository->jerseyNumbersForTeamSeason($teamSeasonId, $exceptAssignmentId);

        if ($taken->contains($number)) {
            throw new InvalidArgumentException("Jersey number {$number} is already taken in this squad.");
        }
    }
}
