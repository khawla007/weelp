<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;

class PublicActivityController extends Controller
{

    // ----------------------Code to get all activities with all location details----------------------
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
            'promoCodes',
            'availability'
        ])->get()->map(function ($activity) {
            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'featured_activity' => $activity->featured_activity,
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

                'locations' => $activity->locations->map(function ($location) {
                    $city = $location->city;
                    return [
                        'location_type' => $location->location_type, 
                        'location_label' => $location->location_label, 
                        'duration' => $location->duration, 
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'state_id' => $city->state ? $city->state->id : null,
                        'state' => $city->state ? $city->state->name : null,
                        'country_id' => $city->state && $city->state->country ? $city->state->country->id : null,
                        'country' => $city->state && $city->state->country ? $city->state->country->name : null,
                        'region_id' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->id
                            : null,
                        'region' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->name
                            : null,
                        
                    ];
                }),
                'pricing' => $activity->pricing,  
                'seasonalPricing' => $activity->seasonalPricing,
                'groupDiscounts' => $activity->groupDiscounts,
                'earlyBirdDiscount' => $activity->earlyBirdDiscount,
                'lastMinuteDiscount' => $activity->lastMinuteDiscount,
                'promoCodes' => $activity->promoCodes,
                'availability' => $activity->availability ? [
                    'date_based_activity' => $activity->availability->date_based_activity,
                    'start_date' => $activity->availability->start_date,
                    'end_date' => $activity->availability->end_date,
                    'quantity_based_activity' => $activity->availability->quantity_based_activity,
                    'max_quantity' => $activity->availability->max_quantity,
                ] : null,
            ];
        });
        
        return response()->json($activities);
    }

    // Code to get all activities with all location details and pagiantion.

    // public function getActivities()
    // {
    //     $page = request('page', 1);

    //     $activities = Activity::with([
    //             'categories.category', 
    //             'attributes.attribute', 
    //             'locations.city',
    //             'pricing', 
    //             'seasonalPricing', 
    //             'groupDiscounts', 
    //             'earlyBirdDiscount', 
    //             'lastMinuteDiscount', 
    //             'promoCodes'
    //         ])
    //         ->orderBy('id', 'desc') 
    //         ->paginate(4, ['*'], 'page', $page)
    //         ->appends(request()->query());
        
    //     $data = $activities->map(function ($activity) {
    //         return [
    //             'id' => $activity->id,
    //             'name' => $activity->name,
    //             'slug' => $activity->slug,
    //             'featured_activity' => $activity->featured_activity,
    //             'description' => $activity->description,
    //             'short_description' => $activity->short_description,
    //             'featured_images' => $activity->featured_images,
    //             'categories' => $activity->categories->pluck('category.name')->join(', '),
    //             'attributes' => $activity->attributes->map(function ($attribute) {
    //                 return [
    //                     'name' => $attribute->attribute->name,
    //                     'attribute_value' => $attribute->attribute_value,
    //                 ];
    //             }),
    //             'locations' => $activity->locations->map(function ($location) {
    //                 $city = $location->city;
    //                 return [
    //                     'location_type' => $location->location_type, 
    //                     'location_label' => $location->location_label, 
    //                     'duration' => $location->duration, 
    //                     'city_id' => $city->id,
    //                     'city' => $city->name,
    //                     'state_id' => $city->state ? $city->state->id : null,
    //                     'state' => $city->state ? $city->state->name : null,
    //                     'country_id' => $city->state && $city->state->country ? $city->state->country->id : null,
    //                     'country' => $city->state && $city->state->country ? $city->state->country->name : null,
    //                     'region_id' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
    //                         ? $city->state->country->regions->first()->id
    //                         : null,
    //                     'region' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
    //                         ? $city->state->country->regions->first()->name
    //                         : null,
                        
    //                 ];
    //             }),
    //             'pricing' => $activity->pricing,  
    //             'seasonalPricing' => $activity->seasonalPricing,
    //             'groupDiscounts' => $activity->groupDiscounts,
    //             'earlyBirdDiscount' => $activity->earlyBirdDiscount,
    //             'lastMinuteDiscount' => $activity->lastMinuteDiscount,
    //             'promoCodes' => $activity->promoCodes,
    //         ];
    //     });
        
    //     $nextPageData = [];
    //     if ($activities->nextPageUrl()) {
    //         $nextPage = $page + 1;
    //         $nextPageActivities = Activity::with([
    //                 'categories.category', 
    //                 'attributes.attribute', 
    //                 'locations.city',
    //                 'pricing', 
    //                 'seasonalPricing', 
    //                 'groupDiscounts', 
    //                 'earlyBirdDiscount', 
    //                 'lastMinuteDiscount', 
    //                 'promoCodes'
    //             ])
    //             ->orderBy('id', 'asc') 
    //             ->paginate(4, ['*'], 'page', $nextPage)
    //             ->getCollection();
        
    //         // $nextPageData = $nextPageActivities->map(function ($activity) {
    //         //     return [
    //         //         'id' => $activity->id,
    //         //         'name' => $activity->name,
    //         //         'slug' => $activity->slug,
    //         //         'featured_activity' => $activity->featured_activity,
    //         //         'description' => $activity->description,
    //         //         'short_description' => $activity->short_description,
    //         //         'featured_images' => $activity->featured_images,
    //         //         'categories' => $activity->categories->pluck('category.name')->join(', '),
    //         //     ];
    //         // });
    //     }
        
    //     return response()->json([
    //         'data' => $data,
    //         // 'next_page_data' => $nextPageData,
    //         'current_page' => $activities->currentPage(),
    //         'last_page' => $activities->lastPage(),
    //         'per_page' => $activities->perPage(),
    //         'total' => $activities->total(),
    //         'next_page_url' => $activities->nextPageUrl(),
    //         'prev_page_url' => $activities->previousPageUrl(),
    //     ]);
        
    // }    

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
            'featured_activity' => $activity->featured_activity,
            'description' => $activity->description,
            'short_description' => $activity->short_description,
            'featured_images' => $activity->featured_images,
            // 'categories' => $activity->categories->pluck('category.name')->join(', '),
            'categories' => $activity->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            })->toArray(),
            'attributes' => $activity->attributes->map(function ($attribute) {
                return [
                    'name' => $attribute->attribute->name,
                    'attribute_value' => $attribute->attribute_value,
                ];
            }),

            'locations' => $activity->locations->map(function ($location) {
                $city = $location->city;
                return [
                    'location_type' => $location->location_type, 
                    'location_label' => $location->location_label, 
                    'duration' => $location->duration, 
                    'city_id' => $city->id,
                    'city' => $city->name,
                    'state_id' => $city->state ? $city->state->id : null,
                    'state' => $city->state ? $city->state->name : null,
                    'country_id' => $city->state && $city->state->country ? $city->state->country->id : null,
                    'country' => $city->state && $city->state->country ? $city->state->country->name : null,
                    'region_id' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                        ? $city->state->country->regions->first()->id
                        : null,
                    'region' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                        ? $city->state->country->regions->first()->name
                        : null,
                    
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
