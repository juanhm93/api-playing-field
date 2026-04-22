<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teams = Team::query()->withCount('players')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Teams fetched successfully',
            'data' => $teams,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:teams,slug',
            'code' => 'nullable|string|max:255',
            'logo' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'players' => 'required|array|min:1',
            'players.*.name' => 'required|string|max:255',
            'players.*.lastname' => 'nullable|string|max:255',
            'players.*.nickname' => 'nullable|string|max:255',
            'players.*.slug' => 'nullable|string|max:255|distinct|unique:players,slug',
            'players.*.code' => 'nullable|string|max:255',
            'players.*.photo' => 'nullable|string|max:255',
            'players.*.date_of_birth' => 'nullable|date',
            'players.*.nationality' => 'nullable|string|max:255',
            'players.*.height' => 'nullable|string|max:255',
            'players.*.weight' => 'nullable|string|max:255',
            'players.*.foot' => 'nullable|string|max:255',
            'players.*.position_id' => 'required|exists:positions,id',
        ]);

        $playersInput = $validated['players'];
        unset($validated['players']);

        $team = DB::transaction(function () use ($validated, $playersInput) {
            $team = Team::query()->create($validated);

            foreach ($playersInput as $row) {
                if (empty($row['slug'])) {
                    $row['slug'] = Str::slug(($row['name'] ?? '').' '.($row['lastname'] ?? '').' '.uniqid());
                }

                $row['team_id'] = $team->id;

                Player::query()->create($row);
            }

            return $team->load(['players.position']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Team created successfully',
            'data' => $team,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $team->load(['players.position']);

        return response()->json([
            'success' => true,
            'message' => 'Team fetched successfully',
            'data' => $team,
        ], 200);
    }
}
