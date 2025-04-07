<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\ItineraryInfomation;
use App\Models\ItineraryLocation;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryActivity;
use App\Models\ItineraryTransfer;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryPriceVariation;
use App\Models\ItineraryBlackoutDate;
use App\Models\ItineraryInclusionExclusion;
use App\Models\ItineraryMediaGallery;
use App\Models\ItineraryCategory;
use App\Models\ItineraryAttribute;
use App\Models\ItineraryTag;
use App\Models\ItineraryFaq;
use App\Models\ItinerarySeo;
use App\Models\ItineraryAvailability;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Validator;


class ItineraryController extends Controller
{

    /**
     * Display a listing of the itineraries.
    */
    public function index(Request $request)
    {
        $perPage = 3; 
        $page = $request->get('page', 1); 

        $categorySlug = $request->get('category');
        $difficulty = $request->get('difficulty_level');
        $duration = $request->get('duration');
        $ageGroup = $request->get('age_restriction');
        $season = $request->get('season');
        $minPrice = $request->get('min_price', 0);
        $maxPrice = $request->get('max_price');
        $sortBy = $request->get('sort_by', 'id_desc'); // Default: Newest First

        $category = $categorySlug ? Category::where('slug', $categorySlug)->first() : null;
        $categoryId = $category ? $category->id : null;

        $difficultyAttr = Attribute::where('slug', 'difficulty-level')->first();
        $durationAttr = Attribute::where('slug', 'duration')->first();
        $ageGroupAttr = Attribute::where('slug', 'age-restriction')->first();

        $query = Itinerary::query()
            ->select('itineraries.*')  
            ->join('itinerary_base_pricing', 'itinerary_base_pricing.itinerary_id', '=', 'itineraries.id') 
            ->join('itinerary_price_variations', 'itinerary_price_variations.base_pricing_id', '=', 'itinerary_base_pricing.id')
            ->with([
                'categories.category', 
                'locations.city', 
                'basePricing.variations', 
                'attributes.attribute:id,name'
            ])
            ->when($categoryId, fn($query) => 
                $query->whereHas('categories', fn($q) => 
                    $q->where('category_id', $categoryId)
                )
            )
            ->when($difficulty && $difficultyAttr, fn($query) => 
                $query->whereHas('attributes', fn($q) => 
                    $q->where('attribute_id', $difficultyAttr->id)
                    ->where('attribute_value', $difficulty)
                )
            )
            ->when($duration && $durationAttr, fn($query) => 
                $query->whereHas('attributes', fn($q) => 
                    $q->where('attribute_id', $durationAttr->id)
                    ->where('attribute_value', $duration)
                )
            )
            ->when($ageGroup && $ageGroupAttr, fn($query) => 
                $query->whereHas('attributes', fn($q) => 
                    $q->where('attribute_id', $ageGroupAttr->id)
                    ->where('attribute_value', $ageGroup)
                )
            )
            ->when($season, fn($query) => 
                $query->whereHas('seasonalPricing', fn($q) => 
                    $q->where('season_name', $season)
                )
            )
            ->when($maxPrice !== null, fn($query) => 
                $query->whereHas('basePricing', fn($q) => 
                    $q->whereHas('variations', fn($q2) => 
                        $q2->whereBetween('sale_price', [$minPrice, $maxPrice])
                    )
                )
            );

        // Sorting logic
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('itinerary_price_variations.sale_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('itinerary_price_variations.sale_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('itineraries.name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('itineraries.name', 'desc');
                break;
            case 'id_asc':
                $query->orderBy('itineraries.id', 'asc');
                break;
            case 'id_desc':
                $query->orderBy('itineraries.id', 'desc');
                break;
            case 'featured':
                $query->orderByRaw('itineraries.featured_itinerary DESC');
                break;
            default:
                $query->orderBy('itineraries.id', 'desc');
                break;
        }

        $allItems = $query->get();
        $paginatedItems = $allItems->forPage($page, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginatedItems->values(),
            'current_page' => (int) $page,
            'per_page' => $perPage,
            'total' => $allItems->count(),
        ], 200);
    }


    /**
     * Store a newly created Itinerary in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'featured_itinerary' => 'required|boolean',
            'private_itinerary' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $itineraryData = $request->only(['name', 'description', 'featured_itinerary', 'private_itinerary']);
        $itineraryData['slug'] = Str::slug($itineraryData['name']);
        $itinerary = Itinerary::create($itineraryData);

        // Create related records (simplified version, expand as needed)
        ItineraryLocation::create([
            'itinerary_id' => $itinerary->id,
            'city_id' => $request->city_id, // Assuming city_id is part of the request
        ]);

        // More related models like ItinerarySchedule, ItineraryActivity, etc. can be created similarly
        // Example for schedule creation
        for ($day = 1; $day <= 3; $day++) {
            $schedule = ItinerarySchedule::create([
                'itinerary_id' => $itinerary->id,
                'day' => $day,
            ]);
            
            // Add activities or transfers based on your need
        }

        return response()->json($itinerary, 201);
    }


    /**
     * Create or Update Itinerary.
    */
    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:itineraries,slug,' . $id,
            'description' => 'nullable|string',
            'featured_itinerary' => 'boolean',
            'private_itinerary' => 'boolean',
            'locations' => 'nullable|array',
            'schedules' => 'nullable|array',
            'activities' => 'nullable|array',
            'transfers' => 'nullable|array',
            'pricing' => 'nullable|array',
            'price_variations' => 'nullable|array',
            'blackout_dates' => 'nullable|array',
            'inclusions_exclusions' => 'nullable|array',
            'media_gallery' => 'nullable|array',
            'seo' => 'nullable|array',
            'categories' => 'nullable|array',
            'attributes' => 'nullable|array',
            'tags' => 'nullable|array',
            'availability' => 'nullable|array',
        ]);
    
        try {
            DB::beginTransaction();
    
            // Create or Update Itinerary
            $itinerary = Itinerary::updateOrCreate(
                ['id' => $id], 
                [
                    'name' => $request->name,
                    'slug' => $request->slug,
                    'description' => $request->description,
                    'featured_itinerary' => $request->featured_itinerary ?? false,
                    'private_itinerary' => $request->private_itinerary ?? false,
                ]
            );
    
            // Handle Locations
            if ($request->has('locations')) {
                $itinerary->locations()->delete();
                foreach ($request->locations as $location) {
                    ItineraryLocation::create([
                        'itinerary_id' => $itinerary->id,
                        'city_id' => $location['city_id'],
                    ]);
                }
            }
    
            // // Handle Schedules
            // $scheduleMap = [];
            // if ($request->has('schedules')) {
            //     $itinerary->schedules()->delete();
            //     foreach ($request->schedules as $schedule) {
            //         $newSchedule = ItinerarySchedule::create([
            //             'itinerary_id' => $itinerary->id,
            //             'day' => $schedule['day'],
            //         ]);
            //         $scheduleMap[$schedule['day']] = $newSchedule->id;
            //     }
            // }

            // // Handle Activities
            // if ($request->has('activities')) {
            //     foreach ($request->activities as $activity) {
            //         // Check if the schedule exists for the given day
            //         $scheduleId = $scheduleMap[$activity['day']] ?? null;
            //         if ($scheduleId) {
            //             ItineraryActivity::create([
            //                 'schedule_id' => $scheduleId,
            //                 'activity_id' => $activity['activity_id'],
            //                 'start_time' => $activity['start_time'],
            //                 'end_time' => $activity['end_time'],
            //                 'notes' => $activity['notes'],
            //                 'price' => $activity['price'],
            //                 'include_in_package' => $activity['include_in_package'],
            //             ]);
            //         }
            //     }
            // }
    
            // // Handle Transfers
            // if ($request->has('transfers')) {
            //     foreach ($request->transfers as $transfer) {
            //         // Check if the schedule exists for the given day
            //         $scheduleId = $scheduleMap[$transfer['day']] ?? null;
            //         if ($scheduleId) {
            //             ItineraryTransfer::create([
            //                 'schedule_id' => $scheduleId,
            //                 'transfer_id' => $transfer['transfer_id'],
            //                 'start_time' => $transfer['start_time'],
            //                 'end_time' => $transfer['end_time'],
            //                 'notes' => $transfer['notes'],
            //                 'price' => $transfer['price'],
            //                 'include_in_package' => $transfer['include_in_package'],
            //                 'pickup_location' => $transfer['pickup_location'],
            //                 'dropoff_location' => $transfer['dropoff_location'],
            //                 'pax' => $transfer['pax'],
            //             ]);
            //         }
            //     }
            // }

            $scheduleMap = [];

            // Build map from existing schedules: [day => id]
            $existingSchedules = $itinerary->schedules->keyBy('day');
            
            if ($request->has('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $day = $schedule['day'];
            
                    if (isset($existingSchedules[$day])) {
                        // ✅ Use existing schedule
                        $scheduleMap[$day] = $existingSchedules[$day]->id;
                    } else {
                        // 🆕 If new day, create it
                        $newSchedule = ItinerarySchedule::create([
                            'itinerary_id' => $itinerary->id,
                            'day' => $day,
                        ]);
                        $scheduleMap[$day] = $newSchedule->id;
                    }
                }
            }

            if ($request->has('activities')) {
                $existingIds = ItineraryActivity::whereHas('schedule', fn($q) => $q->where('itinerary_id', $itinerary->id))->pluck('id')->toArray();
                $sentIds = collect($request->activities)->pluck('id')->filter()->toArray();
            
                $toDelete = array_diff($existingIds, $sentIds);
                if ($toDelete) ItineraryActivity::whereIn('id', $toDelete)->delete();
            
                foreach ($request->activities as $activity) {
                    $scheduleId = $scheduleMap[$activity['day']] ?? null;
                    if (!$scheduleId) continue;
            
                    $data = [
                        'schedule_id' => $scheduleId,
                        'activity_id' => $activity['activity_id'],
                        'start_time' => $activity['start_time'],
                        'end_time' => $activity['end_time'],
                        'notes' => $activity['notes'],
                        'price' => $activity['price'],
                        'include_in_package' => $activity['include_in_package'],
                    ];
            
                    if (!empty($activity['id']) && in_array($activity['id'], $existingIds)) {
                        ItineraryActivity::where('id', $activity['id'])->update($data);
                    } else {
                        ItineraryActivity::create($data);
                    }
                }
            }

            if ($request->has('transfers')) {
                $existingIds = ItineraryTransfer::whereHas('schedule', fn($q) => $q->where('itinerary_id', $itinerary->id))->pluck('id')->toArray();
                $sentIds = collect($request->transfers)->pluck('id')->filter()->toArray();
            
                $toDelete = array_diff($existingIds, $sentIds);
                if ($toDelete) ItineraryTransfer::whereIn('id', $toDelete)->delete();
            
                foreach ($request->transfers as $transfer) {
                    $scheduleId = $scheduleMap[$transfer['day']] ?? null;
                    if (!$scheduleId) continue;
            
                    $data = [
                        'schedule_id' => $scheduleId,
                        'transfer_id' => $transfer['transfer_id'],
                        'start_time' => $transfer['start_time'],
                        'end_time' => $transfer['end_time'],
                        'notes' => $transfer['notes'],
                        'price' => $transfer['price'],
                        'include_in_package' => $transfer['include_in_package'],
                        'pickup_location' => $transfer['pickup_location'] ?? null,
                        'dropoff_location' => $transfer['dropoff_location'] ?? null,
                        'pax' => $transfer['pax'] ?? null,
                    ];
            
                    if (!empty($transfer['id']) && in_array($transfer['id'], $existingIds)) {
                        ItineraryTransfer::where('id', $transfer['id'])->update($data);
                    } else {
                        ItineraryTransfer::create($data);
                    }
                }
            }
                        
    
            // Handle Pricing
            if ($request->has('pricing')) {
                // Create or Update the Base Pricing
                $basePricing = ItineraryBasePricing::updateOrCreate(
                    ['itinerary_id' => $itinerary->id],
                    [
                        'currency' => $request->pricing['currency'],
                        'availability' => $request->pricing['availability'],
                        'start_date' => $request->pricing['start_date'],
                        'end_date' => $request->pricing['end_date'],
                    ]
                );

                // Handle Price Variations
                if ($request->has('price_variations')) {
                    // Remove existing price variations for this base pricing
                    $basePricing->variations()->delete();

                    foreach ($request->price_variations as $variation) {
                        ItineraryPriceVariation::create([
                            'base_pricing_id' => $basePricing->id,
                            'name' => $variation['name'],
                            'regular_price' => $variation['regular_price'],
                            'sale_price' => $variation['sale_price'],
                            'max_guests' => $variation['max_guests'],
                            'description' => $variation['description'],
                        ]);
                    }
                }
            }

            // Handle Blackout Dates
            if ($request->has('blackout_dates')) {
                // Remove existing blackout dates for this base pricing
                $basePricing->blackoutDates()->delete();

                foreach ($request->blackout_dates as $date) {
                    ItineraryBlackoutDate::create([
                        'base_pricing_id' => $basePricing->id,
                        'date' => $date['date'],
                        'reason' => $date['reason'],
                    ]);
                }
            }
    
            // Handle Inclusions & Exclusions
            if ($request->has('inclusions_exclusions')) {
                $itinerary->inclusionsExclusions()->delete();
                foreach ($request->inclusions_exclusions as $ie) {
                    ItineraryInclusionExclusion::create([
                        'itinerary_id' => $itinerary->id,
                        'type' => $ie['type'],
                        'title' => $ie['title'],
                        'description' => $ie['description'],
                        // Convert 'include'/'exclude' to 1/0 if the column is integer type
                        'include_exclude' => $ie['include_exclude'] === 'include' ? 1 : 0,
                    ]);
                }
            }
    
            // Handle Media Gallery
            if ($request->has('media_gallery')) {
                $itinerary->mediaGallery()->delete();
                foreach ($request->media_gallery as $media) {
                    ItineraryMediaGallery::create([
                        'itinerary_id' => $itinerary->id,
                        'url' => $media['url'],
                    ]);
                }
            }
    
            // Handle SEO
            if ($request->has('seo')) {
                ItinerarySeo::updateOrCreate(
                    ['itinerary_id' => $itinerary->id],
                    [
                        'meta_title' => $request->seo['meta_title'],
                        'meta_description' => $request->seo['meta_description'],
                        'keywords' => $request->seo['keywords'],
                        'og_image_url' => $request->seo['og_image_url'],
                        'canonical_url' => $request->seo['canonical_url'],
                        'schema_type' => $request->seo['schema_type'],
                        'schema_data' => json_encode($request->seo['schema_data']),
                    ]
                );
            }

            // Handle Categories
            if ($request->has('categories')) {
                $itinerary->categories()->delete();
                foreach ($request->categories as $category_id) {
                    ItineraryCategory::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'category_id' => $category_id,
                    ]);
                }
            }

            // Handle Attributes
            if ($request->has('attributes')) {
                $itinerary->attributes()->delete(); 
            
                $attributes = $request->input('attributes', []); 
            
                foreach ($attributes as $attribute) {
                    ItineraryAttribute::updateOrCreate(
                        [
                            'itinerary_id' => $itinerary->id,
                            'attribute_id' => $attribute['attribute_id'],
                        ],
                        [
                            'attribute_value' => $attribute['attribute_value'],
                        ]
                    );
                }
            }

            // Handle Tags
            if ($request->has('tags')) {
                $itinerary->tags()->delete();
                foreach ($request->tags as $tag_id) {
                    ItineraryTag::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'tag_id' => $tag_id,
                    ]);
                }
            }

            // Handle Availability
            if ($request->has('availability')) {
                ItineraryAvailability::updateOrCreate(
                    ['itinerary_id' => $itinerary->id],
                    [
                        'date_based_itinerary' => $request->availability['date_based_itinerary'],
                        'start_date' => $request->availability['start_date'] ?? null,
                        'end_date' => $request->availability['end_date'] ?? null,
                        'quantity_based_itinerary' => $request->availability['quantity_based_itinerary'],
                        'max_quantity' => $request->availability['max_quantity'] ?? null,
                    ]
                );
            }
    
            DB::commit();
            return response()->json(['message' => $id ? 'Itinerary updated' : 'Itinerary created', 'itinerary' => $itinerary], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }


    /**
     * Display the specified Itinerary.
     */
    public function show(string $id)
    {
        // $itinerary = Itinerary::find($id);
        
        $itinerary = Itinerary::with([
            'locations.city',
            'categories.category',
            'attributes.attribute',
            'tags.tag',
            'schedules.transfers',
            'schedules.activities',
            'basePricing',
            'inclusionsExclusions',
            'mediaGallery',
            'availability',
            'seo',
        ])->find($id);
        
        if (!$itinerary) {
            return response()->json(['message' => 'Itinerary not found'], 404);
        }

        return response()->json($itinerary);
    }

    /**
     * Update the specified Itinerary in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:itineraries,slug,' . $id,
            'description' => 'nullable|string',
            'featured_itinerary' => 'boolean',
            'private_itinerary' => 'boolean',
            'locations' => 'nullable|array',
            'schedules' => 'nullable|array',
            'activities' => 'nullable|array',
            'transfers' => 'nullable|array',
            'pricing' => 'nullable|array',
            'price_variations' => 'nullable|array',
            'blackout_dates' => 'nullable|array',
            'inclusions_exclusions' => 'nullable|array',
            'media_gallery' => 'nullable|array',
            'seo' => 'nullable|array',
            'categories' => 'nullable|array',
            'attributes' => 'nullable|array',
            'tags' => 'nullable|array',
            'availability' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Fetch Itinerary
            $itinerary = Itinerary::findOrFail($id);

            // Update only provided fields
            $itinerary->fill($request->only([
                'name', 'slug', 'description', 'featured_itinerary', 'private_itinerary'
            ]));

            $itinerary->save();

            // Handle Locations
            if ($request->has('locations')) {
                $itinerary->locations()->delete();
                foreach ($request->locations as $location) {
                    ItineraryLocation::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'city_id' => $location['city_id'],
                        'location_type' => $location['location_type'],
                        'location_label' => $location['location_label'],
                    ]);
                }
            }

            // Handle Schedules
            if ($request->has('schedules')) {
                $itinerary->schedules()->delete();
                foreach ($request->schedules as $schedule) {
                    ItinerarySchedule::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'day' => $schedule['day'],
                        'schedule_details' => $schedule['schedule_details'] ?? '',
                    ]);
                }
            }

            // Handle Activities
            if ($request->has('activities')) {
                $itinerary->activities()->delete();
                foreach ($request->activities as $activity) {
                    ItineraryActivity::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'activity_id' => $activity['activity_id'],
                    ]);
                }
            }

            // Handle Transfers
            if ($request->has('transfers')) {
                $itinerary->transfers()->delete();
                foreach ($request->transfers as $transfer) {
                    ItineraryTransfer::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'transfer_type' => $transfer['transfer_type'],
                        'description' => $transfer['description'],
                    ]);
                }
            }

            // Handle Pricing
            if ($request->has('pricing')) {
                ItineraryBasePricing::updateOrCreate(
                    ['itinerary_id' => $itinerary->id],
                    [
                        'base_price' => $request->pricing['base_price'],
                        'currency' => $request->pricing['currency'],
                    ]
                );
            }

            // Handle Price Variations
            if ($request->has('price_variations')) {
                $itinerary->priceVariations()->delete();
                foreach ($request->price_variations as $variation) {
                    ItineraryPriceVariation::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'variation_name' => $variation['variation_name'],
                        'price' => $variation['price'],
                    ]);
                }
            }

            // Handle Blackout Dates
            if ($request->has('blackout_dates')) {
                $itinerary->blackoutDates()->delete();
                foreach ($request->blackout_dates as $date) {
                    ItineraryBlackoutDate::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'blackout_date' => $date['blackout_date'],
                    ]);
                }
            }

            // Handle Inclusions & Exclusions
            if ($request->has('inclusions_exclusions')) {
                $itinerary->inclusionsExclusions()->delete();
                foreach ($request->inclusions_exclusions as $ie) {
                    ItineraryInclusionExclusion::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'type' => $ie['type'],
                        'description' => $ie['description'],
                    ]);
                }
            }

            // Handle Media Gallery
            if ($request->has('media_gallery')) {
                $itinerary->mediaGallery()->delete();
                foreach ($request->media_gallery as $media) {
                    ItineraryMediaGallery::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'media_url' => $media['media_url'],
                    ]);
                }
            }

            // Handle SEO
            if ($request->has('seo')) {
                ItinerarySeo::updateOrCreate(
                    ['itinerary_id' => $itinerary->id],
                    [
                        'meta_title' => $request->seo['meta_title'],
                        'meta_description' => $request->seo['meta_description'],
                        'meta_keywords' => $request->seo['meta_keywords'],
                    ]
                );
            }

            // Handle Categories
            if ($request->has('categories')) {
                $itinerary->categories()->delete();
                foreach ($request->categories as $category_id) {
                    ItineraryCategory::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'category_id' => $category_id,
                    ]);
                }
            }

            // Handle Attributes
            if ($request->has('attributes')) {
                $itinerary->attributes()->delete();
                foreach ($request->attributes as $attribute) {
                    ItineraryAttribute::updateOrCreate(
                        [
                            'itinerary_id' => $itinerary->id,
                            'attribute_id' => $attribute['attribute_id'],
                        ],
                        [
                            'attribute_value' => $attribute['attribute_value'],
                        ]
                    );
                }
            }

            // Handle Tags
            if ($request->has('tags')) {
                $itinerary->tags()->delete();
                foreach ($request->tags as $tag_id) {
                    ItineraryTag::updateOrCreate([
                        'itinerary_id' => $itinerary->id,
                        'tag_id' => $tag_id,
                    ]);
                }
            }

            // Handle Availability
            if ($request->has('availability')) {
                ItineraryAvailability::updateOrCreate(
                    ['itinerary_id' => $itinerary->id],
                    [
                        'date_based' => $request->availability['date_based'],
                        'start_date' => $request->availability['start_date'] ?? null,
                        'end_date' => $request->availability['end_date'] ?? null,
                        'max_quantity' => $request->availability['max_quantity'] ?? null,
                    ]
                );
            }

            DB::commit();
            return response()->json(['message' => 'Itinerary updated successfully', 'itinerary' => $itinerary], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified Itinerary from storage.
     */
    public function destroy(string $id)
    {
        $itinerary = Itinerary::find($id);
        
        if (!$itinerary) {
            return response()->json(['message' => 'Itinerary not found'], 404);
        }

        // Optionally, you can delete related records before deleting the itinerary
        $itinerary->itineraryLocations()->delete();
        $itinerary->itinerarySchedules()->delete();
        $itinerary->itineraryActivities()->delete();
        $itinerary->itineraryTransfers()->delete();
        // Continue for other related models...
        
        $itinerary->delete();

        return response()->json(['message' => 'Itinerary deleted successfully']);
    }
}
