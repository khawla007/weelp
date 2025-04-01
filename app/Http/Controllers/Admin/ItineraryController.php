<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryActivity;
use App\Models\ItineraryTransfer;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryPriceVariation;
use App\Models\ItineraryBlackoutDate;
use App\Models\ItineraryInclusionExclusion;
use App\Models\ItineraryMediaGallery;
use App\Models\ItinerarySeo;
use App\Models\ItineraryCategory;
use App\Models\ItineraryAttribute;
use App\Models\ItineraryTag;
use App\Models\ItineraryAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Validator;


class ItineraryController extends Controller
{
    /**
     * Display a listing of the Itinerary.
     */
    public function index()
    {
        $itineraries = Itinerary::all();
        return response()->json($itineraries);
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
    
            // Handle Schedules
            $scheduleMap = [];
            if ($request->has('schedules')) {
                $itinerary->schedules()->delete();
                foreach ($request->schedules as $schedule) {
                    $newSchedule = ItinerarySchedule::create([
                        'itinerary_id' => $itinerary->id,
                        'day' => $schedule['day'],
                    ]);
                    $scheduleMap[$schedule['day']] = $newSchedule->id;
                }
            }

            // Handle Activities
            if ($request->has('activities')) {
                foreach ($request->activities as $activity) {
                    // Check if the schedule exists for the given day
                    $scheduleId = $scheduleMap[$activity['day']] ?? null;
                    if ($scheduleId) {
                        ItineraryActivity::create([
                            'schedule_id' => $scheduleId,
                            'activity_id' => $activity['activity_id'],
                            'start_time' => $activity['start_time'],
                            'end_time' => $activity['end_time'],
                            'notes' => $activity['notes'],
                            'price' => $activity['price'],
                            'include_in_package' => $activity['include_in_package'],
                        ]);
                    }
                }
            }
    
            // Handle Transfers
            if ($request->has('transfers')) {
                foreach ($request->transfers as $transfer) {
                    // Check if the schedule exists for the given day
                    $scheduleId = $scheduleMap[$transfer['day']] ?? null;
                    if ($scheduleId) {
                        ItineraryTransfer::create([
                            'schedule_id' => $scheduleId,
                            'transfer_id' => $transfer['transfer_id'],
                            'start_time' => $transfer['start_time'],
                            'end_time' => $transfer['end_time'],
                            'notes' => $transfer['notes'],
                            'price' => $transfer['price'],
                            'include_in_package' => $transfer['include_in_package'],
                            'pickup_location' => $transfer['pickup_location'],
                            'dropoff_location' => $transfer['dropoff_location'],
                            'pax' => $transfer['pax'],
                        ]);
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
        $itinerary = Itinerary::find($id);
        
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
