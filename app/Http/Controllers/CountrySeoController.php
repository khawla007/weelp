<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CountrySeo;
use Illuminate\Http\Request;

class CountrySeoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     //
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $id)
    {
        $validatedData = $request->validate([
            'meta_title'       => 'required|string',
            'meta_description' => 'nullable|string',
            'keywords'         => 'nullable|string',
            'og_image_url'     => 'nullable|string',
            'canonical_url'    => 'nullable|string',
            'schema_type'      => 'nullable|string',
            'schema_data'      => 'nullable|array', // ✅ JSON format accept karega
        ]);

        $validatedData['country_id'] = $id;
        
        // ✅ JSON Encode to store as string (Brackets ke saath)
        if ($request->has('schema_data')) {
            $validatedData['schema_data'] = json_encode($request->schema_data, JSON_UNESCAPED_SLASHES);
        }

        $seo = CountrySeo::updateOrCreate(
            ['country_id' => $id],
            $validatedData
        );

        return response()->json([
            'message' => 'SEO data stored successfully!',
            'data' => $seo
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $seo = CountrySeo::where('country_id', $id)->first();
        return response()->json($seo);
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     //
    // }
}
