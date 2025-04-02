<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageLocation;
use App\Models\PackageSchedule;
use App\Models\PackageActivity;
use App\Models\PackageTransfer;
use App\Models\PackageBasePricing;
use App\Models\PackagePriceVariation;
use App\Models\PackageBlackoutDate;
use App\Models\PackageInclusionExclusion;
use App\Models\PackageMediaGallery;
use App\Models\PackageSeo;
use App\Models\PackageCategory;
use App\Models\PackageAttribute;
use App\Models\PackageTag;
use App\Models\PackageAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Validator;


class PackageController extends Controller
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

        $query = Package::query()
            ->select('packages.*')  
            ->join('package_base_pricing', 'package_base_pricing.package_id', '=', 'packages.id') 
            ->join('package_price_variations', 'package_price_variations.base_pricing_id', '=', 'package_base_pricing.id')
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
                $query->orderBy('package_price_variations.sale_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('package_price_variations.sale_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('packages.name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('packages.name', 'desc');
                break;
            case 'id_asc':
                $query->orderBy('packages.id', 'asc');
                break;
            case 'id_desc':
                $query->orderBy('packages.id', 'desc');
                break;
            case 'featured':
                $query->orderByRaw('packages.featured_itinerary DESC');
                break;
            default:
                $query->orderBy('packages.id', 'desc');
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
     * Store a newly created Package in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'featured_Package' => 'required|boolean',
            'private_Package' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $packageData = $request->only(['name', 'description', 'featured_Package', 'private_Package']);
        $packageData['slug'] = Str::slug($packageData['name']);
        $package = Package::create($packageData);

        // Create related records (simplified version, expand as needed)
        PackageLocation::create([
            'package_id' => $package->id,
            'city_id' => $request->city_id, // Assuming city_id is part of the request
        ]);

        // More related models like PackageSchedule, PackageActivity, etc. can be created similarly
        // Example for schedule creation
        for ($day = 1; $day <= 3; $day++) {
            $schedule = PackageSchedule::create([
                'package_id' => $package->id,
                'day' => $day,
            ]);
            
            // Add activities or transfers based on your need
        }

        return response()->json($package, 201);
    }


    /**
     * Create or Update Package.
    */
    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:packages,slug,' . $id,
            'description' => 'nullable|string',
            'featured_Package' => 'boolean',
            'private_Package' => 'boolean',
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
    
            // Create or Update Package
            $package = Package::updateOrCreate(
                ['id' => $id], 
                [
                    'name' => $request->name,
                    'slug' => $request->slug,
                    'description' => $request->description,
                    'featured_package' => $request->featured_package ?? false,
                    'private_package' => $request->private_package ?? false,
                ]
            );
    
            // Handle Locations
            if ($request->has('locations')) {
                $package->locations()->delete();
                foreach ($request->locations as $location) {
                    PackageLocation::create([
                        'package_id' => $package->id,
                        'city_id' => $location['city_id'],
                    ]);
                }
            }
    
            // Handle Schedules
            $scheduleMap = [];
            if ($request->has('schedules')) {
                $Package->schedules()->delete();
                foreach ($request->schedules as $schedule) {
                    $newSchedule = PackageSchedule::create([
                        'package_id' => $package->id,
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
                        PackageActivity::create([
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
                        PackageTransfer::create([
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

            // Handle Itinerary
            if ($request->has('itineraries')) {
                foreach ($request->itineraries as $itinerary) {
                    // Check if the schedule exists for the given day
                    $scheduleId = $scheduleMap[$itinerary['day']] ?? null;
                    if ($scheduleId) {
                        PackageItinerary::create([
                            'schedule_id' => $scheduleId,
                            'itinerary_id' => $itinerary['itinerary_id'],
                            'start_time' => $itinerary['start_time'],
                            'end_time' => $itinerary['end_time'],
                            'notes' => $itinerary['notes'],
                            'price' => $itinerary['price'],
                            'include_in_package' => $itinerary['include_in_package'],
                        ]);
                    }
                }
            }
    
            // Handle Pricing
            if ($request->has('pricing')) {
                // Create or Update the Base Pricing
                $basePricing = ItineraryasePricing::updateOrCreate(
                    ['package_id' => $package->id],
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
                        PackagePriceVariation::create([
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
                    PackageBlackoutDate::create([
                        'base_pricing_id' => $basePricing->id,
                        'date' => $date['date'],
                        'reason' => $date['reason'],
                    ]);
                }
            }
    
            // Handle Inclusions & Exclusions
            if ($request->has('inclusions_exclusions')) {
                $package->inclusionsExclusions()->delete();
                foreach ($request->inclusions_exclusions as $ie) {
                    PackageInclusionExclusion::create([
                        'package_id' => $package->id,
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
                $package->mediaGallery()->delete();
                foreach ($request->media_gallery as $media) {
                    PackageMediaGallery::create([
                        'package_id' => $package->id,
                        'url' => $media['url'],
                    ]);
                }
            }
    
            // Handle SEO
            if ($request->has('seo')) {
                PackageSeo::updateOrCreate(
                    ['package_id' => $package->id],
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
                $package->categories()->delete();
                foreach ($request->categories as $category_id) {
                    PackageCategory::updateOrCreate([
                        'package_id' => $package->id,
                        'category_id' => $category_id,
                    ]);
                }
            }

            // Handle Attributes
            if ($request->has('attributes')) {
                $package->attributes()->delete(); 
            
                $attributes = $request->input('attributes', []); 
            
                foreach ($attributes as $attribute) {
                    PackageAttribute::updateOrCreate(
                        [
                            'package_id' => $package->id,
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
                $package->tags()->delete();
                foreach ($request->tags as $tag_id) {
                    PackageTag::updateOrCreate([
                        'package_id' => $package->id,
                        'tag_id' => $tag_id,
                    ]);
                }
            }

            // Handle Availability
            if ($request->has('availability')) {
                PackageAvailability::updateOrCreate(
                    ['package_id' => $package->id],
                    [
                        'date_based_Package' => $request->availability['date_based_Package'],
                        'start_date' => $request->availability['start_date'] ?? null,
                        'end_date' => $request->availability['end_date'] ?? null,
                        'quantity_based_Package' => $request->availability['quantity_based_Package'],
                        'max_quantity' => $request->availability['max_quantity'] ?? null,
                    ]
                );
            }
    
            DB::commit();
            return response()->json(['message' => $id ? 'Package updated' : 'Package created', 'Package' => $package], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }


    /**
     * Display the specified Package.
     */
    public function show(string $id)
    {
        $package = Package::find($id);
        
        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        return response()->json($package);
    }

    /**
     * Update the specified Package in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:packages,slug,' . $id,
            'description' => 'nullable|string',
            'featured_Package' => 'boolean',
            'private_Package' => 'boolean',
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

            // Fetch Package
            $package = Package::findOrFail($id);

            // Update only provided fields
            $package->fill($request->only([
                'name', 'slug', 'description', 'featured_package', 'private_package'
            ]));

            $package->save();

            // Handle Locations
            if ($request->has('locations')) {
                $package->locations()->delete();
                foreach ($request->locations as $location) {
                    PackageLocation::updateOrCreate([
                        'package_id' => $package->id,
                        'city_id' => $location['city_id'],
                        'location_type' => $location['location_type'],
                        'location_label' => $location['location_label'],
                    ]);
                }
            }

            // Handle Schedules
            if ($request->has('schedules')) {
                $package->schedules()->delete();
                foreach ($request->schedules as $schedule) {
                    PackageSchedule::updateOrCreate([
                        'package_id' => $package->id,
                        'day' => $schedule['day'],
                        'schedule_details' => $schedule['schedule_details'] ?? '',
                    ]);
                }
            }

            // Handle Activities
            if ($request->has('activities')) {
                $package->activities()->delete();
                foreach ($request->activities as $activity) {
                    PackageActivity::updateOrCreate([
                        'package_id' => $package->id,
                        'activity_id' => $activity['activity_id'],
                    ]);
                }
            }

            // Handle Transfers
            if ($request->has('transfers')) {
                $package->transfers()->delete();
                foreach ($request->transfers as $transfer) {
                    PackageTransfer::updateOrCreate([
                        'package_id' => $package->id,
                        'transfer_type' => $transfer['transfer_type'],
                        'description' => $transfer['description'],
                    ]);
                }
            }

            // Handle Pricing
            if ($request->has('pricing')) {
                PackageBasePricing::updateOrCreate(
                    ['package_id' => $package->id],
                    [
                        'base_price' => $request->pricing['base_price'],
                        'currency' => $request->pricing['currency'],
                    ]
                );
            }

            // Handle Price Variations
            if ($request->has('price_variations')) {
                $package->priceVariations()->delete();
                foreach ($request->price_variations as $variation) {
                    PackagePriceVariation::updateOrCreate([
                        'package_id' => $package->id,
                        'variation_name' => $variation['variation_name'],
                        'price' => $variation['price'],
                    ]);
                }
            }

            // Handle Blackout Dates
            if ($request->has('blackout_dates')) {
                $package->blackoutDates()->delete();
                foreach ($request->blackout_dates as $date) {
                    PackageBlackoutDate::updateOrCreate([
                        'package_id' => $package->id,
                        'blackout_date' => $date['blackout_date'],
                    ]);
                }
            }

            // Handle Inclusions & Exclusions
            if ($request->has('inclusions_exclusions')) {
                $package->inclusionsExclusions()->delete();
                foreach ($request->inclusions_exclusions as $ie) {
                    PackageInclusionExclusion::updateOrCreate([
                        'package_id' => $package->id,
                        'type' => $ie['type'],
                        'description' => $ie['description'],
                    ]);
                }
            }

            // Handle Media Gallery
            if ($request->has('media_gallery')) {
                $package->mediaGallery()->delete();
                foreach ($request->media_gallery as $media) {
                    PackageMediaGallery::updateOrCreate([
                        'package_id' => $package->id,
                        'media_url' => $media['media_url'],
                    ]);
                }
            }

            // Handle SEO
            if ($request->has('seo')) {
                PackageSeo::updateOrCreate(
                    ['package_id' => $package->id],
                    [
                        'meta_title' => $request->seo['meta_title'],
                        'meta_description' => $request->seo['meta_description'],
                        'meta_keywords' => $request->seo['meta_keywords'],
                    ]
                );
            }

            // Handle Categories
            if ($request->has('categories')) {
                $package->categories()->delete();
                foreach ($request->categories as $category_id) {
                    PackageCategory::updateOrCreate([
                        'package_id' => $package->id,
                        'category_id' => $category_id,
                    ]);
                }
            }

            // Handle Attributes
            if ($request->has('attributes')) {
                $package->attributes()->delete();
                foreach ($request->attributes as $attribute) {
                    PackageAttribute::updateOrCreate(
                        [
                            'package_id' => $package->id,
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
                $package->tags()->delete();
                foreach ($request->tags as $tag_id) {
                    PackageTag::updateOrCreate([
                        'package_id' => $package->id,
                        'tag_id' => $tag_id,
                    ]);
                }
            }

            // Handle Availability
            if ($request->has('availability')) {
                PackageAvailability::updateOrCreate(
                    ['package_id' => $package->id],
                    [
                        'date_based' => $request->availability['date_based'],
                        'start_date' => $request->availability['start_date'] ?? null,
                        'end_date' => $request->availability['end_date'] ?? null,
                        'max_quantity' => $request->availability['max_quantity'] ?? null,
                    ]
                );
            }

            DB::commit();
            return response()->json(['message' => 'Package updated successfully', 'Package' => $package], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified Package from storage.
     */
    public function destroy(string $id)
    {
        $package = Package::find($id);
        
        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        // Optionally, you can delete related records before deleting the Package
        $package->PackageLocations()->delete();
        $package->PackageSchedules()->delete();
        $package->PackageActivities()->delete();
        $package->PackageTransfers()->delete();
        $package->PackageItineraries()->delete();
        // Continue for other related models...
        
        $package->delete();

        return response()->json(['message' => 'Package deleted successfully']);
    }
}
