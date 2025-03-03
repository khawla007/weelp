<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Region;
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
