<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryAdditionalInfo;
use Illuminate\Http\Request;

class CountryAdditionalInfoController extends Controller
{
    // Get all additional info for a country
    public function index($id) {
        $info = CountryAdditionalInfo::where('country_id', $id)->get();
        return response()->json($info);
    }

    // Get a specific additional info record
    public function show($id, $infoId) {
        $info = CountryAdditionalInfo::where('country_id', $id)->findOrFail($infoId);
        return response()->json($info);
    }

    // Create a new additional info record
    public function store(Request $request, $id) {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $validatedData['country_id'] = $id;

        $info = CountryAdditionalInfo::create($validatedData);

        return response()->json([
            'message' => 'Additional info created successfully!',
            'data' => $info
        ], 201);
    }

    // Update a specific additional info record
    public function update(Request $request, $id, $infoId) {
        $info = CountryAdditionalInfo::where('country_id', $id)->findOrFail($infoId);

        $request->validate([
            'title' => 'sometimes|string',
            'content' => 'sometimes|string',
        ]);

        $info->update($request->only(['title', 'content']));

        return response()->json([
            'message' => 'Additional info updated successfully!',
            'data' => $info
        ]);
    }

    // Delete a specific additional info record
    public function destroy($id, $infoId) {
        $info = CountryAdditionalInfo::where('country_id', $id)->findOrFail($infoId);
        $info->delete();

        return response()->json(['message' => 'Additional info deleted successfully!']);
    }
}
