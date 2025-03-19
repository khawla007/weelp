<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
use App\Models\City;
use App\Models\Region;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PublicShopController extends Controller
{
    public function index()
    {
        $perPage = 8;
        $currentPage = request()->get('page', 1);

        $activities = Activity::with([
            'categories.category',
            'locations.city.state.country.regions',
            'pricing',
            'groupDiscounts',
            'earlyBirdDiscount',
        ])->get()->map(fn ($activity) => $this->formatItem($activity, 'activity'));

        $itineraries = Itinerary::with([
            'categories.category',
            'locations.city.state.country.regions',
            'basePricing.variations',
        ])->get()->map(fn ($itinerary) => $this->formatItem($itinerary, 'itinerary'));

        $packages = Package::with([
            'categories.category',
            'locations.city.state.country.regions',
            'basePricing.variations',
        ])->get()->map(fn ($package) => $this->formatItem($package, 'package'));

        // Collecting the list of categories of all shop items.
        $categoriesList = collect([]);

        $categoriesList = $categoriesList
        ->merge($activities->flatMap(function ($activity) {
            return $activity['categories'];
        }))
        ->merge($itineraries->flatMap(function ($itinerary) {
            return $itinerary['categories'];
        }))
        ->merge($packages->flatMap(function ($package) {
            return $package['categories'];
        }))
        ->unique('id')
        ->values();

        // Collectiing the list of city and region of all shop items
        $locationList = collect([]);

        $locationList = $locationList
            ->merge($activities->flatMap(function ($activity) {
                return $activity['locations']->map(function ($location) {
                    return [
                        'id' => $location['city_id'],
                        'name' => $location['city'],
                        'type' => 'city',
                    ];
                })->merge(
                    $activity['locations']->map(function ($location) {
                        return [
                            'id' => $location['region_id'],
                            'name' => $location['region'],
                            'type' => 'region', 
                        ];
                    })
                );
            }))
            ->merge($itineraries->flatMap(function ($itinerary) {
                return $itinerary['locations']->map(function ($location) {
                    return [
                        'id' => $location['city_id'],
                        'name' => $location['city'],
                        'type' => 'city',
                    ];
                })->merge(
                    $itinerary['locations']->map(function ($location) {
                        return [
                            'id' => $location['region_id'],
                            'name' => $location['region'],
                            'type' => 'region',
                        ];
                    })
                );
            }))
            ->merge($packages->flatMap(function ($package) {
                return $package['locations']->map(function ($location) {
                    return [
                        'id' => $location['city_id'],
                        'name' => $location['city'],
                        'type' => 'city',
                    ];
                })->merge(
                    $package['locations']->map(function ($location) {
                        return [
                            'id' => $location['region_id'],
                            'name' => $location['region'],
                            'type' => 'region',
                        ];
                    })
                );
            }))
            ->unique('id')
            ->values();
        

        $allItems = collect()
            ->merge($activities)
            ->merge($itineraries)
            ->merge($packages);

        $paginatedItems = new LengthAwarePaginator(
            $allItems->forPage($currentPage, $perPage)->values(),
            $allItems->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        if ($currentPage > $paginatedItems->lastPage()) {
            return response()->json([
                'message' => 'No more items'
            ], 422);
        }

        return response()->json([
            // 'shop_items' => $paginatedItems
            'shop_items' => [
                'data' => $paginatedItems->items(),
                'categories_list' => $categoriesList,
                'location_list' => $locationList,
                'current_page' => $paginatedItems->currentPage(),
                'per_page' => $paginatedItems->perPage(),
                'total' => $paginatedItems->total(),
                'last_page' => $paginatedItems->lastPage(),
            ]
        ]);
    }

    private function formatItem($item, $type)
    {
        $price = null;
        $groupDiscount = null;
        $earlyBirdDiscount = null;
        $variations = null;

        switch ($type) {
            case 'activity':
                $price = $item->pricing ? [
                    'base_price' => $item->pricing->base_price,
                    'currency' => $item->pricing->currency,
                ] : null;
                $groupDiscount = $item->groupDiscounts ? $item->groupDiscounts->map(function ($discount) {
                    return [
                        'min_people' => $discount->min_people,
                        'discount_amount' => $discount->discount_amount,
                        'discount_type' => $discount->discount_type,
                    ];
                }) : [];
                $earlyBirdDiscount = $item->earlyBirdDiscount ? [
                    'days_before_start' => $item->earlyBirdDiscount->first()?->days_before_start,
                    'discount_amount' => $item->earlyBirdDiscount->first()?->discount_amount,
                    'discount_type' => $item->earlyBirdDiscount->first()?->discount_type,
                ] : null;
                break;
            case 'itinerary':
            case 'package':
                $price = $item->basePricing ? [
                    'currency' => $item->basePricing->currency,
                    'availability' => $item->basePricing->availability,
                    'start_date' => $item->basePricing->start_date,
                    'end_date' => $item->basePricing->end_date,
                    'variations' => $item->basePricing->variations->map(function ($variation) {
                        return [
                            'id' => $variation->id,
                            'name' => $variation->name,
                            'regular_price' => $variation->regular_price,
                            'sale_price' => $variation->sale_price,
                            'max_guests' => $variation->max_guests,
                            'description' => $variation->description,
                        ];
                    })->toArray(),
                ] : null;
        }

        return [
            'id' => $item->id,
            'type' => $type,
            'name' => $item->name,
            'price' => $price,
            'group_discount' => $groupDiscount,
            'early_bird_discount' => $earlyBirdDiscount,
            'categories' => $item->categories->map(fn ($category) => [
                'id' => $category->category->id ?? null,
                'name' => $category->category->name ?? null,
            ])->filter(),

            'locations' => $item->locations->map(function ($location) {
                $city = $location->city;
                return [
                    'city_id' => $city->id,
                    'city' => $city->name,
                    'state_id' => $city->state?->id,
                    'state' => $city->state?->name,
                    'country_id' => $city->state?->country?->id,
                    'country' => $city->state?->country?->name,
                    'region_id' => $city->state?->country?->regions->first()?->id,
                    'region' => $city->state?->country?->regions->first()?->name,
                ];
            }),
        ];
    }

}
