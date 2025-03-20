<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use App\Models\Country;

class PublicCitiesController extends Controller
{
    public function getCitiesByState($country_slug, $state_slug)
    {
        $country = Country::where('slug', $country_slug)->first();
        if (!$country) {
            return response()->json(['success' => false, 'message' => 'Country not found'], 404);
        }

        $state = State::where('slug', $state_slug)->where('country_id', $country->id)->first();
        if (!$state) {
            return response()->json(['success' => false, 'message' => 'State not found'], 404);
        }

        $cities = City::where('state_id', $state->id)->get();

        if (empty($cities)) {
            return response()->json([
                'success' => false,
                'message' => 'Cities not found'
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }

    public function getFeaturedCities()
    {
        $cities = City::with([
            'state.country.regions'
        ])
        ->where('featured_destination', true)
        ->get()
        ->map(function ($city) {
            return [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'description' => $city->description,
                'featured_image' => $city->featured_image,
                'state' => [
                    'id' => $city->state->id ?? null,
                    'name' => $city->state->name ?? null,
                ],
                'country' => [
                    'id' => $city->state->country->id ?? null,
                    'name' => $city->state->country->name ?? null,
                ],
                'region' => $city->state->country->regions->map(function ($region) {
                    return [
                        'id' => $region->id,
                        'name' => $region->name,
                    ];
                })
            ];
        });

        if ($cities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No featured cities found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }

}

