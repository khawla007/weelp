<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Activity;
use App\Models\City;
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

    // -----------------------Old code to get activity based on city without location details--------------------------

    // public function getActivityByCity($region_slug, $city_slug)
    // {
    //     $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();
    
    //     if (!$city) {
    //         return response()->json(['message' => 'City not found.'], 404);
    //     }
    
    //     $activities = $city->activities()->with(['pricing', 'groupDiscounts'])->get();
    
    //     if ($activities->isEmpty()) {
    //         return response()->json(['message' => 'No activities found for this city.'], 404);
    //     }
    
    //     $formattedActivities = $activities->map(function ($activity) use ($city) {
    //         return [
    //             'id' => $activity->id,
    //             'name' => $activity->name,
    //             'slug' => $activity->slug,
    //             'pricing' => $activity->pricing,
    //             'groupDiscounts' => $activity->groupDiscounts,
    //             'city' => $city->name,
    //             'city_id' => $city->id,
    //             'state' => $city->state ? $city->state->name : null,
    //             'state_id' => $city->state ? $city->state->id : null,
    //             'country' => $city->state && $city->state->country ? $city->state->country->name : null,
    //             'country_id' => $city->state && $city->state->country ? $city->state->country->id : null,
    //             'region' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
    //                 ? $city->state->country->regions->pluck('name')->join(', ')
    //                 : null,
    //             'region_id' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
    //                 ? $city->state->country->regions->pluck('id')->join(', ')
    //                 : null,
    //         ];
    //     });
    
    //     return response()->json($formattedActivities);
    // }

    // -----------------------Code to get activity based on location type primary city with location details--------------------------

    public function getActivityByCity($region_slug, $city_slug)
    {
        $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        $activities = Activity::whereHas('locations', function ($query) use ($city) {
            $query->where('city_id', $city->id)
                ->where('location_type', 'primary');
        })
        ->with(['pricing', 'groupDiscounts', 'locations.city.state.country.regions'])
        ->get();

        if ($activities->isEmpty()) {
            return response()->json(['message' => 'No activities found for this city.'], 404);
        }

        $formattedActivities = $activities->map(function ($activity) {
            $primaryLocation = $activity->locations->where('location_type', 'primary')->first();

            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'featured_activity' => $activity->featured_activity,
                'pricing' => $activity->pricing,
                'groupDiscounts' => $activity->groupDiscounts,

                //  All Locations (including primary + additional)
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
            ];
        });

        return response()->json($formattedActivities);
    }

    // -----------------------Code to get activity based on location type primary city with location details and Pagiantion--------------------------

    // public function getActivityByCity($region_slug, $city_slug)
    // {
    //     $page = request('page', 1);
    
    //     $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();
    
    //     if (!$city) {
    //         return response()->json(['message' => 'City not found.'], 404);
    //     }
    
    //     $activities = Activity::whereHas('locations', function ($query) use ($city) {
    //             $query->where('city_id', $city->id)
    //                 ->where('location_type', 'primary');
    //         })
    //         ->with(['pricing', 'groupDiscounts', 'locations.city.state.country.regions'])
    //         ->orderBy('id', 'asc')
    //         ->paginate(1, ['*'], 'page', $page)
    //         ->appends(request()->query());
    
    //     if ($activities->isEmpty()) {
    //         return response()->json(['message' => 'No activities found for this city.'], 404);
    //     }
    
    //     $formattedActivities = $activities->map(function ($activity) {
    //         $primaryLocation = $activity->locations->where('location_type', 'primary')->first();
    
    //         return [
    //             'id' => $activity->id,
    //             'name' => $activity->name,
    //             'slug' => $activity->slug,
    //             'featured_activity' => $activity->featured_activity,
    //             'pricing' => $activity->pricing,
    //             'groupDiscounts' => $activity->groupDiscounts,
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
    //         ];
    //     });
    
    //     return response()->json([
    //         'data' => $formattedActivities,
    //         'current_page' => $activities->currentPage(),
    //         'last_page' => $activities->lastPage(),
    //         'per_page' => $activities->perPage(),
    //         'total' => $activities->total(),
    //         'next_page_url' => $activities->nextPageUrl(),
    //         'prev_page_url' => $activities->previousPageUrl(),
    //     ]);
    // }    

    // -----------------------Code to get Itineraries based on city with location details--------------------------

    public function getItinerariesByCity($region_slug, $city_slug)
    {
        $city = City::where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        // Itineraries ke saath schedules aur related data fetch karo
        $itineraries = $city->itineraries()->with([
            // 'schedules.activities.activity',
            // 'schedules.transfers.transfer',
            'basePricing.variations',
            // 'basePricing.blackoutDates',
            // 'inclusionsExclusions',
            'mediaGallery',
            // 'seo',
            'categories',
            // 'attributes.attribute',
            'tags'
        ])->get();

        if ($itineraries->isEmpty()) {
            return response()->json(['message' => 'No itineraries found for this city.'], 404);
        }

        $formattedItineraries = $itineraries->map(function ($itinerary) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'city_id' => $itinerary->city ? $itinerary->city->id : null,
                'city' => $itinerary->city ? $itinerary->city->name : null,
                'state_id' => $itinerary->city && $itinerary->city->state ? $itinerary->city->state->id : null,
                'state' => $itinerary->city && $itinerary->city->state ? $itinerary->city->state->name : null,
                'country_id' => $itinerary->city && $itinerary->city->state && $itinerary->city->state->country
                    ? $itinerary->city->state->country->id
                    : null,
                'country' => $itinerary->city && $itinerary->city->state && $itinerary->city->state->country
                    ? $itinerary->city->state->country->name
                    : null,
                'region_id' => $itinerary->city && $itinerary->city->state && $itinerary->city->state->country && $itinerary->city->state->country->regions->isNotEmpty()
                    ? $itinerary->city->state->country->regions->first()->id
                    : null,
                'region' => $itinerary->city && $itinerary->city->state && $itinerary->city->state->country && $itinerary->city->state->country->regions->isNotEmpty()
                    ? $itinerary->city->state->country->regions->first()->name
                    : null,
                // 'schedules' => $itinerary->schedules->map(function ($schedule) {
                //     return [
                //         'day' => $schedule->day,
                //         'activities' => $schedule->activities->map(function ($activity) {
                //             return [
                //                 'id' => $activity->id,
                //                 'name' => $activity->activity ? $activity->activity->name : null,
                //                 'start_time' => $activity->start_time,
                //                 'end_time' => $activity->end_time,
                //                 'notes' => $activity->notes,
                //                 'price' => $activity->price,
                //                 'include_in_package' => $activity->include_in_package,
                //             ];
                //         }),
                //         'transfers' => $schedule->transfers->map(function ($transfer) {
                //             return [
                //                 'id' => $transfer->id,
                //                 'name' => $transfer->transfer ? $transfer->transfer->name : null,
                //                 'start_time' => $transfer->start_time,
                //                 'end_time' => $transfer->end_time,
                //                 'pickup_location' => $transfer->pickup_location,
                //                 'dropoff_location' => $transfer->dropoff_location,
                //                 'pax' => $transfer->pax,
                //                 'price' => $transfer->price,
                //                 'include_in_package' => $transfer->include_in_package,
                //             ];
                //         }),
                //     ];
                // }),
                'categories' => $itinerary->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                    ];
                })->toArray(),
                // 'attributes' => $itinerary->attributes->map(function ($attribute) {
                //     return [
                //         'id' => $attribute->attribute->id,
                //         'name' => $attribute->attribute->name,
                //         'attribute_value' => $attribute->attribute_value,
                //     ];
                // }),
                'tags' => $itinerary->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                })->toArray(),
                'base_pricing' => $itinerary->basePricing,
                // 'inclusions_exclusions' => $itinerary->inclusionsExclusions,
                'media_gallery' => $itinerary->mediaGallery,
                // 'seo' => $itinerary->seo,
            ];
        });

        return response()->json([
            'data' => $formattedItineraries
        ]);
    }

    // -----------------------Code to get activity based on city with location details and Pagiantion--------------------------

    // public function getItinerariesByCity($region_slug, $city_slug)
    // {
    //     $page = request('page', 1);

    //     $city = City::where('slug', $city_slug)->first();

    //     if (!$city) {
    //         return response()->json(['message' => 'City not found.'], 404);
    //     }

    //     // Itineraries ke saath schedules aur related data fetch karo (paginated)
    //     $itineraries = $city->itineraries()
    //         ->with([
    //             'basePricing.variations',
    //             'mediaGallery',
    //             'categories',
    //             'tags'
    //         ])
    //         ->orderBy('id', 'asc')
    //         ->paginate(1, ['*'], 'page', $page)
    //         ->appends(request()->query());

    //     if ($itineraries->isEmpty()) {
    //         return response()->json(['message' => 'No itineraries found for this city.'], 404);
    //     }

    //     $formattedItineraries = $itineraries->map(function ($itinerary) {
    //         return [
    //             'id' => $itinerary->id,
    //             'name' => $itinerary->name,
    //             'slug' => $itinerary->slug,
    //             'city_id' => $itinerary->city ? $itinerary->city->id : null,
    //             'city' => $itinerary->city ? $itinerary->city->name : null,
    //             'state_id' => $itinerary->city && $itinerary->city->state ? $itinerary->city->state->id : null,
    //             'state' => $itinerary->city && $itinerary->city->state ? $itinerary->city->state->name : null,
    //             'country_id' => $itinerary->city && $itinerary->city->state && $itinerary->city->state->country
    //                 ? $itinerary->city->state->country->id
    //                 : null,
    //             'country' => $itinerary->city && $itinerary->city->state && $itinerary->city->state->country
    //                 ? $itinerary->city->state->country->name
    //                 : null,
    //             'region_id' => $itinerary->city && $itinerary->city->state && $itinerary->city->state->country && $itinerary->city->state->country->regions->isNotEmpty()
    //                 ? $itinerary->city->state->country->regions->first()->id
    //                 : null,
    //             'region' => $itinerary->city && $itinerary->city->state && $itinerary->city->state->country && $itinerary->city->state->country->regions->isNotEmpty()
    //                 ? $itinerary->city->state->country->regions->first()->name
    //                 : null,
    //             'categories' => $itinerary->categories->map(function ($category) {
    //                 return [
    //                     'id' => $category->id,
    //                     'name' => $category->name,
    //                 ];
    //             })->toArray(),
    //             'tags' => $itinerary->tags->map(function ($tag) {
    //                 return [
    //                     'id' => $tag->id,
    //                     'name' => $tag->name,
    //                 ];
    //             })->toArray(),
    //             'base_pricing' => $itinerary->basePricing,
    //             'media_gallery' => $itinerary->mediaGallery,
    //         ];
    //     });

    //     return response()->json([
    //         'data' => $formattedItineraries,
    //         'current_page' => $itineraries->currentPage(),
    //         'last_page' => $itineraries->lastPage(),
    //         'per_page' => $itineraries->perPage(),
    //         'total' => $itineraries->total(),
    //         'next_page_url' => $itineraries->nextPageUrl(),
    //         'prev_page_url' => $itineraries->previousPageUrl(),
    //     ]);
    // }

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
