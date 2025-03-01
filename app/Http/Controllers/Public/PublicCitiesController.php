<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class PublicCitiesController extends Controller
{
    public function index()
    {
        $cities = City::with([
            'locationDetails',
            'travelInfo',
            'seasons',
            'events',
            'additionalInfo',
            'faqs',
            'seo'
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }
}
