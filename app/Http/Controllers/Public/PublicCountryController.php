<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;

class PublicCountryController extends Controller
{
    public function getCountries()
    {
        $countries = Country::with([
            'details',
            'travelInfo',
            'seasons',
            'events',
            'additionalInfo',
            'faqs',
            'seo'
        ])->get();

        return response()->json([
            'status' => 'success',
            'data' => $countries
        ], 200);
    }
}
