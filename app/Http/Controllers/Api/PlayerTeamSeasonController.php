<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlayerAssignmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PlayerTeamSeasonController extends Controller
{
    public function __construct(
        private readonly PlayerAssignmentService $playerAssignmentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'player_id' => 'sometimes|integer|exists:players,id',
            'team_season_id' => 'sometimes|integer|exists:team_season,id',
            'team_id' => 'sometimes|integer|exists:teams,id',
            'season_id' => 'sometimes|integer|exists:seasons,id',
            'league_id' => 'sometimes|integer|exists:leagues,id',
        ]);

        $result = $this->playerAssignmentService->list($filters);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['status']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:players,id',
            'team_season_id' => 'required|integer|exists:team_season,id',
            'number' => 'sometimes|integer|min:0|max:99',
            'is_started' => 'sometimes|boolean',
        ]);

        return $this->respondFromService(
            fn () => $this->playerAssignmentService->assign($validated)
        );
    }

    public function show(int $playerTeamSeason): JsonResponse
    {
        return $this->respondFromService(
            fn () => $this->playerAssignmentService->show($playerTeamSeason)
        );
    }

    public function update(Request $request, int $playerTeamSeason): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'sometimes|integer|min:0|max:99',
            'is_started' => 'sometimes|boolean',
        ]);

        return $this->respondFromService(
            fn () => $this->playerAssignmentService->update($playerTeamSeason, $validated)
        );
    }

    public function destroy(int $playerTeamSeason): JsonResponse
    {
        return $this->respondFromService(
            fn () => $this->playerAssignmentService->unassign($playerTeamSeason)
        );
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:players,id',
            'from_team_season_id' => 'required|integer|exists:team_season,id',
            'to_team_season_id' => 'required|integer|exists:team_season,id|different:from_team_season_id',
            'number' => 'sometimes|integer|min:0|max:99',
            'is_started' => 'sometimes|boolean',
        ]);

        return $this->respondFromService(
            fn () => $this->playerAssignmentService->transfer($validated)
        );
    }

    /**
     * @param  callable(): array<string, mixed>  $callback
     */
    private function respondFromService(callable $callback): JsonResponse
    {
        try {
            $result = $callback();
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => class_basename($e->getModel()).' not found',
                'data' => null,
            ], 404);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['status']);
    }
}
