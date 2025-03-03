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

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }
}

