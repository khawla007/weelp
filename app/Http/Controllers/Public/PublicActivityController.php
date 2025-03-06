<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;

class PublicActivityController extends Controller
{
    public function getActivities()
    {
        // return response()->json(['message' => 'Controller Working!']);
        $activities = Activity::with([
            // 'categories',
            // 'locations.city',
            // 'attributes.attribute',
            'categories.category:id,name',  
            'attributes.attribute:id,name',  
            'locations.city:id,name',
            'pricing',
            'seasonalPricing',
            'groupDiscounts',
            'earlyBirdDiscount',
            'lastMinuteDiscount',
            'promoCodes'
        ])->get();

        return response()->json($activities);
    }

    public function getActivityBySlug($activityslug)
    {
        $activity = Activity::with([
            // 'categories',
            // 'locations.city',
            // 'attributes.attribute',
            'categories.category:id,name',  
            'attributes.attribute:id,name',  
            'locations.city:id,name',
            'pricing',
            'seasonalPricing',
            'groupDiscounts',
            'earlyBirdDiscount',
            'lastMinuteDiscount',
            'promoCodes'
        ])->where('slug', $activityslug)->first();

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        return response()->json($activity);
    }
}
