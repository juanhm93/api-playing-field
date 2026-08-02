<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlayerAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerTransferController extends Controller
{
    public function __construct(
        private readonly PlayerAssignmentService $playerAssignmentService,
    ) {}

    /**
     * Transfer a player from one team to another within a season.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:players,id',
            'from_team_id' => 'required|integer|exists:teams,id',
            'to_team_id' => 'required|integer|exists:teams,id|different:from_team_id',
            'season_id' => 'required|integer|exists:seasons,id',
            'league_id' => 'nullable|integer|exists:leagues,id',
            'lineup_id' => 'nullable|integer|exists:lineups,id',
            'number' => 'nullable|integer|min:0|max:99',
            'is_started' => 'nullable|boolean',
        ]);

        $assignment = $this->playerAssignmentService->transfer($validated);

        return response()->json([
            'success' => true,
            'message' => 'Player transferred successfully',
            'data' => $assignment,
        ], 201);
    }
}
