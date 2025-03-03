<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Country;

class PublicCountryController extends Controller
{
    public function getCountries()
    {
        // ✅ Sirf countries fetch karni hai (without extra relationships)
        $countries = Country::all();

        return response()->json([
            'status' => 'success',
            'data' => $countries
        ], 200);
    }
}
