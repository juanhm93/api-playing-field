<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Services\PlayerAssignmentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PlayerAssignmentController extends Controller
{
    public function __construct(
        private readonly PlayerAssignmentService $playerAssignmentService,
    ) {}

    public function index(Request $request, Team $team, Season $season)
    {
        $validated = $request->validate([
            'league_id' => 'nullable|integer|exists:leagues,id',
        ]);

        $data = $this->playerAssignmentService->list(
            $team,
            $season,
            isset($validated['league_id']) ? (int) $validated['league_id'] : null,
        );

        return response()->json([
            'success' => $data->isNotEmpty(),
            'message' => 'Team season players fetched successfully',
            'data' => $data,
        ], 200);
    }

    public function store(Request $request, Team $team, Season $season)
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:players,id',
            'number' => 'required|integer|min:1|max:99',
            'is_started' => 'sometimes|boolean',
            'league_id' => 'required|integer|exists:leagues,id',
            'lineup_id' => 'nullable|integer|exists:lineups,id',
        ]);

        try {
            $data = $this->playerAssignmentService->assign($team, $season, $validated);
        } catch (ConflictHttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Player assigned to team season successfully',
            'data' => $data,
        ], 201);
    }

    public function show(Request $request, Team $team, Season $season, Player $player)
    {
        $validated = $request->validate([
            'league_id' => 'nullable|integer|exists:leagues,id',
        ]);

        $data = $this->playerAssignmentService->show(
            $team,
            $season,
            $player,
            isset($validated['league_id']) ? (int) $validated['league_id'] : null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Team season player fetched successfully',
            'data' => $data,
        ], 200);
    }

    public function update(Request $request, Team $team, Season $season, Player $player)
    {
        $validated = $request->validate([
            'number' => 'sometimes|integer|min:1|max:99',
            'is_started' => 'sometimes|boolean',
            'league_id' => 'nullable|integer|exists:leagues,id',
        ]);

        $leagueId = isset($validated['league_id']) ? (int) $validated['league_id'] : null;
        unset($validated['league_id']);

        $data = $this->playerAssignmentService->update(
            $team,
            $season,
            $player,
            $validated,
            $leagueId,
        );

        return response()->json([
            'success' => true,
            'message' => 'Team season player updated successfully',
            'data' => $data,
        ], 200);
    }

    public function destroy(Request $request, Team $team, Season $season, Player $player)
    {
        $validated = $request->validate([
            'league_id' => 'nullable|integer|exists:leagues,id',
        ]);

        $this->playerAssignmentService->remove(
            $team,
            $season,
            $player,
            isset($validated['league_id']) ? (int) $validated['league_id'] : null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Player removed from team season successfully',
            'data' => null,
        ], 200);
    }
}
