<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
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
        
        // if (empty($cities)) {
        //     return response()->json(['error' => 'No cities found in this region'], 404);
        // }

        // return response()->json($cities);
        if (collect($cities)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No cities found in this region'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $cities
        ], 200);
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

        // return response()->json($formattedActivities);
        if (collect($formattedActivities)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Activities not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedActivities
        ], 200);
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

        // return response()->json([
        //     'data' => $formattedItineraries
        // ]);
        if (collect($formattedItineraries)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Itineraries not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedItineraries
        ], 200);
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

        if (collect($formattedPackages)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Packages not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedPackages
        ], 200);
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
        $categoriesList = collect([])
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

        // Combine all items into a single collection
        $allData = $formattedActivities
                    ->concat($formattedItineraries)
                    ->concat($formattedPackages)
                    ->sortBy('name') // Sorting by name (optional)
                    ->values();

        // Pagination
        $perPage = 8; // Change as needed
        $currentPage = request()->get('page', 1);
        $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
            $allData->forPage($currentPage, $perPage),
            $allData->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        if ($paginatedData->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $paginatedData,
            'categories_list' => $categoriesList
        ], 200);
    }


    // -----------------------Code to get All Packages based on region with location details--------------------------
    public function getPackagesByRegion($region_slug)
    {
        // Find Region
        $region = Region::where('slug', $region_slug)->first();

        if (!$region) {
            return response()->json([
                'success' => false,
                'message' => 'Region not found'
            ], 404);
        }

        // Get city IDs linked to the region
        $cityIds = City::whereExists(function ($query) use ($region) {
            $query->select(DB::raw(1))
                ->from('states')
                ->whereColumn('cities.state_id', 'states.id')
                ->whereExists(function ($subQuery) use ($region) {
                    $subQuery->select(DB::raw(1))
                        ->from('countries')
                        ->whereColumn('states.country_id', 'countries.id')
                        ->whereExists(function ($innerQuery) use ($region) {
                            $innerQuery->select(DB::raw(1))
                                ->from('region_country')
                                ->whereColumn('countries.id', 'region_country.country_id')
                                ->where('region_country.region_id', $region->id);
                        });
                });
        })->pluck('id');

        if ($cityIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No cities and Packages found in this region'
            ], 404);
        }

        // Get all packages linked to those cities using the pivot table
        $packages = Package::whereHas('locations', function ($query) use ($cityIds) {
            $query->whereIn('city_id', $cityIds);
        })
            ->with([
                'basePricing.variations',
                'mediaGallery',
                'categories.category',
                'tags',
                'locations.city.state.country.regions'
            ])
            ->where('featured_package', true)
            ->get();

        if ($packages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No packages found for this region.'
            ], 404);
        }

        // Format the package data
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
                        'state_id' => $city->state->id ?? null,
                        'state' => $city->state->name ?? null,
                        'country_id' => $city->state->country->id ?? null,
                        'country' => $city->state->country->name ?? null,
                        'region_id' => $city->state->country->regions->first()->id ?? null,
                        'region' => $city->state->country->regions->first()->name ?? null,
                    ];
                })->toArray(),
                'categories' => $package->categories->map(function ($category) {
                    return [
                        'id' => $category->category->id ?? null,
                        'name' => $category->category->name ?? null,
                    ];
                })->toArray(),
                'tags' => $package->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id ?? null,
                        'name' => $tag->name ?? null,
                    ];
                })->toArray(),
                'base_pricing' => $package->basePricing,
                'media_gallery' => $package->mediaGallery,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedPackages
        ], 200);
    }

    public function getAllItemsByRegion($region_slug)
    {
        $region = Region::with('countries.states.cities')->where('slug', $region_slug)->first();
    
        if (!$region) {
            return response()->json(['success' => 'false', 'message' => 'Region not found.'], 404);
        }
    
        $cities = $region->countries->flatMap(fn ($country) =>  
            $country->states->flatMap(fn ($state) => $state->cities)
        );
    
        if ($cities->isEmpty()) {
            return response()->json([
                'success' => 'false',
                'message' => 'No cities found under this region.'
            ], 404);
        }
    
        // Combine Activities, Itineraries, and Packages with Pagination
        $activities = Activity::whereHas('locations', fn ($query) =>  
            $query->whereIn('city_id', $cities->pluck('id'))
        )->with(['pricing', 'groupDiscounts', 'categories.category', 'locations.city.state.country.regions']);
    
        $itineraries = Itinerary::whereHas('locations', fn ($query) =>  
            $query->whereIn('city_id', $cities->pluck('id'))
        )->with(['basePricing.variations', 'mediaGallery', 'categories.category', 'tags']);
    
        $packages = Package::whereHas('locations', fn ($query) =>  
            $query->whereIn('city_id', $cities->pluck('id'))
        )->with(['basePricing.variations', 'mediaGallery', 'categories.category', 'tags']);
    
        // Use `paginate()` to get paginated data
        $allItems = collect()
            ->merge($activities->get()->map(fn ($activity) => [
                'id' => $activity->id,
                'name' => $activity->name,
                'slug' => $activity->slug,
                'item_type' => 'activity',
                'featured' => $activity->featured_activity,
                'pricing' => $activity->pricing ? [
                    'regular_price' => $activity->pricing->regular_price,
                    'currency' => $activity->pricing->currency,
                ] : null,
            ]))
            ->merge($itineraries->get()->map(fn ($itinerary) => [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'item_type' => 'itinerary',
                'featured' => $itinerary->featured_itinerary,
                'base_pricing' => $itinerary->basePricing ? [
                    'regular_price' => $itinerary->basePricing->regular_price,
                    'currency' => $itinerary->basePricing->currency,
                ] : null,
            ]))
            ->merge($packages->get()->map(fn ($package) => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'item_type' => 'package',
                'featured' => $package->featured_package,
                'base_pricing' => $package->basePricing ? [
                    'regular_price' => $package->basePricing->regular_price,
                    'currency' => $package->basePricing->currency,
                ] : null,
            ]))
            ->sortByDesc('id')
            ->values();
    
        // Paginate data
        $perPage = 10;
        $page = request()->get('page', 1); // Default page = 1
        $paginatedItems = $allItems->forPage($page, $perPage);
    
        $paginationData = [
            'current_page' => (int) $page,
            'last_page' => ceil($allItems->count() / $perPage),
            'per_page' => $perPage,
            'total' => $allItems->count(),
            'data' => $paginatedItems->values(),
        ];
    
        // Collecting categories list
        $categoriesList = $allItems->flatMap(fn ($item) => 
            match ($item['item_type']) {
                'activity' => Activity::find($item['id'])->categories->map(fn ($category) => [
                    'id' => $category->category->id,
                    'name' => $category->category->name,
                ]),
                'itinerary' => Itinerary::find($item['id'])->categories->map(fn ($category) => [
                    'id' => $category->category->id,
                    'name' => $category->category->name,
                ]),
                'package' => Package::find($item['id'])->categories->map(fn ($category) => [
                    'id' => $category->category->id,
                    'name' => $category->category->name,
                ]),
                default => [],
            }
        )->unique('id')->values();
    
        // Collecting cities list
        $locationList = $allItems->flatMap(fn ($item) => 
            match ($item['item_type']) {
                'activity' => Activity::find($item['id'])->locations->map(fn ($location) => [
                    'id' => $location->city->id,
                    'name' => $location->city->name,
                ]),
                'itinerary' => Itinerary::find($item['id'])->locations->map(fn ($location) => [
                    'id' => $location->city->id,
                    'name' => $location->city->name,
                ]),
                'package' => Package::find($item['id'])->locations->map(fn ($location) => [
                    'id' => $location->city->id,
                    'name' => $location->city->name,
                ]),
                default => [],
            }
        )->unique('id')->values();
    
        // Return Response
        return response()->json([
            'success' => 'true',
            'data' => $paginationData,
            'category_list' => $categoriesList,
            'location_list' => $locationList,
        ], 200);
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
    
        // return response()->json($places);
        if (collect($places)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Places not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $places
        ], 200);
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
