<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CityLocationDetail;
use App\Models\CityTravelInfo;
use App\Models\CitySeason;
use App\Models\CityEvent;
use App\Models\CityAdditionalInfo;
use App\Models\CityFaq;
use App\Models\CitySeo;
use Illuminate\Http\Request;
use Validator;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cities = City::with(['locationDetails', 'travelInfo', 'seasons', 'events', 'additionalInfo', 'faqs', 'seo'])->get();
        return response()->json($cities);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         // Validation for incoming data
         $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'state_id' => 'required|integer',
            'city_code' => 'required|string|max:10',
            'slug' => 'required|string|max:255',
            'description' => 'required|string',
            'feature_image' => 'required|url',
            'featured_destination' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Create or update the city
        $city = City::updateOrCreate(
            ['id' => $id],
            $request->only(['state_id', 'name', 'city_code', 'slug', 'description', 'feature_image', 'featured_destination'])
        );

        // Insert or update related data

        // 1️⃣ City Location Details
        CityLocationDetail::updateOrCreate(['city_id' => $city->id], $request->only([
            'latitude', 'longitude', 'population', 'currency', 'timezone', 'language', 'local_cuisine'
        ]));

        // 2️⃣ Travel Information
        CityTravelInfo::updateOrCreate(['city_id' => $city->id], $request->only([
            'airport', 'public_transportation', 'taxi_available', 'rental_cars_available', 'hotels', 'hostels',
            'apartments', 'resorts', 'visa_requirements', 'best_time_to_visit', 'travel_tips', 'safety_information'
        ]));

        // 3️⃣ City Seasons
        CitySeason::updateOrCreate(['city_id' => $city->id], $request->only(['name', 'months', 'weather', 'activities']));

        // 4️⃣ City Events
        foreach ($request->events as $event) {
            CityEvent::updateOrCreate(['id' => $event['id'] ?? null, 'city_id' => $city->id], $event);
        }

        // 5️⃣ Additional Info
        CityAdditionalInfo::updateOrCreate(['city_id' => $city->id], $request->only(['title', 'content']));

        // 6️⃣ FAQs
        foreach ($request->faqs as $faq) {
            CityFaq::updateOrCreate(['id' => $faq['id'] ?? null, 'city_id' => $city->id], $faq);
        }

        // 7️⃣ SEO Data
        CitySeo::updateOrCreate(['city_id' => $city->id], $request->only([
            'meta_title', 'meta_description', 'keywords', 'og_image_url', 'canonical_url', 'schema_data'
        ]));

        return response()->json($city, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $city = City::with(['locationDetail', 'travelInfo', 'seasons', 'events', 'additionalInfo', 'faqs', 'seoData'])->find($id);

        if (!$city) {
            return response()->json(['message' => 'City not found'], 404);
        }

        return response()->json($city);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $city = City::find($id);

        if (!$city) {
            return response()->json(['message' => 'City not found'], 404);
        }

        // Deleting related data before deleting the city
        $city->locationDetail()->delete();
        $city->travelInfo()->delete();
        $city->seasons()->delete();
        $city->events()->delete();
        $city->additionalInfo()->delete();
        $city->faqs()->delete();
        $city->seoData()->delete();

        $city->delete();

        return response()->json(['message' => 'City deleted successfully']);
    }
}
