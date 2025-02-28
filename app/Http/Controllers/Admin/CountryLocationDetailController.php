<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CountryLocationDetail;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryLocationDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
        //
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $id)
    {
        $validatedData = $request->validate([
            // 'country_id' => 'required|exists:countries,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capital_city' => 'nullable|string',
            'population' => 'nullable|integer',
            'currency' => 'nullable|string',
            'timezone' => 'nullable|string',
            'language' => 'nullable|string',
            'local_cuisine' => 'nullable|string',
        ]);

        // Automatically assign country_id from the URL
        $validatedData['country_id'] = $id;

        $locationDetail = CountryLocationDetail::updateOrCreate(
            ['country_id' => $id],
            $validatedData
        );

        return response()->json([
            'message' => 'Location details stored successfully!',
            'data' => $locationDetail
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $details = CountryLocationDetail::where('country_id', $id)->first();

        if (!$details) {
            return response()->json(['message' => 'Details not found'], 404);
        }

        return response()->json($details);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $details = CountryLocationDetail::where('country_id', $id)->first();

        if (!$details) {
            return response()->json(['message' => 'Details not found'], 404);
        }

        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capital_city' => 'nullable|string',
            'population' => 'nullable|integer',
            'currency' => 'nullable|string',
            'timezone' => 'nullable|string',
            'language' => 'nullable|string',
            'local_cuisine' => 'nullable|string',
        ]);

        $details->update($request->all());

        return response()->json([
            'message' => 'Location details updated successfully!',
            'data' => $details
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $details = CountryLocationDetail::where('country_id', $id)->first();

        if (!$details) {
            return response()->json(['message' => 'Details not found'], 404);
        }

        $details->delete();

        return response()->json(['message' => 'Location details deleted successfully']);
    }
}
