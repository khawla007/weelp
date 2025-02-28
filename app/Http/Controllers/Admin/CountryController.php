<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Country::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:countries,name',
            'country_code' => 'required|string',
            'description' => 'nullable|string',
            'feature_image' => 'nullable|string',
            'featured_destination' => 'boolean'
        ]);

        $slug = Str::slug($request->name, '-');

        $country = Country::create([
            'name' => $request->name,
            'country_code' => $request->country_code,
            'slug' => $slug,
            'description' => $request->description,
            'feature_image' => $request->feature_image,
            'featured_destination' => $request->featured_destination ?? false
        ]);

        return response()->json($country, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // return response()->json(Country::findOrFail($id));
        $country = Country::with([
            'locationDetails',
            'travelInfo',
            'seasons',
            'events',
            'additionalInfo',
            'faqs',
            'seo'
        ])->find($id);
    
        if (!$country) {
            return response()->json(['message' => 'Country not found'], 404);
        }
    
        return response()->json($country);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $country = Country::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:countries,name,' . $id,
            'country_code' => 'required|string',
            'description' => 'nullable|string',
            'feature_image' => 'nullable|string',
            'featured_destination' => 'boolean'
        ]);

        $slug = Str::slug($request->name, '-');

        $country->update([
            'name' => $request->name,
            'country_code' => $request->country_code,
            'slug' => $slug,
            'description' => $request->description,
            'feature_image' => $request->feature_image,
            'featured_destination' => $request->featured_destination ?? false
        ]);

        return response()->json($country);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Country::findOrFail($id)->delete();
        return response()->json(['message' => 'Country deleted successfully']);
    }
}
