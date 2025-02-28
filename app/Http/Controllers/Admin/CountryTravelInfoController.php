<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryTravelInfo;
use Illuminate\Http\Request;

class CountryTravelInfoController extends Controller
{
    public function store(Request $request, $id)
    {
        $validatedData = $request->validate([
            'airport' => 'nullable|string',
            'public_transportation' => 'nullable|string',
            'taxi_available' => 'boolean',
            'rental_cars_available' => 'boolean',
            'hotels' => 'boolean',
            'hostels' => 'boolean',
            'apartments' => 'boolean',
            'resorts' => 'boolean',
            'visa_requirements' => 'nullable|string',
            'best_time_to_visit' => 'nullable|string',
            'travel_tips' => 'nullable|string',
            'safety_information' => 'nullable|string',
        ]);

        $validatedData['country_id'] = $id;

        $travelInfo = CountryTravelInfo::updateOrCreate(
            ['country_id' => $id],
            $validatedData
        );

        return response()->json([
            'message' => 'Travel information stored successfully!',
            'data' => $travelInfo
        ], 201);
    }

    public function show($id)
    {
        $travelInfo = CountryTravelInfo::where('country_id', $id)->first();

        if (!$travelInfo) {
            return response()->json(['message' => 'No travel information found!'], 404);
        }

        return response()->json($travelInfo);
    }

    public function update(Request $request, string $id)
    {
        $travelInfo = CountryTravelInfo::where('country_id', $id)->first();

        if (!$travelInfo) {
            return response()->json(['message' => 'Travel information not found'], 404);
        }

        $request->validate([
            'airport' => 'nullable|string',
            'public_transportation' => 'nullable|string',
            'taxi_available' => 'boolean',
            'rental_cars_available' => 'boolean',
            'hotels' => 'boolean',
            'hostels' => 'boolean',
            'apartments' => 'boolean',
            'resorts' => 'boolean',
            'visa_requirements' => 'nullable|string',
            'best_time_to_visit' => 'nullable|string',
            'travel_tips' => 'nullable|string',
            'safety_information' => 'nullable|string',
        ]);

        $travelInfo->update($request->all());

        return response()->json([
            'message' => 'Travel information updated successfully!',
            'data' => $travelInfo
        ]);
    }

    public function destroy($id)
    {
        $travelInfo = CountryTravelInfo::where('country_id', $id)->first();

        if (!$travelInfo) {
            return response()->json(['message' => 'No travel information found!'], 404);
        }

        $travelInfo->delete();

        return response()->json(['message' => 'Travel information deleted successfully!']);
    }
}
