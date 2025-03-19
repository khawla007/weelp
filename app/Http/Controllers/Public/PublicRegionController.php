<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
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

    // -----------------------Code to get activity based on location type primary city with location details--------------------------
    // public function getActivityByCity($region_slug, $city_slug)
    public function getActivityByCity($city_slug)
    {
        $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        $activities = Activity::whereHas('locations', function ($query) use ($city) {
            $query->where('city_id', $city->id)
                ->where('location_type', 'primary');
        })
        ->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions'])
        ->where('featured_activity', true) 
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
                'item_type' => $activity->item_type,
                'featured_activity' => $activity->featured_activity,
                'pricing' => $activity->pricing,
                'groupDiscounts' => $activity->groupDiscounts,
                'categories' => $activity->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),

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

    // -----------------------Code to get Itineraries based on city with location details--------------------------
    // public function getItinerariesByCity($region_slug, $city_slug)
    public function getItinerariesByCity($city_slug)
    {

        $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        // Itineraries ke saath schedules aur related data fetch karo
        $itineraries = $city->itineraries()->with([
            'basePricing.variations',
            'mediaGallery',
            'categories.category',
            'tags'
        ])->where('featured_itinerary', true)->get();

        if ($itineraries->isEmpty()) {
            return response()->json(['message' => 'No itineraries found for this city.'], 404);
        }

        $formattedItineraries = $itineraries->map(function ($itinerary) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'item_type' => $itinerary->item_type,
                'featured_itinerary' => $itinerary->featured_itinerary,
                'locations' => $itinerary->locations->map(function ($location) {
                    $city = $location->city;
                    return [
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
                'categories' => $itinerary->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'tags' => $itinerary->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                })->toArray(),
                'base_pricing' => $itinerary->basePricing,
                'media_gallery' => $itinerary->mediaGallery,
            ];
        });

        return response()->json([
            'data' => $formattedItineraries
        ]);
    }

    // -----------------------Code to get Packages based on city with location details--------------------------

    // public function getPackagesByCity($region_slug, $city_slug)
    public function getPackagesByCity($city_slug)
    {
        $city = City::where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        $packages = $city->packages()->with([
            'basePricing.variations',
            'mediaGallery',
            'categories.category',
            'tags'
        ])->where('featured_package', true)->get();

        if ($packages->isEmpty()) {
            return response()->json(['message' => 'No packages found for this city.'], 404);
        }

        $formattedPackages = $packages->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'item_type' => $package->item_type,
                'featured_package' => $package->featured_package,
                'locations' => $package->locations->map(function ($location) {
                    $city = $location->city;
                    return [
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
                'categories' => $package->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'tags' => $package->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                })->toArray(),
                'base_pricing' => $package->basePricing,
                'media_gallery' => $package->mediaGallery,
            ];
        });

        return response()->json([
            'data' => $formattedPackages
        ]);
    }

    // Getting all type of items through city for city page includiing activity , itinerary and package
    public function getAllItemsByCity($city_slug)
    {
        $city = City::with(['state.country.regions'])->where('slug', $city_slug)->first();

        if (!$city) {
            return response()->json(['message' => 'City not found.'], 404);
        }

        // Get Activities
        $activities = Activity::whereHas('locations', function ($query) use ($city) {
            $query->where('city_id', $city->id)
                ->where('location_type', 'primary');
        })
        ->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions'])
        ->get();

        // Get Itineraries
        $itineraries = $city->itineraries()->with([
            'basePricing.variations',
            'mediaGallery',
            'categories.category',
            'tags'
        ])->get();

        // Get Packages
        $packages = $city->packages()->with([
            'basePricing.variations',
            'mediaGallery',
            'categories.category',
            'tags'
        ])->get();

        // Collecting the list of categories of all items that are present in this city.
        $categoriesList = collect([]);

        $categoriesList = $categoriesList
            ->merge($activities->flatMap(function ($activity) {
                return $activity->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                });
            }))
            ->merge($itineraries->flatMap(function ($itinerary) {
                return $itinerary->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                });
            }))
            ->merge($packages->flatMap(function ($package) {
                return $package->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                });
            }))
            ->unique('id')
            ->values(); 

        $formattedActivities = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'item_type' => $activity->item_type,
                'featured_activity' => $activity->featured_activity,
                'pricing' => $activity->pricing,
                'groupDiscounts' => $activity->groupDiscounts,
                'categories' => $activity->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'locations' => $activity->locations->map(function ($location) {
                    $city = $location->city;
                    return [
                        'city_id' => $city->id,
                        'city' => $city->name,
                        'state' => $city->state ? $city->state->name : null,
                        'country' => $city->state && $city->state->country ? $city->state->country->name : null,
                        'region' => $city->state && $city->state->country && $city->state->country->regions->isNotEmpty()
                            ? $city->state->country->regions->first()->name
                            : null,
                    ];
                }),
            ];
        });
        
        $formattedItineraries = $itineraries->map(function ($itinerary) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'item_type' => $itinerary->item_type,
                'featured_itinerary' => $itinerary->featured_itinerary,
                'base_pricing' => $itinerary->basePricing,
                'categories' => $itinerary->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'tags' => $itinerary->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                })->toArray(),
                'media_gallery' => $itinerary->mediaGallery,
            ];
        });
        
        $formattedPackages = $packages->map(function ($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'item_type' => $package->item_type,
                'featured_package' => $package->featured_package,
                'base_pricing' => $package->basePricing,
                'categories' => $package->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id,
                        'name' => $category->category->name,
                    ];
                })->toArray(),
                'tags' => $package->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ];
                })->toArray(),
                'media_gallery' => $package->mediaGallery,
            ];
        });
        
        $allData = [
            'all_items' => $formattedActivities
                ->concat($formattedItineraries)
                ->concat($formattedPackages),

                'categories_list' => $categoriesList,
        ];
        
        return response()->json($allData);
    }

    // -------------------------Getting places by city------------------------
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
