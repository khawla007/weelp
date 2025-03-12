<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\City;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class PublicItineraryController extends Controller
{

    // ---------------------old basic code to Get all itineraries------------------------

    // public function index(): JsonResponse
    // {
    //     $itineraries = Itinerary::with([
    //         'schedules.activities',
    //         'schedules.transfers',
    //         'basePricing.variations',
    //         'basePricing.blackoutDates',
    //         'inclusionsExclusions',
    //         'mediaGallery',
    //         'seo',
    //         'categories',
    //         'attributes',
    //         'tags'
    //     ])->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $itineraries
    //     ]);
    // }
    
    //  -------------------Code to grt itineraries with location details-------------------

    public function index(): JsonResponse
    {
        $itineraries = Itinerary::with([
            'city.state.country.regions',
            'schedules.activities',
            'schedules.transfers',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery',
            'seo',
            'categories',
            'attributes',
            'tags'
        ])->get()->map(function ($itinerary) {
            return [
                'id' => $itinerary->id,
                'name' => $itinerary->name,
                'slug' => $itinerary->slug,
                'description' => $itinerary->description,
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
                'categories' => $itinerary->categories->pluck('name')->toArray(),
                'attributes' => $itinerary->attributes->map(function ($attribute) {
                    return [
                        'name' => $attribute->name,
                        'value' => $attribute->pivot->value ?? null,
                    ];
                }),
                'tags' => $itinerary->tags->pluck('name')->toArray(),
                'media_gallery' => $itinerary->mediaGallery->pluck('url')->toArray(),
                'seo' => $itinerary->seo ? [
                    'meta_title' => $itinerary->seo->meta_title,
                    'meta_description' => $itinerary->seo->meta_description,
                    'keywords' => $itinerary->seo->keywords,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $itineraries
        ]);
    }

    // --------------------------Code with location detail and with pagination to get all itineraries-------------------------

    // public function index(): JsonResponse
    // {
    //     $page = request('page', 1);

    //     $itineraries = Itinerary::with([
    //             'city.state.country.regions',
    //             'schedules.activities',
    //             'schedules.transfers',
    //             'basePricing.variations',
    //             'basePricing.blackoutDates',
    //             'inclusionsExclusions',
    //             'mediaGallery',
    //             'seo',
    //             'categories',
    //             'attributes',
    //             'tags'
    //         ])
    //         ->orderBy('id', 'asc')
    //         ->paginate(4, ['*'], 'page', $page)
    //         ->appends(request()->query());

    //     $data = $itineraries->map(function ($itinerary) {
    //         return [
    //             'id' => $itinerary->id,
    //             'name' => $itinerary->name,
    //             'slug' => $itinerary->slug,
    //             'description' => $itinerary->description,
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
    //             'base_pricing' => $itinerary->basePricing,
    //             'categories' => $itinerary->categories->pluck('name')->toArray(),
    //             'attributes' => $itinerary->attributes->map(function ($attribute) {
    //                 return [
    //                     'id' => $attribute->attribute->id, // Attribute ID
    //                     'name' => $attribute->attribute->name,
    //                     'attribute_value' => $attribute->attribute_value,
    //                 ];
    //             }),
    //             'tags' => $itinerary->tags->pluck('name')->toArray(),
    //             'media_gallery' => $itinerary->mediaGallery->pluck('url')->toArray(),
    //             'seo' => $itinerary->seo ? [
    //                 'meta_title' => $itinerary->seo->meta_title,
    //                 'meta_description' => $itinerary->seo->meta_description,
    //                 'keywords' => $itinerary->seo->keywords,
    //             ] : null,
    //         ];
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'data' => $data,
    //         'current_page' => $itineraries->currentPage(),
    //         'last_page' => $itineraries->lastPage(),
    //         'per_page' => $itineraries->perPage(),
    //         'total' => $itineraries->total(),
    //         'next_page_url' => $itineraries->nextPageUrl(),
    //         'prev_page_url' => $itineraries->previousPageUrl(),
    //     ]);
    // }

    // ---------------------Old Code to get Get single itinerary by slug-------------------

    // public function show($slug): JsonResponse
    // {
    //     $itinerary = Itinerary::with([
    //         'schedules.activities.activity', // Activity relation ke saath naam include karega
    //         'schedules.transfers.transfer', // Transfer relation ke saath naam include karega
    //         'basePricing.variations',
    //         'basePricing.blackoutDates',
    //         'inclusionsExclusions',
    //         'mediaGallery',
    //         'seo',
    //         'categories',
    //         'attributes.attribute',
    //         'tags'
    //     ])->where('slug', $slug)->first();

    //     if (!$itinerary) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Itinerary not found'
    //         ], 404);
    //     }

    //     $formattedItinerary = [
    //         'id' => $itinerary->id,
    //         'name' => $itinerary->name,
    //         'slug' => $itinerary->slug,
    //         // 'city' => $itinerary->city ? $itinerary->city->name : null,
    //         'city' => $itinerary->city ? [
    //             'id' => $itinerary->city->id,
    //             'name' => $itinerary->city->name,
    //         ] : null,
    //         'schedules' => $itinerary->schedules->map(function ($schedule) {
    //             return [
    //                 'day' => $schedule->day,
    //                 'activities' => $schedule->activities->map(function ($activity) {
    //                     return [
    //                         'id' => $activity->id,
    //                         'name' => $activity->activity ? $activity->activity->name : null,
    //                         'start_time' => $activity->start_time,
    //                         'end_time' => $activity->end_time,
    //                         'notes' => $activity->notes,
    //                         'price' => $activity->price,
    //                         'include_in_package' => $activity->include_in_package,
    //                     ];
    //                 }),
    //                 'transfers' => $schedule->transfers->map(function ($transfer) {
    //                     return [
    //                         'id' => $transfer->id,
    //                         'name' => $transfer->transfer ? $transfer->transfer->name : null,
    //                         'start_time' => $transfer->start_time,
    //                         'end_time' => $transfer->end_time,
    //                         'pickup_location' => $transfer->pickup_location,
    //                         'dropoff_location' => $transfer->dropoff_location,
    //                         'pax' => $transfer->pax,
    //                         'price' => $transfer->price,
    //                         'include_in_package' => $transfer->include_in_package,
    //                     ];
    //                 }),
    //             ];
    //         }),
    //         'categories' => $itinerary->categories->map(function ($category) {
    //             return [
    //                 'id' => $category->id,
    //                 'name' => $category->name,
    //             ];
    //         })->toArray(),

    //         'attributes' => $itinerary->attributes->map(function ($attribute) {
    //             return [
    //                 'id' => $attribute->attribute->id, // Attribute ID
    //                 'name' => $attribute->attribute->name,
    //                 'attribute_value' => $attribute->attribute_value,
    //             ];
    //         }),

    //         'tags' => $itinerary->tags->map(function ($tag) {
    //             return [
    //                 'id' => $tag->id,
    //                 'name' => $tag->name,
    //             ];
    //         })->toArray(),
    //         'base_pricing' => $itinerary->basePricing,
    //         'inclusions_exclusions' => $itinerary->inclusionsExclusions,
    //         'media_gallery' => $itinerary->mediaGallery,
    //         'seo' => $itinerary->seo,
    //     ];

    //     return response()->json([
    //         'success' => true,
    //         'data' => $formattedItinerary
    //     ]);
    // }

    // ---------------------------New Code to get Single itinerary with location details--------------------------

    public function show($slug): JsonResponse
    {
        $itinerary = Itinerary::with([
            'city.state.country.regions', 
            'schedules.activities.activity',
            'schedules.transfers.transfer',
            'basePricing.variations',
            'basePricing.blackoutDates',
            'inclusionsExclusions',
            'mediaGallery',
            'seo',
            'categories',
            'attributes.attribute',
            'tags'
        ])->where('slug', $slug)->first();

        if (!$itinerary) {
            return response()->json([
                'success' => false,
                'message' => 'Itinerary not found'
            ], 404);
        }

        $formattedItinerary = [
            'id' => $itinerary->id,
            'name' => $itinerary->name,
            'slug' => $itinerary->slug,
            'description' => $itinerary->description,
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
            'schedules' => $itinerary->schedules->map(function ($schedule) {
                return [
                    'day' => $schedule->day,
                    'activities' => $schedule->activities->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'name' => $activity->activity ? $activity->activity->name : null,
                            'start_time' => $activity->start_time,
                            'end_time' => $activity->end_time,
                            'notes' => $activity->notes,
                            'price' => $activity->price,
                            'include_in_package' => $activity->include_in_package,
                        ];
                    }),
                    'transfers' => $schedule->transfers->map(function ($transfer) {
                        return [
                            'id' => $transfer->id,
                            'name' => $transfer->transfer ? $transfer->transfer->name : null,
                            'start_time' => $transfer->start_time,
                            'end_time' => $transfer->end_time,
                            'pickup_location' => $transfer->pickup_location,
                            'dropoff_location' => $transfer->dropoff_location,
                            'pax' => $transfer->pax,
                            'price' => $transfer->price,
                            'include_in_package' => $transfer->include_in_package,
                        ];
                    }),
                ];
            }),
            'categories' => $itinerary->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            })->toArray(),
            'attributes' => $itinerary->attributes->map(function ($attribute) {
                return [
                    'id' => $attribute->attribute->id, // Attribute ID
                    'name' => $attribute->attribute->name,
                    'attribute_value' => $attribute->attribute_value,
                ];
            }),
            'tags' => $itinerary->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ];
            })->toArray(),
            'base_pricing' => $itinerary->basePricing,
            'inclusions_exclusions' => $itinerary->inclusionsExclusions,
            'media_gallery' => $itinerary->mediaGallery,
            'seo' => $itinerary->seo,
        ];

        return response()->json([
            'success' => true,
            'data' => $formattedItinerary
        ]);
}

}
