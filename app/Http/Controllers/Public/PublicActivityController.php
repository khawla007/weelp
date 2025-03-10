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
        $activities = Activity::with([
            'categories.category', 
            'attributes.attribute', 
            'locations.city',
            'pricing', 
            'seasonalPricing', 
            'groupDiscounts', 
            'earlyBirdDiscount', 
            'lastMinuteDiscount', 
            'promoCodes'
        ])->get()->map(function ($activity) {
            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'description' => $activity->description,
                'short_description' => $activity->short_description,
                'featured_images' => $activity->featured_images,
                'categories' => $activity->categories->pluck('category.name')->join(', '),
                'attributes' => $activity->attributes->map(function ($attribute) {
                    return [
                        'name' => $attribute->attribute->name,
                        'attribute_value' => $attribute->attribute_value,
                    ];
                }),
                'locations' => $activity->locations->pluck('city.name')->join(', '),
                'pricing' => $activity->pricing,  
                'seasonalPricing' => $activity->seasonalPricing,
                'groupDiscounts' => $activity->groupDiscounts,
                'earlyBirdDiscount' => $activity->earlyBirdDiscount,
                'lastMinuteDiscount' => $activity->lastMinuteDiscount,
                'promoCodes' => $activity->promoCodes,
            ];
        });
        
        return response()->json($activities);
    }

    public function getActivityBySlug($activityslug)
    {
        $activity = Activity::with([
            'categories.category', 
            'attributes.attribute', 
            'locations.city',
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
    
        $formattedActivity = [
            'id' => $activity->id,
            'name' => $activity->name,
            'slug' => $activity->slug,
            'description' => $activity->description,
            'short_description' => $activity->short_description,
            'featured_images' => $activity->featured_images,
            'categories' => $activity->categories->pluck('category.name')->join(', '),
            'attributes' => $activity->attributes->map(function ($attribute) {
                return [
                    'name' => $attribute->attribute->name,
                    'attribute_value' => $attribute->attribute_value,
                ];
            }),
            'locations' => $activity->locations->pluck('city.name')->join(', '),
            'pricing' => $activity->pricing,  
            'seasonalPricing' => $activity->seasonalPricing,
            'groupDiscounts' => $activity->groupDiscounts,
            'earlyBirdDiscount' => $activity->earlyBirdDiscount,
            'lastMinuteDiscount' => $activity->lastMinuteDiscount,
            'promoCodes' => $activity->promoCodes,
        ];
    
        return response()->json($formattedActivity);
    }


    
}
