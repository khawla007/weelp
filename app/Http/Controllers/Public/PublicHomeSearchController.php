<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
use App\Models\City;
use App\Models\Region;

class PublicHomeSearchController extends Controller
{
    // getting region and cities for Where to? search feild on home page.
    public function getRegionsAndCities()
    {
        // Get all regions
        $regions = Region::select('id', 'name')
            ->get()
            ->map(function ($region) {
                return [
                    'id' => 'region_' . $region->id, 
                    'name' => $region->name,
                    'type' => 'region'
                ];
            });

        // Get all cities
        $cities = City::select('id', 'name')
            ->get()
            ->map(function ($city) {
                return [
                    'id' => 'city_' . $city->id, 
                    'name' => $city->name,
                    'type' => 'city'
                ];
            });

        // Merge and sort
        $list = $regions->merge($cities)->sortBy('name')->values();

        if ($list->isEmpty()) {
            return response()->json([
                'success' => 'false',
                'message' => 'Locations not found'
            ]);
        }

        return response()->json([
            'success' => 'true',
            'data' => $list
        ]);
    }

    // Merging all activity, itinerary and packages in one function to return response in api
    public function homeSearch(Request $request)
    {
        $request->validate([
            'location'   => 'required|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'quantity'   => 'nullable|integer|min:1',
            'page'       => 'nullable|integer|min:1',
        ]);

        $location = $request->location;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $quantity = $request->quantity;
        $page = $request->page ?? 1;
        $perPage = 4;

        $cityIds = $this->getCityIdsFromLocationSlug($location);

        $activities = $this->searchActivities($cityIds, $startDate, $endDate, $quantity);
        $itineraries = $this->searchItineraries($cityIds, $startDate, $endDate, $quantity);
        $packages = $this->searchPackages($cityIds, $startDate, $endDate, $quantity);

        // Merge all items into a single list
        $allItems = $activities
            ->concat($itineraries)
            ->concat($packages);

        // Paginate (4 items per page)
        $paginatedItems = $allItems->forPage($page, $perPage)->values();

        // Get total pages
        $totalItems = $allItems->count();
        $totalPages = ceil($totalItems / $perPage);

        // Prepare category list
        $categoriesList = collect($allItems->flatMap(function ($item) {
            return $item['categories'];
        }))->unique('id')->values();

        $results = [
            'success' => 'true',
            'data' => $paginatedItems,
            'categories_list' => $categoriesList,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages
            ]
        ];

        if ($totalItems === 0) {
            return response()->json([
                'success' => 'false',
                'message' => 'No items found'
            ]);
        }

        return response()->json($results);
    }

    // Get City IDs based on Slug
    private function getCityIdsFromLocationSlug($slug)
    {
        $cityIds = [];
    
        $city = City::where('slug', $slug)->first();
        if ($city) {
            $cityIds[] = $city->id;
        }
    
        $region = Region::where('slug', $slug)->first();
        if ($region) {
            $regionCities = City::whereHas('state.country.regions', function ($query) use ($region) {
                $query->where('regions.id', $region->id); 
            })->pluck('id')->toArray();
    
            $cityIds = array_merge($cityIds, $regionCities);
        }
    
        return $cityIds;
    }

    // Activity Search Function
    private function searchActivities($cityIds, $startDate, $endDate, $quantity)
    {
        $query = Activity::with([
            'categories' => function ($q) {
                $q->with('category:id,name'); 
            },
            'pricing',
            'groupDiscounts',
            'earlyBirdDiscount'
        ])->whereHas('locations', function ($q) use ($cityIds) {
            $q->whereIn('city_id', $cityIds);
        });

        if ($startDate && $endDate) {
            $query->whereHas('availability', function ($q) use ($startDate, $endDate) {
                $q->where('date_based_activity', true)
                    ->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $endDate);
            });
        }

        if ($quantity) {
            $query->whereHas('availability', function ($q) use ($quantity) {
                $q->where(function ($q) use ($quantity) {
                    $q->where('quantity_based_activity', false)
                        ->orWhere(function ($q) use ($quantity) {
                            $q->where('quantity_based_activity', true)
                                ->where('max_quantity', '>=', $quantity);
                        });
                });
            });
        }

        // return $query->get();
        return $query->get()->map(function ($activity) {
            $categories = $activity->categories->map(function ($activityCategory) {
                return [
                    'id' => $activityCategory->category->id,
                    'name' => $activityCategory->category->name,
                ];
            })->unique()->values();
        
            return [
                'id' => $activity->id,
                'name' => $activity->name,
                'item_type' => $activity->item_type,
                'categories' => $categories,
                'pricing' => $activity->pricing ? [
                    'base_price' => $activity->pricing->base_price,
                    'currency' => $activity->pricing->currency,
                ] : null,
                'group_discount' => $activity->groupDiscounts ? $activity->groupDiscounts->map(function ($discount) {
                    return [
                        'min_people' => $discount->min_people,
                        'discount_amount' => $discount->discount_amount,
                        'discount_type' => $discount->discount_type,
                    ];
                }) : [],
                'early_bird_discount' => $activity->earlyBirdDiscount ? [
                    'days_before_start' => $activity->earlyBirdDiscount->first()?->days_before_start,
                    'discount_amount' => $activity->earlyBirdDiscount->first()?->discount_amount,
                    'discount_type' => $activity->earlyBirdDiscount->first()?->discount_type,
                ] : null,
            ];
        });
    }


    // Itinerary Search Function
    private function searchItineraries($cityIds, $startDate, $endDate, $quantity)
    {
        $query = Itinerary::with([
            'categories' => function ($q) {
                $q->with('category:id,name'); 
            },
            'locations',
            'basePricing.variations',
        ])->whereHas('locations', function ($q) use ($cityIds) {
            $q->whereIn('city_id', $cityIds);
        });
    
        if ($startDate && $endDate) {
            $query->whereHas('availability', function ($q) use ($startDate, $endDate) {
                $q->where('date_based_itinerary', true)
                    ->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $endDate);
            });
        }
    
        if ($quantity) {
            $query->whereHas('availability', function ($q) use ($quantity) {
                $q->where(function ($q) use ($quantity) {
                    $q->where('quantity_based_itinerary', false)
                        ->orWhere(function ($q) use ($quantity) {
                            $q->where('quantity_based_itinerary', true)
                                ->where('max_quantity', '>=', $quantity);
                        });
                });
            });
        }
    
        $itineraries = $query->get();
    
        $itineraries->transform(function ($itinerary) {

            $categories = $itinerary->categories->map(function ($itineraryCategory) {
                return [
                    'id' => $itineraryCategory->category->id,
                    'name' => $itineraryCategory->category->name,
                ];
            })->unique()->values();

            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'item_type' => $itinerary->item_type,
                'categories' => $categories,
                'base_pricing' => $itinerary->basePricing ? [
                    'currency' => $itinerary->basePricing->currency,
                    'availability' => $itinerary->basePricing->availability,
                    'start_date' => $itinerary->basePricing->start_date,
                    'end_date' => $itinerary->basePricing->end_date,
                    'variations' => $itinerary->basePricing->variations->map(function ($variation) {
                        return [
                            'id' => $variation->id,
                            'name' => $variation->name,
                            'regular_price' => $variation->regular_price,
                            'sale_price' => $variation->sale_price,
                            'max_guests' => $variation->max_guests,
                            'description' => $variation->description,
                        ];
                    })->toArray(),
                ] : null,
            ];
        });
    
        return $itineraries;
    }
    

    // Package Search function
    private function searchPackages($cityIds, $startDate, $endDate, $quantity)
    {
        $query = Package::with([
            'categories' => function ($q) {
                $q->with('category:id,name'); 
            },
            'locations',
            'basePricing.variations',
        ])->whereHas('locations', function ($q) use ($cityIds) {
            $q->whereIn('city_id', $cityIds);
        });

        if ($startDate && $endDate) {
            $query->whereHas('availability', function ($q) use ($startDate, $endDate) {
                $q->where('date_based_package', true)
                    ->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $endDate);
            });
        }

        if ($quantity) {
            $query->whereHas('availability', function ($q) use ($quantity) {
                $q->where(function ($q) use ($quantity) {
                    $q->where('quantity_based_package', false)
                        ->orWhere(function ($q) use ($quantity) {
                            $q->where('quantity_based_package', true)
                                ->where('max_quantity', '>=', $quantity);
                        });
                });
            });
        }

        // return $query->get();
        $packages = $query->get();
    
        $packages->transform(function ($package) {

            $categories = $package->categories->map(function ($packageCategory) {
                return [
                    'id' => $packageCategory->category->id,
                    'name' => $packageCategory->category->name,
                ];
            })->unique()->values();

            return [
                'id' => $package->id,
                'name' => $package->name,
                'item_type' => $package->item_type,
                'categories' => $categories,
                'base_pricing' => $package->basePricing ? [
                    'currency' => $package->basePricing->currency,
                    'availability' => $package->basePricing->availability,
                    'start_date' => $package->basePricing->start_date,
                    'end_date' => $package->basePricing->end_date,
                    'variations' => $package->basePricing->variations->map(function ($variation) {
                        return [
                            'id' => $variation->id,
                            'name' => $variation->name,
                            'regular_price' => $variation->regular_price,
                            'sale_price' => $variation->sale_price,
                            'max_guests' => $variation->max_guests,
                            'description' => $variation->description,
                        ];
                    })->toArray(),
                ] : null,
            ];
        });
    
        return $packages;
    }

}
