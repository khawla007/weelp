<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Place;
use App\Models\PlaceLocationDetail;
use App\Models\PlaceTravelInfo;
use App\Models\PlaceSeason;
use App\Models\PlaceEvent;
use App\Models\PlaceAdditionalInfo;
use App\Models\PlaceFaq;
use App\Models\PlaceSeo;

class PlaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Pagination parameters
        $perPage = (int) $request->get('per_page', 1);
        $page = (int) $request->get('page', 1);
    
        // Filters
        $cityId = $request->get('city_id');
        $featured = $request->get('featured');
        $search = $request->get('search');
    
        $query = Place::query()
            ->with([
                'locationDetails',
                'travelInfo',
                'seasons',
                'events',
                'additionalInfo',
                'faqs',
                'seo',
            ])
            ->when($cityId, fn($q) => $q->where('city_id', $cityId))
            ->when($featured !== null, fn($q) => $q->where('featured_destination', (bool)$featured))
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"));
    
        // Sorting
        $sortBy = $request->get('sort_by', 'id_desc');
        switch ($sortBy) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'id_asc':
                $query->orderBy('id', 'asc');
                break;
            case 'id_desc':
            default:
                $query->orderBy('id', 'desc');
                break;
        }
    
        // Get total count
        $total = $query->count();
        // Calculate total pages
        $totalPages = (int) ceil($total / $perPage);
    
        // Get paginated records
        $items = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
    
        return response()->json([
            'success' => true,
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
        ]);
    }    

    //for select dropdown
    public function getPlaceDropdown()
    {
        $places = Place::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $places
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'city_id' => 'required|integer|exists:cities,id',
                'name' => 'required|string|max:255',
                'place_code' => 'required|string|max:10|unique:places,place_code',
                'slug' => 'required|string|max:255|unique:places,slug',
                'description' => 'nullable|string',
                'feature_image' => 'nullable|url',
                'featured_destination' => 'boolean',
    
                'location_detail' => 'required|array',
                'location_detail.latitude' => 'required|string',
                'location_detail.longitude' => 'required|string',
                'location_detail.population' => 'nullable|integer',
                'location_detail.currency' => 'nullable|string',
                'location_detail.timezone' => 'nullable|string',
                'location_detail.language' => 'nullable|string',
                'location_detail.local_cuisine' => 'nullable|string',
    
                'travel_info' => 'required|array',
                'travel_info.airport' => 'nullable|string',
                'travel_info.public_transportation' => 'nullable|string',
                'travel_info.taxi_available' => 'boolean',
                'travel_info.rental_cars_available' => 'boolean',
                'travel_info.hotels' => 'boolean',
                'travel_info.hostels' => 'boolean',
                'travel_info.apartments' => 'boolean',
                'travel_info.resorts' => 'boolean',
                'travel_info.visa_requirements' => 'nullable|string',
                'travel_info.best_time_to_visit' => 'nullable|string',
                'travel_info.travel_tips' => 'nullable|string',
                'travel_info.safety_information' => 'nullable|string',
    
                'seasons' => 'array',
                'seasons.*.name' => 'required|string',
                'seasons.*.months' => 'required|string',
                'seasons.*.weather' => 'nullable|string',
                'seasons.*.activities' => 'nullable|string',
    
                'events' => 'array',
                'events.*.name' => 'required|string',
                'events.*.type' => 'nullable|string',
                'events.*.date_time' => 'nullable|date',
                'events.*.location' => 'nullable|string',
                'events.*.description' => 'nullable|string',
    
                'additional_infos' => 'array',
                'additional_infos.*.title' => 'required|string',
                'additional_infos.*.content' => 'nullable|string',
    
                'faqs' => 'array',
                'faqs.*.question' => 'required|string',
                'faqs.*.answer' => 'required|string',
    
                'seo' => 'required|array',
                'seo.meta_title' => 'nullable|string|max:255',
                'seo.meta_description' => 'nullable|string',
                'seo.keywords' => 'nullable|string',
                'seo.og_image_url' => 'nullable|url',
                'seo.canonical_url' => 'nullable|url',
                'seo.schema_type' => 'nullable|string',
                'seo.schema_data' => 'nullable|json',
            ], [
                'slug.unique' => 'Place with this slug already exists.',
                'place_code.unique' => 'Place code must be unique.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error. Place could not be created.',
                'errors' => $e->errors()
            ], 422);
        }

        // 1. Create Place
        $place = Place::create([
            'city_id' => $validated['city_id'],
            'name' => $validated['name'],
            'place_code' => $validated['place_code'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'feature_image' => $validated['feature_image'] ?? null,
            'featured_destination' => $validated['featured_destination'] ?? false,
        ]);

        // 2. Location Details
        PlaceLocationDetail::create([
            'place_id' => $place->id,
            'latitude' => $validated['location_detail']['latitude'],
            'longitude' => $validated['location_detail']['longitude'],
            'population' => $validated['location_detail']['population'] ?? null,
            'currency' => $validated['location_detail']['currency'] ?? null,
            'timezone' => $validated['location_detail']['timezone'] ?? null,
            'language' => $validated['location_detail']['language'] ?? null,
            'local_cuisine' => $validated['location_detail']['local_cuisine'] ?? null,
        ]);

        // 3. Travel Info
        PlaceTravelInfo::create([
            'place_id' => $place->id,
            'airport' => $validated['travel_info']['airport'] ?? null,
            'public_transportation' => $validated['travel_info']['public_transportation'] ?? null,
            'taxi_available' => $validated['travel_info']['taxi_available'] ?? false,
            'rental_cars_available' => $validated['travel_info']['rental_cars_available'] ?? false,
            'hotels' => $validated['travel_info']['hotels'] ?? false,
            'hostels' => $validated['travel_info']['hostels'] ?? false,
            'apartments' => $validated['travel_info']['apartments'] ?? false,
            'resorts' => $validated['travel_info']['resorts'] ?? false,
            'visa_requirements' => $validated['travel_info']['visa_requirements'] ?? null,
            'best_time_to_visit' => $validated['travel_info']['best_time_to_visit'] ?? null,
            'travel_tips' => $validated['travel_info']['travel_tips'] ?? null,
            'safety_information' => $validated['travel_info']['safety_information'] ?? null,
        ]);
        

        // 4. Seasons
        if (!empty($validated['seasons'])) {
            foreach ($validated['seasons'] as $season) {
                PlaceSeason::create([
                    'place_id' => $place->id,
                    'name' => $season['name'],
                    'months' => $season['months'],
                    'weather' => $season['weather'] ?? null,
                    'activities' => $season['activities'] ?? null,
                ]);
            }
        }

        // 5. Events
        if (!empty($validated['events'])) {
            foreach ($validated['events'] as $event) {
                PlaceEvent::create([
                    'place_id' => $place->id,
                    'name' => $event['name'],
                    'type' => $event['type'] ?? null,
                    'date_time' => $event['date_time'] ?? null,
                    'location' => $event['location'] ?? null,
                    'description' => $event['description'] ?? null,
                ]);
            }
        }

        // 6. Additional Infos
        if (!empty($validated['additional_infos'])) {
            foreach ($validated['additional_infos'] as $info) {
                PlaceAdditionalInfo::create([
                    'place_id' => $place->id,
                    'title' => $info['title'],
                    'content' => $info['content'] ?? null,
                ]);
            }
        }

        // 7. FAQs
        if (!empty($validated['faqs'])) {
            $questionNumber = 1;
            foreach ($validated['faqs'] as $faq) {
                PlaceFaq::create([
                    'place_id' => $place->id,
                    'question_number' => $questionNumber++,
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ]);
            }
        }

        // 8. SEO
        PlaceSeo::create([
            'place_id' => $place->id,
            'meta_title' => $validated['seo']['meta_title'] ?? null,
            'meta_description' => $validated['seo']['meta_description'] ?? null,
            'keywords' => $validated['seo']['keywords'] ?? null,
            'og_image_url' => $validated['seo']['og_image_url'] ?? null,
            'canonical_url' => $validated['seo']['canonical_url'] ?? null,
            'schema_type' => $validated['seo']['schema_type'] ?? null,
            'schema_data' => $validated['seo']['schema_data'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Place created successfully.',
            'data' => $place
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $place = Place::with([
            'locationDetails',
            'travelInfo',
            'seasons',
            'events',
            'additionalInfo',
            'faqs',
            'seo'
        ])->find($id);
    
        if (!$place) {
            return response()->json([
                'success' => false,
                'message' => 'Place not found.',
            ], 404);
        }
    
        return response()->json([
            'success' => true,
            'data' => $place
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $place = Place::with([
            'locationDetail',
            'travelInfo',
            'seo',
            'seasons',
            'events',
            'additionalInfos',
            'faqs'
        ])->findOrFail($id);
    
        // Update main place table (only provided fields)
        $place->fill($request->only([
            'city_id',
            'name',
            'place_code',
            'slug',
            'description',
            'feature_image',
            'featured_destination'
        ]))->save();
    
        // Update location_detail
        if ($request->has('location_detail')) {
            $place->locationDetail->update($request->location_detail);
        }
    
        // Update travel_info
        if ($request->has('travel_info')) {
            $place->travelInfo->update($request->travel_info);
        }
    
        // Update SEO
        if ($request->has('seo')) {
            $place->seo->update($request->seo);
        }
    
        // Partial Update: Seasons (multiple)
        if ($request->has('seasons')) {
            foreach ($request->seasons as $seasonData) {
                if (isset($seasonData['id'])) {
                    $season = $place->seasons()->where('id', $seasonData['id'])->first();
                    if ($season) {
                        $season->update($seasonData);
                    }
                }
            }
        }
    
        // Partial Update: Events (multiple)
        if ($request->has('events')) {
            foreach ($request->events as $eventData) {
                if (isset($eventData['id'])) {
                    $event = $place->events()->where('id', $eventData['id'])->first();
                    if ($event) {
                        $event->update($eventData);
                    }
                }
            }
        }
    
        // Partial Update: Additional Infos (multiple)
        if ($request->has('additional_infos')) {
            foreach ($request->additional_infos as $infoData) {
                if (isset($infoData['id'])) {
                    $info = $place->additionalInfos()->where('id', $infoData['id'])->first();
                    if ($info) {
                        $info->update($infoData);
                    }
                }
            }
        }
    
        // Partial Update: FAQs (multiple)
        if ($request->has('faqs')) {
            foreach ($request->faqs as $faqData) {
                if (isset($faqData['id'])) {
                    $faq = $place->faqs()->where('id', $faqData['id'])->first();
                    if ($faq) {
                        $faq->update($faqData);
                    }
                }
            }
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Place updated successfully.',
            'data' => $place->fresh([
                'locationDetail', 'travelInfo', 'seo',
                'seasons', 'events', 'additionalInfos', 'faqs'
            ])
        ]);
    }    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
