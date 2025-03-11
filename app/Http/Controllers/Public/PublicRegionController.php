<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Activity;
use App\Models\City;
use Illuminate\Http\Request;

class PublicRegionController extends Controller
{
    public function getCitiesByRegion($region_slug)
    {
        $region = Region::where('name', $region_slug)->firstOrFail();

        if (!$region) {
            return response()->json(['success' => false, 'message' => 'Region not found'], 404);
        }

        $cities = [];
    
        foreach ($region->countries as $country) {
            foreach ($country->states as $state) {
                $cities = array_merge($cities, $state->cities()->get()->toArray());
            }
        }
        
        if (empty($cities)) {
            return response()->json(['error' => 'No cities found in this region'], 404);
        }

        return response()->json($cities);
    }

    public function getActivityByCity($region_slug, $city_slug)
    {
        $city = City::where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        // $activities = $city->activities;
        $activities = $city->activities()->with(['pricing', 'groupDiscounts'])->get();

        if ($activities->isEmpty()) {
            return response()->json(['message' => 'No activities found for this city.'], 404);
        }

        return response()->json($activities);
    }

    public function getItinerariesByCity($region_slug, $city_slug)
    {
        $city = City::where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        // Itineraries ke saath schedules aur related data fetch karo
        $itineraries = $city->itineraries()->with([
            'schedules.activities',
            'schedules.transfers',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery',
            'seo',
            'categories',
            'attributes',
            'tags'
        ])->get();

        if ($itineraries->isEmpty()) {
            return response()->json(['message' => 'No itineraries found for this city.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $itineraries
        ]);
    }

    public function getPlacesByCity($region_slug, $city_slug)
    {
        $region = Region::where('name', $region_slug)->firstOrFail();
        $places = [];
    
        foreach ($region->countries as $country) {
            foreach ($country->states as $state) {
                $city = $state->cities()->where('slug', $city_slug)->first();
                if ($city) {
                    $places = $city->places()->get()->toArray();
                    break;
                }
            }
        }
    
        if (empty($places)) {
            return response()->json(['error' => 'City not found'], 404);
        }
    
        return response()->json($places);
    }

    // public function getStatesByCountry($region_slug, $country_slug)
    // {
    //     $region = Region::where('name', $region_slug)->firstOrFail();
    //     $country = $region->countries()->where('slug', $country_slug)->firstOrFail();
    
    //     // Fetching states from the country
    //     return response()->json($country->states()->get());
    // }

    // public function getCitiesByState($region_slug, $country_slug, $state_slug)
    // {
    //     $region = Region::where('name', $region_slug)->firstOrFail();
    //     $country = $region->countries()->where('slug', $country_slug)->firstOrFail();
    //     $state = $country->states()->where('slug', $state_slug)->firstOrFail();
    
    //     // Fetching cities from the state
    //     return response()->json($state->cities()->get());
    // }

    // public function getPlacesInCity($region_slug, $country_slug, $state_slug, $city_slug)
    // {
    //     $region = Region::where('name', $region_slug)->firstOrFail();
    //     $country = $region->countries()->where('slug', $country_slug)->firstOrFail();
    //     $state = $country->states()->where('slug', $state_slug)->firstOrFail();
    //     $city = $state->cities()->where('slug', $city_slug)->firstOrFail();
    
    //     // Fetching places from the city
    //     return response()->json($city->places()->get());
    // }    
}
