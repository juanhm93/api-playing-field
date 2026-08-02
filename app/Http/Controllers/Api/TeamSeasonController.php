<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Team;
use App\Services\TeamSeasonService;
use Illuminate\Http\Request;

class TeamSeasonController extends Controller
{
    public function __construct(
        private readonly TeamSeasonService $teamSeasonService,
    ) {}

    public function show(Request $request, Team $team, Season $season)
    {
        $validated = $request->validate([
            'format' => 'nullable|in:clear',
            'league_id' => 'nullable|integer|exists:leagues,id',
        ]);

        $data = $this->teamSeasonService->getTeamBySeason(
            $team,
            $season,
            isset($validated['league_id']) ? (int) $validated['league_id'] : null,
            $validated['format'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Team season fetched successfully',
            'data' => $data,
        ], 200);
    }
}
