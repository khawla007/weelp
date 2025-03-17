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
    public function homeSearch(Request $request)
    {
        $request->validate([
            'location'   => 'required|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        $location = $request->location;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $quantity = $request->quantity;

        $cityIds = $this->getCityIdsFromLocationSlug($location);

        $activities = $this->searchActivities($cityIds, $startDate, $endDate, $quantity);
        $itineraries = $this->searchItineraries($cityIds, $startDate, $endDate, $quantity);
        $packages = $this->searchPackages($cityIds, $startDate, $endDate, $quantity);

        $results = [
            'activities' => $activities,
            'itineraries' => $itineraries,
            'packages' => $packages,
        ];

        if ($activities->isEmpty() && $itineraries->isEmpty() && $packages->isEmpty()) {
            return response()->json([
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
                $query->where('regions.id', $region->id); // ✅ Table prefix added here
            })->pluck('id')->toArray();
    
            $cityIds = array_merge($cityIds, $regionCities);
        }
    
        return $cityIds;
    }

    // ✅ FIXED Activity Availability Reference
    private function searchActivities($cityIds, $startDate, $endDate, $quantity)
    {
        $query = Activity::whereHas('locations', function ($q) use ($cityIds) {
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

        return $query->get();
    }


    // ✅ FIXED Itineraries Availability Reference
    private function searchItineraries($cityIds, $startDate, $endDate, $quantity)
    {
        $query = Itinerary::whereHas('locations', function ($q) use ($cityIds) {
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

        return $query->get();
    }

    // ✅ FIXED Packages Availability Reference
    private function searchPackages($cityIds, $startDate, $endDate, $quantity)
    {
        $query = Package::whereHas('locations', function ($q) use ($cityIds) {
            $q->whereIn('city_id', $cityIds);
        });

        // if ($startDate && $endDate) {
        //     $query->where('date_based_availability', true)
        //         ->whereHas('package_availabilities', function ($q) use ($startDate, $endDate) {
        //             $q->where('start_date', '<=', $startDate)
        //                 ->where('end_date', '>=', $endDate);
        //         });
        // }

        if ($startDate && $endDate) {
            $query->whereHas('availability', function ($q) use ($startDate, $endDate) {
                $q->where('date_based_package', true)
                    ->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $endDate);
            });
        }

        // if ($quantity) {
        //     $query->where(function ($q) use ($quantity) {
        //         $q->where('quantity_based_availability', false)
        //             ->orWhere(function ($q) use ($quantity) {
        //                 $q->where('quantity_based_availability', true)
        //                     ->where('max_quantity', '>=', $quantity);
        //             });
        //     });
        // }

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

        return $query->get();
    }

    public function getRegionsAndCities()
    {
        // Get all regions
        $regions = Region::select('id', 'name')
            ->get()
            ->map(function ($region) {
                return [
                    'id' => 'region_' . $region->id, // Prefix to differentiate region and city
                    'name' => $region->name,
                    'type' => 'region'
                ];
            });

        // Get all cities
        $cities = City::select('id', 'name')
            ->get()
            ->map(function ($city) {
                return [
                    'id' => 'city_' . $city->id, // Prefix to differentiate region and city
                    'name' => $city->name,
                    'type' => 'city'
                ];
            });

        // Merge and sort
        $list = $regions->merge($cities)->sortBy('name')->values();

        return response()->json([
            'data' => $list
        ]);
    }

}
