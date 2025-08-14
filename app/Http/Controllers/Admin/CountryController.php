<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryMediaGallery;
use App\Models\CountryLocationDetail;
use App\Models\CountryTravelInfo;
use App\Models\CountrySeason;
use App\Models\CountryEvent;
use App\Models\CountryAdditionalInfo;
use App\Models\CountryFaq;
use App\Models\CountrySeo;
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
        $validated = $request->validate([
            // Country fields
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:10',
            // 'slug' => 'required|string|max:255|unique:countries,slug',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'feature_image' => 'nullable|url',
            'featured_destination' => 'boolean',

            // Media (array of objects)
            'media_gallery'     => 'nullable|array',

            // Location Details
            'location.latitude' => 'nullable|string',
            'location.longitude' => 'nullable|string',
            'location.capital_city' => 'nullable|string',
            'location.population' => 'nullable|integer',
            'location.currency' => 'nullable|string',
            'location.timezone' => 'nullable|string',
            'location.language' => 'nullable|array',
            'location.local_cuisine' => 'nullable|array',

            // Travel Info
            'travel.airport' => 'nullable|string',
            'travel.public_transportation' => 'nullable|string',
            'travel.taxi_available' => 'boolean',
            'travel.rental_cars_available' => 'boolean',
            'travel.hotels' => 'boolean',
            'travel.hostels' => 'boolean',
            'travel.apartments' => 'boolean',
            'travel.resorts' => 'boolean',
            'travel.visa_requirements' => 'nullable|string',
            'travel.best_time_to_visit' => 'nullable|string',
            'travel.travel_tips' => 'nullable|string',
            'travel.safety_information' => 'nullable|string',

            // Season (array of objects)
            'seasons' => 'nullable|array',
            'seasons.*.name' => 'nullable|string',
            'seasons.*.months' => 'nullable|array',
            'seasons.*.weather' => 'nullable|string',
            'seasons.*.activities' => 'nullable|array',

            // Event (array of objects)
            'events' => 'nullable|array',
            'events.*.name' => 'nullable|string',
            'events.*.type' => 'nullable|array',
            'events.*.date_time' => 'nullable|date',
            'events.*.location' => 'nullable|array',
            'events.*.description' => 'nullable|string',

            // Additional Info
            'additional' => 'nullable|array',
            'additional.*.title' => 'required|string',
            'additional.*.content' => 'required|string',

            // FAQs
            'faqs' => 'array',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',

            // SEO
            'seo.meta_title' => 'nullable|string',
            'seo.meta_description' => 'nullable|string',
            'seo.keywords' => 'nullable|string',
            'seo.og_image_url' => 'nullable|url',
            'seo.canonical_url' => 'nullable|url',
            'seo.schema_type' => 'nullable|string',
            'seo.schema_data' => 'nullable|array',
        ]);

        $exists = Country::where('name', $request->name)
            ->orWhere('slug', $request->slug)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This country already exists, please choose another name.'
            ], 422); // 422 = Unprocessable Entity (validation error)
        }
        // Create Country
        $country = Country::create([
            'name' => $validated['name'],
            'country_code' => $validated['country_code'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'feature_image' => $validated['feature_image'] ?? null,
            'featured_destination' => $validated['featured_destination'] ?? false,
        ]);

        // Media Details
        if (!empty($validated['media_gallery'])) {
            foreach ($validated['media_gallery'] as $media) {
                CountryMediaGallery::create([
                    'country_id' => $country->id,
                    'media_id'    => $media['media_id'],
                ]);
            }
        }

        // Location Details
        if (!empty($validated['location'])) {
            $validated['location']['country_id'] = $country->id;
            CountryLocationDetail::create($validated['location']);
        }

        // Travel Info
        if (!empty($validated['travel'])) {
            $validated['travel']['country_id'] = $country->id;
            CountryTravelInfo::create($validated['travel']);
        }

        // Season
        if ($request->has('seasons')) {
            foreach ($request->seasons as $season) {
                $country->seasons()->create($season);
            }
        }

        // Event
        if ($request->has('events')) {
            foreach ($request->events as $event) {
                $country->events()->create($event);
            }
        }

        // Additional Info
        if (!empty($validated['additional'])) {
            foreach ($validated['additional'] as $additional) {
                $additional['country_id'] = $country->id;
                CountryAdditionalInfo::create($additional);
            }
        }

        // FAQs
        if (!empty($validated['faqs'])) {
            $questionNumber = 1;
            foreach ($validated['faqs'] as $faq) {
                CountryFaq::create([
                    'country_id' => $country->id,
                    'question_number' => $questionNumber++,
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ]);
            }
        }

        // SEO
        if (!empty($validated['seo'])) {
            $validated['seo']['country_id'] = $country->id;
            CountrySeo::create($validated['seo']);
        }

        return response()->json([
            'message' => 'Country created successfully',
            'country' => $country
        ], 201);
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
