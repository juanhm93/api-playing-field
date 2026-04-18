<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\League;
use App\Repositories\BaseApiInterface;

class LeagueController extends Controller
{
    protected $baseApi;
    public function __construct(BaseApiInterface $baseApi)
    {
        $this->baseApi = $baseApi;
    }

    public function index(Request $request)
    {

        $response = $this->baseApi->index($request);
        return $response;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:leagues',
            'code' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
        ]);

        $league = League::create($request->all());

        return response()->json([
            "success" => $league->wasCreated(),
            "message" => 'League created successfully',
            "data" => $league,
        ], 201);
    }

    public function show(League $league)
    {
        return response()->json([
            "success" => true,
            "message" => 'League fetched successfully',
            "data" => $league,
        ], 200);
    }

    public function update(Request $request, League $league)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:leagues,slug,' . $league->id,
            'code' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
        ]);

        $league->update($request->all());

        return response()->json([
            "success" => $league->wasChanged(),
            "message" => $league->wasChanged() ? 'League updated successfully' : 'League not found',
            "data" => $league,
        ], 200);
    }

    public function destroy(League $league)
    {
        $league->delete();

        return response()->json([
            "success" => $league->trashed(),
            "message" => $league->trashed() ? 'League deleted successfully' : 'League not found',
            "data" => $league,
        ], 200);
    }
}
