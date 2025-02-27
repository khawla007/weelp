<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CountrySeason;
use Illuminate\Http\Request;

class CountrySeasonController extends Controller
{
    // Get all seasons for a country
    public function index($id)
    {
        $seasons = CountrySeason::where('country_id', $id)->get();
        return response()->json($seasons);
    }

    // Create or update a season
    public function store(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'months' => 'required|string',
            'weather' => 'required|string',
            'activities' => 'required|array',
        ]);

        $validatedData['country_id'] = $id;
        $validatedData['activities'] = json_encode($validatedData['activities']);

        $season = CountrySeason::updateOrCreate(
            ['country_id' => $id, 'name' => $validatedData['name']],
            $validatedData
        );

        return response()->json([
            'message' => 'Season stored successfully!',
            'data' => $season
        ], 201);
    }

    // Get single season details
    public function show($id, $seasonId)
    {
        $season = CountrySeason::where('country_id', $id)->findOrFail($seasonId);
        return response()->json($season);
    }

    // Update a season (only provided fields)
    public function update(Request $request, $id, $seasonId)
    {
        $season = CountrySeason::where('country_id', $id)->findOrFail($seasonId);

        $request->validate([
            'name' => 'sometimes|string',
            'months' => 'sometimes|string',
            'weather' => 'sometimes|string',
            'activities' => 'sometimes|array',
        ]);

        if ($request->has('activities')) {
            $request['activities'] = json_encode($request->activities);
        }

        $season->update($request->only(array_keys($request->all())));

        return response()->json([
            'message' => 'Season updated successfully!',
            'data' => $season
        ]);
    }

    // Delete a season
    public function destroy($id, $seasonId)
    {
        $season = CountrySeason::where('country_id', $id)->findOrFail($seasonId);
        $season->delete();

        return response()->json(['message' => 'Season deleted successfully!']);
    }
}
