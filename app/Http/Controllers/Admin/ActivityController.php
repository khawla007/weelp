<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityLocation;
use App\Models\ActivityAttribute;
use App\Models\ActivityPricing;
use App\Models\ActivitySeasonalPricing;
use App\Models\ActivityGroupDiscount;
use App\Models\ActivityEarlyBirdDiscount;
use App\Models\ActivityLastMinuteDiscount;
use App\Models\ActivityPromoCode;
use App\Models\ActivityAvailability;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\City;
use Illuminate\Support\Facades\DB;


class ActivityController extends Controller
{

    // $activities = Activity::with([
    //     'categories', 'locations', 'attributes', 'pricing', 'seasonalPricing',
    //     'groupDiscounts', 'earlyBirdDiscount', 'lastMinuteDiscount', 'promoCodes', 'availability'
    // ])->get();

    // return response()->json($activities, 200);
    /**
     * Display a listing of the activities.
     */
    public function index(Request $request)
    {
        $perPage = 3;
        $page = $request->get('page', 1);
    
        // Extract Filters from Query String
        $categorySlug = $request->get('category');
        $difficulty = $request->get('difficulty_level');
        $duration = $request->get('duration');
        $ageGroup = $request->get('age_restriction');
        $season = $request->get('season');
        $minPrice = $request->get('min_price', 0);
        $maxPrice = $request->get('max_price');
    
        // Fetch Category ID from Slug
        $category = $categorySlug ? Category::where('slug', $categorySlug)->first() : null;
        $categoryId = $category ? $category->id : null;
    
        // 🔹 Fetch Attribute IDs Dynamically
        $difficultyAttr = Attribute::where('slug', 'difficulty-level')->first();
        $durationAttr = Attribute::where('slug', 'duration')->first();
        $ageGroupAttr = Attribute::where('slug', 'age-restriction')->first(); // Adjust slug if different
    
        // Base Query with Filters
        $activities = Activity::with(['categories.category', 'locations.city', 'pricing', 'attributes'])
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
                $query->whereHas('pricing', fn($q) => 
                    $q->whereBetween('regular_price', [$minPrice, $maxPrice])
                )
            )
            ->paginate($perPage);
    
        return response()->json([
            'success' => true,
            'data' => $activities,
            'current_page' => (int) $page,
            'per_page' => $perPage,
            'total' => $activities->total(),
        ], 200);
    }    

    /**
     * Store a newly created activity in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:activities,slug',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'featured_images' => 'nullable|json',
            'featured_activity' => 'boolean',
            'categories' => 'nullable|array',
            'locations' => 'nullable|array',
            'attributes' => 'nullable|array',
            'pricing' => 'nullable|array',
            'seasonal_pricing' => 'nullable|array',
            'group_discounts' => 'nullable|array',
            'early_bird_discount' => 'nullable|array',
            'last_minute_discount' => 'nullable|array',
            'promo_codes' => 'nullable|array',
            'availability' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Create the activity
            $activity = Activity::create($validated);

            // Attach categories
            if (!empty($request->categories)) {
                foreach ($request->categories as $category_id) {
                    ActivityCategory::create(['activity_id' => $activity->id, 'category_id' => $category_id]);
                }
            }

            // Add locations
            if (!empty($request->locations)) {
                foreach ($request->locations as $location) {
                    ActivityLocation::create(array_merge($location, ['activity_id' => $activity->id]));
                }
            }

            // Assign attributes
            if (!empty($request->attributes)) {
                foreach ($request->attributes as $attribute) {
                    ActivityAttribute::create(array_merge($attribute, ['activity_id' => $activity->id]));
                }
            }

            // Set pricing
            if (!empty($request->pricing)) {
                ActivityPricing::create(array_merge($request->pricing, ['activity_id' => $activity->id]));
            }

            // Add seasonal pricing
            if (!empty($request->seasonal_pricing)) {
                foreach ($request->seasonal_pricing as $season) {
                    ActivitySeasonalPricing::create(array_merge($season, ['activity_id' => $activity->id]));
                }
            }

            // Add group discounts
            if (!empty($request->group_discounts)) {
                foreach ($request->group_discounts as $discount) {
                    ActivityGroupDiscount::create(array_merge($discount, ['activity_id' => $activity->id]));
                }
            }

            // Add early bird discount
            if (!empty($request->early_bird_discount)) {
                ActivityEarlyBirdDiscount::create(array_merge($request->early_bird_discount, ['activity_id' => $activity->id]));
            }

            // Add last minute discount
            if (!empty($request->last_minute_discount)) {
                ActivityLastMinuteDiscount::create(array_merge($request->last_minute_discount, ['activity_id' => $activity->id]));
            }

            // Add promo codes
            if (!empty($request->promo_codes)) {
                foreach ($request->promo_codes as $promo) {
                    ActivityPromoCode::create(array_merge($promo, ['activity_id' => $activity->id]));
                }
            }

            // Add availability
            if (!empty($request->availability)) {
                ActivityAvailability::create(array_merge($request->availability, ['activity_id' => $activity->id]));
            }

            DB::commit();
            return response()->json($activity->load(['categories', 'locations', 'attributes', 'pricing', 'seasonalPricing', 'groupDiscounts', 'earlyBirdDiscount', 'lastMinuteDiscount', 'promoCodes', 'availability']), 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Something went wrong!', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create newly or Update existing activity.
     */
    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:activities,slug,' . $id,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'featured_images' => 'nullable|array',
            'featured_activity' => 'boolean',
            'categories' => 'nullable|array',
            'locations' => 'nullable|array',
            'attributes' => 'nullable|array',
            'pricing' => 'nullable|array',
            'seasonal_pricing' => 'nullable|array',
            'group_discounts' => 'nullable|array',
            'early_bird_discount' => 'nullable|array',
            'last_minute_discount' => 'nullable|array',
            'promo_codes' => 'nullable|array',
            'availability' => 'nullable|array',
        ]);
        // dd($request->all());
        try {
            DB::beginTransaction();

            // Create or Update Activity
            $activity = Activity::updateOrCreate(
                ['id' => $id], 
                [
                    'name' => $request->name,
                    'slug' => $request->slug,
                    'description' => $request->description,
                    'short_description' => $request->short_description,
                    'featured_images' => json_encode($request->featured_images),
                    'featured_activity' => $request->featured_activity ?? false,
                ]
            );

            // Handle Categories
            if ($request->has('categories')) {
                $activity->categories()->delete();
                foreach ($request->categories as $category_id) {
                    ActivityCategory::updateOrCreate([
                        'activity_id' => $activity->id,
                        'category_id' => $category_id,
                    ]);
                }
            }

            // Handle Locations
            if ($request->has('locations')) {
                $activity->locations()->delete();
                foreach ($request->locations as $location) {
                    ActivityLocation::updateOrCreate([
                        'activity_id' => $activity->id,
                        'city_id' => $location['city_id'],
                        'location_type' => $location['location_type'],
                        'location_label' => $location['location_label'],
                        'duration' => $location['duration'] ?? null,
                    ]);
                }
            }

            // Handle Attributes
            if ($request->has('attributes')) {
                $activity->attributes()->delete(); 
            
                $attributes = $request->input('attributes', []); 
            
                foreach ($attributes as $attribute) {
                    ActivityAttribute::updateOrCreate(
                        [
                            'activity_id' => $activity->id,
                            'attribute_id' => $attribute['attribute_id'],
                        ],
                        [
                            'attribute_value' => $attribute['attribute_value'],
                        ]
                    );
                }
            }

            // Handle Pricing
            if ($request->has('pricing')) {
                $pricing = ActivityPricing::updateOrCreate(
                    ['activity_id' => $activity->id],
                    [
                        'regular_price' => $request->pricing['regular_price'],
                        'currency' => $request->pricing['currency'],
                    ]
                );

                // Handle Seasonal Pricing
                if ($request->has('seasonal_pricing')) {
                    ActivitySeasonalPricing::updateOrCreate(
                        ['activity_id' => $activity->id],
                        [
                            'enable_seasonal_pricing' => true,
                            'season_name' => $request->seasonal_pricing['season_name'],
                            'season_start' => $request->seasonal_pricing['season_start'],
                            'season_end' => $request->seasonal_pricing['season_end'],
                            'season_price' => $request->seasonal_pricing['season_price'],
                        ]
                    );
                }
            }

            // Handle Group Discounts
            if ($request->has('group_discounts')) {
                $activity->groupDiscounts()->delete();
                foreach ($request->group_discounts as $discount) {
                    ActivityGroupDiscount::updateOrCreate([
                        'activity_id' => $activity->id,
                        'min_people' => $discount['min_people'],
                        'discount_amount' => $discount['discount_amount'],
                        'discount_type' => $discount['discount_type'],
                    ]);
                }
            }

            // Handle Early Bird Discount
            if ($request->has('early_bird_discount')) {
                ActivityEarlyBirdDiscount::updateOrCreate(
                    ['activity_id' => $activity->id],
                    [
                        'enable_early_bird_discount' => true,
                        'days_before_start' => $request->early_bird_discount['days_before_start'],
                        'discount_amount' => $request->early_bird_discount['discount_amount'],
                        'discount_type' => $request->early_bird_discount['discount_type'],
                    ]
                );
            }

            // Handle Last Minute Discount
            if ($request->has('last_minute_discount')) {
                ActivityLastMinuteDiscount::updateOrCreate(
                    ['activity_id' => $activity->id],
                    [
                        'enable_last_minute_discount' => true,
                        'days_before_start' => $request->last_minute_discount['days_before_start'],
                        'discount_amount' => $request->last_minute_discount['discount_amount'],
                        'discount_type' => $request->last_minute_discount['discount_type'],
                    ]
                );
            }

            // Handle Promo Codes
            if ($request->has('promo_codes')) {
                $activity->promoCodes()->delete();
                foreach ($request->promo_codes as $promo) {
                    ActivityPromoCode::updateOrCreate([
                        'activity_id' => $activity->id,
                        'promo_code' => $promo['promo_code'],
                        'max_uses' => $promo['max_uses'],
                        'discount_amount' => $promo['discount_amount'],
                        'discount_type' => $promo['discount_type'],
                        'valid_from' => $promo['valid_from'],
                        'valid_to' => $promo['valid_to'],
                    ]);
                }
            }

            // Handle Availability
            if ($request->has('availability')) {
                ActivityAvailability::updateOrCreate(
                    ['activity_id' => $activity->id],
                    [
                        'date_based_activity' => $request->availability['date_based_activity'],
                        'start_date' => $request->availability['start_date'] ?? null,
                        'end_date' => $request->availability['end_date'] ?? null,
                        'quantity_based_activity' => $request->availability['quantity_based_activity'],
                        'max_quantity' => $request->availability['max_quantity'] ?? null,
                    ]
                );
            }

            DB::commit();
            return response()->json(['message' => $id ? 'Activity updated successfully' : 'Activity created successfully', 'activity' => $activity], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Display the specified activity.
     */
    public function show(string $id)
    {
        $activity = Activity::with([
            'categories', 
            'locations.city', 
            'attributes.attribute',
            'pricing', 'seasonalPricing', 
            'groupDiscounts', 'earlyBirdDiscount', 
            'lastMinuteDiscount', 'promoCodes', 'availability'
        ])->find($id);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        // Transform response
        $activityData = $activity->toArray();
    
        // Replace location city object with just `city_name`
        $activityData['locations'] = collect($activity->locations)->map(function ($location) {
            return [
                'id' => $location->id,
                'activity_id' => $location->activity_id,
                'location_type' => $location->location_type,
                'city_id' => $location->city_id,
                'city_name' => $location->city->name ?? null, // Get city name
                'location_label' => $location->location_label,
                'duration' => $location->duration,
                'created_at' => $location->created_at,
                'updated_at' => $location->updated_at,
            ];
        });
    
        // Replace attributes with just `attribute_name`
        $activityData['attributes'] = collect($activity->attributes)->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'attribute_id' => $attribute->attribute_id,
                'attribute_name' => $attribute->attribute->name ?? null, // Get attribute name
                'attribute_value' => $attribute->attribute_value,
            ];
        });
    
        // Replace categories with just `category_name`
        $activityData['categories'] = collect($activity->categories)->map(function ($category) {
            return [
                'id' => $category->id,
                'category_id' => $category->category_id,
                'category_name' => $category->category->name ?? null, // Get category name
            ];
        });
    
        return response()->json($activityData, 200);
    }

    /**
     * Update the specified activity in storage.
     */
    public function update(Request $request, string $id)
    {
        $activity = Activity::find($id);
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:activities,slug,' . $id,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'featured_images' => 'nullable|json',
            'featured_activity' => 'boolean',
            'categories' => 'nullable|array',
            'locations' => 'nullable|array',
            'attributes' => 'nullable|array',
            'pricing' => 'nullable|array',
            'seasonal_pricing' => 'nullable|array',
            'group_discounts' => 'nullable|array',
            'early_bird_discount' => 'nullable|array',
            'last_minute_discount' => 'nullable|array',
            'promo_codes' => 'nullable|array',
            'availability' => 'nullable|array',
        ]);
    
        DB::beginTransaction();
        try {
            // Update activity main details
            $activity->update($validated);
    
            // Update categories (delete & insert new)
            ActivityCategory::where('activity_id', $id)->delete();
            if (!empty($request->categories)) {
                foreach ($request->categories as $category_id) {
                    ActivityCategory::create(['activity_id' => $id, 'category_id' => $category_id]);
                }
            }
    
            // Update locations (delete & insert new)
            ActivityLocation::where('activity_id', $id)->delete();
            if (!empty($request->locations)) {
                foreach ($request->locations as $location) {
                    ActivityLocation::create(array_merge($location, ['activity_id' => $id]));
                }
            }
    
            // Update attributes (delete & insert new)
            ActivityAttribute::where('activity_id', $id)->delete();
            if (!empty($request->attributes)) {
                foreach ($request->attributes as $attribute) {
                    ActivityAttribute::create(array_merge($attribute, ['activity_id' => $id]));
                }
            }
    
            // Update pricing (replace old record)
            ActivityPricing::where('activity_id', $id)->delete();
            if (!empty($request->pricing)) {
                ActivityPricing::create(array_merge($request->pricing, ['activity_id' => $id]));
            }
    
            // Update seasonal pricing (delete & insert new)
            ActivitySeasonalPricing::where('activity_id', $id)->delete();
            if (!empty($request->seasonal_pricing)) {
                foreach ($request->seasonal_pricing as $season) {
                    ActivitySeasonalPricing::create(array_merge($season, ['activity_id' => $id]));
                }
            }
    
            // Update group discounts (delete & insert new)
            ActivityGroupDiscount::where('activity_id', $id)->delete();
            if (!empty($request->group_discounts)) {
                foreach ($request->group_discounts as $discount) {
                    ActivityGroupDiscount::create(array_merge($discount, ['activity_id' => $id]));
                }
            }
    
            // Update early bird discount (replace old record)
            ActivityEarlyBirdDiscount::where('activity_id', $id)->delete();
            if (!empty($request->early_bird_discount)) {
                ActivityEarlyBirdDiscount::create(array_merge($request->early_bird_discount, ['activity_id' => $id]));
            }
    
            // Update last minute discount (replace old record)
            ActivityLastMinuteDiscount::where('activity_id', $id)->delete();
            if (!empty($request->last_minute_discount)) {
                ActivityLastMinuteDiscount::create(array_merge($request->last_minute_discount, ['activity_id' => $id]));
            }
    
            // Update promo codes (delete & insert new)
            ActivityPromoCode::where('activity_id', $id)->delete();
            if (!empty($request->promo_codes)) {
                foreach ($request->promo_codes as $promo) {
                    ActivityPromoCode::create(array_merge($promo, ['activity_id' => $id]));
                }
            }
    
            // Update availability (replace old record)
            ActivityAvailability::where('activity_id', $id)->delete();
            if (!empty($request->availability)) {
                ActivityAvailability::create(array_merge($request->availability, ['activity_id' => $id]));
            }
    
            DB::commit();
            return response()->json($activity->load([
                'categories', 'locations', 'attributes', 'pricing', 'seasonalPricing',
                'groupDiscounts', 'earlyBirdDiscount', 'lastMinuteDiscount', 'promoCodes', 'availability'
            ]), 200);
    
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Update failed', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified activity from storage.
     */
    public function destroy(string $id)
    {
        $activity = Activity::find($id);
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Delete related records
            ActivityCategory::where('activity_id', $id)->delete();
            ActivityLocation::where('activity_id', $id)->delete();
            ActivityAttribute::where('activity_id', $id)->delete();
            ActivityPricing::where('activity_id', $id)->delete();
            ActivitySeasonalPricing::where('activity_id', $id)->delete();
            ActivityGroupDiscount::where('activity_id', $id)->delete();
            ActivityEarlyBirdDiscount::where('activity_id', $id)->delete();
            ActivityLastMinuteDiscount::where('activity_id', $id)->delete();
            ActivityPromoCode::where('activity_id', $id)->delete();
            ActivityAvailability::where('activity_id', $id)->delete();

            // Delete activity
            $activity->delete();

            DB::commit();
            return response()->json(['message' => 'Activity deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to delete activity', 'message' => $e->getMessage()], 500);
        }
    }
}
