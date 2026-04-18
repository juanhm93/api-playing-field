<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $id = $request->query('id');

        $countries = Country::query();
        if ($id) {
            $countries = $countries->where('id', $id);
        }

        $countries = $countries->get();

        return response()->json([
            "success" => $countries->count() > 0,
            "message" => 'Countries fetched successfully',
            "data" => $countries,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:countries',
            'code' => 'required|string|max:255',
            'flag' => 'nullable|string|max:255',
            'currency' => 'required|string|max:255',
        ]);

        $country = Country::create($request->all());

        return response()->json([
            "success" => $country->wasCreated(),
            "message" => 'Country created successfully',
            "data" => $country,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        return response()->json([
            "success" => true,
            "message" => 'Country fetched successfully',
            "data" => $country,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Country $country)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:countries,slug,' . $country->id,
            'code' => 'required|string|max:255',
            'flag' => 'required|string|max:255',
            'currency' => 'required|string|max:255',
        ]);

        $country->update($request->all());

        return response()->json([
            "success" => $country->wasChanged(),
            "message" => $country->wasChanged() ? 'Country updated successfully' : 'Country not found',
            "data" => $country,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        $country->delete();

        return response()->json([
            "success" => $country->trashed(),
            "message" => $country->trashed() ? 'Country deleted successfully' : 'Country not found',
            "data" => $country,
        ], 200);
    }
}
