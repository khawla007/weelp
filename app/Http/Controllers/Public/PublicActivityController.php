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

        // $activities = Activity::with([
        //     // 'categories',
        //     // 'locations.city',
        //     // 'attributes.attribute',
        //     'categories.category:id,name',  
        //     'attributes.attribute:id,name',  
        //     'locations.city:id,name',
        //     'pricing',
        //     'seasonalPricing',
        //     'groupDiscounts',
        //     'earlyBirdDiscount',
        //     'lastMinuteDiscount',
        //     'promoCodes'
        // ])->get();

        // return response()->json($activities);
        $activities = Activity::with([
            'categories.category', 
            'attributes.attribute', 
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

    // public function getActivityBySlug($activityslug)
    // {
    //     $activity = Activity::with([
    //         // 'categories',
    //         // 'locations.city',
    //         // 'attributes.attribute',
    //         'categories.category:id,name',  
    //         'attributes.attribute:id,name',  
    //         'locations.city:id,name',
    //         'pricing',
    //         'seasonalPricing',
    //         'groupDiscounts',
    //         'earlyBirdDiscount',
    //         'lastMinuteDiscount',
    //         'promoCodes'
    //     ])->where('slug', $activityslug)->first();

    //     if (!$activity) {
    //         return response()->json(['message' => 'Activity not found'], 404);
    //     }

    //     return response()->json($activity);
    // }

    public function getActivityBySlug($activityslug)
    {
        $activity = Activity::with([
            'categories.category', 
            'attributes.attribute', 
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
