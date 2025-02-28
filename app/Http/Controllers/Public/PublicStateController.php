<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\State;

class PublicStateController extends Controller
{
    public function getStates()
    {
        $states = State::with([
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
            'data' => $states
        ], 200);
    }
}
