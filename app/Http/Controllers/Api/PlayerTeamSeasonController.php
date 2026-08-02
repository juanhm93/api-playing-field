<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlayerAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerTeamSeasonController extends Controller
{
    public function __construct(
        private readonly PlayerAssignmentService $playerAssignmentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'player_id' => 'sometimes|integer|exists:players,id',
            'team_id' => 'sometimes|integer|exists:teams,id',
            'season_id' => 'sometimes|integer|exists:seasons,id',
            'team_season_id' => 'sometimes|integer|exists:team_season,id',
        ]);

        $assignments = $this->playerAssignmentService->list($filters);

        return response()->json([
            'success' => true,
            'message' => 'Player team season assignments fetched successfully',
            'data' => $assignments,
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:players,id',
            'team_id' => 'required|integer|exists:teams,id',
            'season_id' => 'required|integer|exists:seasons,id',
            'league_id' => 'nullable|integer|exists:leagues,id',
            'lineup_id' => 'nullable|integer|exists:lineups,id',
            'number' => 'nullable|integer|min:0|max:99',
            'is_started' => 'nullable|boolean',
        ]);

        $assignment = $this->playerAssignmentService->assign($validated);

        return response()->json([
            'success' => true,
            'message' => 'Player assigned to team season successfully',
            'data' => $assignment,
        ], 201);
    }

    public function show(int $playerTeamSeason): JsonResponse
    {
        $assignment = $this->playerAssignmentService->show($playerTeamSeason);

        return response()->json([
            'success' => true,
            'message' => 'Player team season assignment fetched successfully',
            'data' => $assignment,
        ], 200);
    }

    public function update(Request $request, int $playerTeamSeason): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'sometimes|integer|min:0|max:99',
            'is_started' => 'sometimes|boolean',
        ]);

        $assignment = $this->playerAssignmentService->update($playerTeamSeason, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Player team season assignment updated successfully',
            'data' => $assignment,
        ], 200);
    }

    public function destroy(int $playerTeamSeason): JsonResponse
    {
        $assignment = $this->playerAssignmentService->remove($playerTeamSeason);

        return response()->json([
            'success' => true,
            'message' => 'Player team season assignment deleted successfully',
            'data' => $assignment,
        ], 200);
    }
}
