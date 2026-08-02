<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Team;
use App\Services\TeamSeasonService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TeamSeasonController extends Controller
{
    public function __construct(
        private readonly TeamSeasonService $teamSeasonService,
    ) {}

    /**
     * Equipo + plantilla por temporada.
     * Default: starters / substitutes.
     * format=clear: un solo array players.
     */
    public function show(Request $request, Team $team, Season $season): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'sometimes|string|in:default,clear',
            'league_id' => 'sometimes|integer|exists:leagues,id',
        ]);

        $format = $validated['format'] ?? 'default';
        $leagueId = isset($validated['league_id']) ? (int) $validated['league_id'] : null;

        try {
            $data = $this->teamSeasonService->getTeamSquadBySeason(
                $team->id,
                $season->id,
                $leagueId,
                $format
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Team-season not found for the given team and season',
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
            'success' => true,
            'message' => 'Team squad fetched successfully',
            'data' => $data,
        ], 200);
    }
}
