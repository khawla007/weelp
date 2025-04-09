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

    /**
     * Display a listing of the activities.
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
        $ageGroupAttr = Attribute::where('slug', 'age-restriction')->first(); // Adjust slug if different
        
        $query = Activity::query()
            ->select('activities.*')  // Select all fields from activities
            ->join('activity_pricing', 'activity_pricing.activity_id', '=', 'activities.id') // Join with activity_pricing table
            ->with(['categories.category', 'locations.city', 'pricing', 'attributes']) // Eager load relationships
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
            );

            // Sorting based on the 'sort_by' parameter
            switch ($sortBy) {
                case 'price_asc':
                    $query->orderBy('activity_pricing.regular_price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('activity_pricing.regular_price', 'desc');
                    break;
                case 'name_asc':
                    $query->orderBy('activities.name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('activities.name', 'desc');
                    break;
                case 'id_asc':
                    $query->orderBy('activities.id', 'asc'); // Sort by ID ascending (oldest first)
                    break;
                case 'id_desc':
                    $query->orderBy('activities.id', 'desc'); // Sort by ID descending (newest first)
                    break;
                case 'featured':
                    $query->orderByRaw('activities.featured_activity DESC'); // Sort featured=true first
                    break;
                default:
                    $query->orderBy('activities.id', 'desc'); // Default to newest first (created_at_desc)
                    break;
            }
            $allItems = $query->get(); 
            // ->get();
        
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
     * Store a newly created activity in storage.
    */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:activities,slug',
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
    
        try {
            DB::beginTransaction();
    
            $activity = Activity::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'short_description' => $request->short_description,
                'featured_images' => $request->featured_images ? json_encode($request->featured_images) : null,
                'featured_activity' => $request->featured_activity ?? false,
            ]);
    
            // Categories
            if ($request->has('categories')) {
                foreach ($request->categories as $category_id) {
                    ActivityCategory::create([
                        'activity_id' => $activity->id,
                        'category_id' => $category_id,
                    ]);
                }
            }
    
            // Locations
            if ($request->has('locations')) {
                foreach ($request->locations as $location) {
                    ActivityLocation::create([
                        'activity_id' => $activity->id,
                        'city_id' => $location['city_id'],
                        'location_type' => $location['location_type'],
                        'location_label' => $location['location_label'],
                        'duration' => $location['duration'] ?? null,
                    ]);
                }
            }
    
            // Attributes
            if ($request->has('attributes')) {
                foreach ($request->input('attributes') as $attribute) {
                    ActivityAttribute::create([
                        'activity_id' => $activity->id,
                        'attribute_id' => $attribute['attribute_id'],
                        'attribute_value' => $attribute['attribute_value'],
                    ]);
                }
            }
    
            // Pricing
            if ($request->has('pricing')) {
                ActivityPricing::create([
                    'activity_id' => $activity->id,
                    'regular_price' => $request->pricing['regular_price'],
                    'currency' => $request->pricing['currency'],
                ]);
    
                // Seasonal Pricing
                if ($request->has('seasonal_pricing')) {
                    foreach ($request->seasonal_pricing as $season) {
                        ActivitySeasonalPricing::create([
                            'activity_id' => $activity->id,
                            'season_name' => $season['season_name'],
                            'enable_seasonal_pricing' => true,
                            'season_start' => $season['season_start'],
                            'season_end' => $season['season_end'],
                            'season_price' => $season['season_price'],
                        ]);
                    }
                }
            }
    
            // Group Discounts
            if ($request->has('group_discounts')) {
                foreach ($request->group_discounts as $discount) {
                    ActivityGroupDiscount::create([
                        'activity_id' => $activity->id,
                        'min_people' => $discount['min_people'],
                        'discount_amount' => $discount['discount_amount'],
                        'discount_type' => $discount['discount_type'],
                    ]);
                }
            }
    
            // Early Bird Discount
            if ($request->has('early_bird_discount')) {
                ActivityEarlyBirdDiscount::create([
                    'activity_id' => $activity->id,
                    'enable_early_bird_discount' => true,
                    'days_before_start' => $request->early_bird_discount['days_before_start'],
                    'discount_amount' => $request->early_bird_discount['discount_amount'],
                    'discount_type' => $request->early_bird_discount['discount_type'],
                ]);
            }
    
            // Last Minute Discount
            if ($request->has('last_minute_discount')) {
                ActivityLastMinuteDiscount::create([
                    'activity_id' => $activity->id,
                    'enable_last_minute_discount' => true,
                    'days_before_start' => $request->last_minute_discount['days_before_start'],
                    'discount_amount' => $request->last_minute_discount['discount_amount'],
                    'discount_type' => $request->last_minute_discount['discount_type'],
                ]);
            }
    
            // Promo Codes
            if ($request->has('promo_codes')) {
                foreach ($request->promo_codes as $promo) {
                    ActivityPromoCode::create([
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
    
            // Availability
            if ($request->has('availability')) {
                ActivityAvailability::create([
                    'activity_id' => $activity->id,
                    'date_based_activity' => $request->availability['date_based_activity'],
                    'start_date' => $request->availability['start_date'] ?? null,
                    'end_date' => $request->availability['end_date'] ?? null,
                    'quantity_based_activity' => $request->availability['quantity_based_activity'],
                    'max_quantity' => $request->availability['max_quantity'] ?? null,
                ]);
            }
    
            DB::commit();
    
            return response()->json(['message' => 'Activity created successfully', 'activity' => $activity], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }
    

    /**
     * Create newly or Update existing activity.
     */
    // public function save(Request $request, $id = null)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'slug' => 'required|string|unique:activities,slug,' . $id,
    //         'description' => 'nullable|string',
    //         'short_description' => 'nullable|string',
    //         'featured_images' => 'nullable|array',
    //         'featured_activity' => 'boolean',
    //         'categories' => 'nullable|array',
    //         'locations' => 'nullable|array',
    //         'attributes' => 'nullable|array',
    //         'pricing' => 'nullable|array',
    //         'seasonal_pricing' => 'nullable|array',
    //         'group_discounts' => 'nullable|array',
    //         'early_bird_discount' => 'nullable|array',
    //         'last_minute_discount' => 'nullable|array',
    //         'promo_codes' => 'nullable|array',
    //         'availability' => 'nullable|array',
    //     ]);
    //     // dd($request->all());
    //     try {
    //         DB::beginTransaction();

    //         // Create or Update Activity
    //         $activity = Activity::updateOrCreate(
    //             ['id' => $id], 
    //             [
    //                 'name' => $request->name,
    //                 'slug' => $request->slug,
    //                 'description' => $request->description,
    //                 'short_description' => $request->short_description,
    //                 'featured_images' => json_encode($request->featured_images),
    //                 'featured_activity' => $request->featured_activity ?? false,
    //             ]
    //         );

    //         // Handle Categories
    //         if ($request->has('categories')) {
    //             $activity->categories()->delete();
    //             foreach ($request->categories as $category_id) {
    //                 ActivityCategory::updateOrCreate([
    //                     'activity_id' => $activity->id,
    //                     'category_id' => $category_id,
    //                 ]);
    //             }
    //         }

    //         // Handle Locations
    //         if ($request->has('locations')) {
    //             $activity->locations()->delete();
    //             foreach ($request->locations as $location) {
    //                 ActivityLocation::updateOrCreate([
    //                     'activity_id' => $activity->id,
    //                     'city_id' => $location['city_id'],
    //                     'location_type' => $location['location_type'],
    //                     'location_label' => $location['location_label'],
    //                     'duration' => $location['duration'] ?? null,
    //                 ]);
    //             }
    //         }
    //         // logger()->info('ATTRIBUTES RECEIVED:', $request->attributes);
    //         // Handle Attributes
    //         if ($request->has('attributes')) {
    //             $activity->attributes()->delete(); 
            
    //             $attributes = $request->input('attributes', []); 
            
    //             foreach ($attributes as $attribute) {
    //                 logger()->info('Processing attribute', $attribute);
    //                 ActivityAttribute::updateOrCreate(
    //                     [
    //                         'activity_id' => $activity->id,
    //                         'attribute_id' => $attribute['attribute_id'],
    //                     ],
    //                     [
    //                         'attribute_value' => $attribute['attribute_value'],
    //                     ]
    //                 );
    //             }
    //         }

    //         // Handle Pricing
    //         if ($request->has('pricing')) {
    //             $pricing = ActivityPricing::updateOrCreate(
    //                 ['activity_id' => $activity->id],
    //                 [
    //                     'regular_price' => $request->pricing['regular_price'],
    //                     'currency' => $request->pricing['currency'],
    //                 ]
    //             );

    //             // Handle Seasonal Pricing
    //             if ($request->has('seasonal_pricing')) {
    //                 foreach ($request->seasonal_pricing as $season) {
    //                     ActivitySeasonalPricing::updateOrCreate(
    //                         [
    //                             'activity_id' => $activity->id,
    //                             'season_name' => $season['season_name'], // Used to avoid duplicates
    //                         ],
    //                         [
    //                             'enable_seasonal_pricing' => true,
    //                             'season_start' => $season['season_start'],
    //                             'season_end' => $season['season_end'],
    //                             'season_price' => $season['season_price'],
    //                         ]
    //                     );
    //                 }
    //             }
    //         }

    //         // Handle Group Discounts
    //         if ($request->has('group_discounts')) {
    //             $activity->groupDiscounts()->delete();
    //             foreach ($request->group_discounts as $discount) {
    //                 ActivityGroupDiscount::updateOrCreate([
    //                     'activity_id' => $activity->id,
    //                     'min_people' => $discount['min_people'],
    //                     'discount_amount' => $discount['discount_amount'],
    //                     'discount_type' => $discount['discount_type'],
    //                 ]);
    //             }
    //         }

    //         // Handle Early Bird Discount
    //         if ($request->has('early_bird_discount')) {
    //             ActivityEarlyBirdDiscount::updateOrCreate(
    //                 ['activity_id' => $activity->id],
    //                 [
    //                     'enable_early_bird_discount' => true,
    //                     'days_before_start' => $request->early_bird_discount['days_before_start'],
    //                     'discount_amount' => $request->early_bird_discount['discount_amount'],
    //                     'discount_type' => $request->early_bird_discount['discount_type'],
    //                 ]
    //             );
    //         }

    //         // Handle Last Minute Discount
    //         if ($request->has('last_minute_discount')) {
    //             ActivityLastMinuteDiscount::updateOrCreate(
    //                 ['activity_id' => $activity->id],
    //                 [
    //                     'enable_last_minute_discount' => true,
    //                     'days_before_start' => $request->last_minute_discount['days_before_start'],
    //                     'discount_amount' => $request->last_minute_discount['discount_amount'],
    //                     'discount_type' => $request->last_minute_discount['discount_type'],
    //                 ]
    //             );
    //         }

    //         // Handle Promo Codes
    //         if ($request->has('promo_codes')) {
    //             $activity->promoCodes()->delete();
    //             foreach ($request->promo_codes as $promo) {
    //                 ActivityPromoCode::updateOrCreate([
    //                     'activity_id' => $activity->id,
    //                     'promo_code' => $promo['promo_code'],
    //                     'max_uses' => $promo['max_uses'],
    //                     'discount_amount' => $promo['discount_amount'],
    //                     'discount_type' => $promo['discount_type'],
    //                     'valid_from' => $promo['valid_from'],
    //                     'valid_to' => $promo['valid_to'],
    //                 ]);
    //             }
    //         }

    //         // Handle Availability
    //         if ($request->has('availability')) {
    //             ActivityAvailability::updateOrCreate(
    //                 ['activity_id' => $activity->id],
    //                 [
    //                     'date_based_activity' => $request->availability['date_based_activity'],
    //                     'start_date' => $request->availability['start_date'] ?? null,
    //                     'end_date' => $request->availability['end_date'] ?? null,
    //                     'quantity_based_activity' => $request->availability['quantity_based_activity'],
    //                     'max_quantity' => $request->availability['max_quantity'] ?? null,
    //                 ]
    //             );
    //         }

    //         DB::commit();
    //         return response()->json(['message' => $id ? 'Activity updated successfully' : 'Activity created successfully', 'activity' => $activity], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
    //     }
    // }
    
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
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);
    
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|unique:activities,slug,' . $activity->id,
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
        ];
    
        $request->validate($rules);
    
        try {
            DB::beginTransaction();
    
            $activity->fill($request->only([
                'name', 'slug', 'description', 'short_description', 'featured_activity'
            ]));
    
            if ($request->has('featured_images')) {
                $activity->featured_images = json_encode($request->featured_images);
            }
    
            $activity->save();
    
            $updateOrCreateRelation = function ($relationName, $data) use ($activity) {
                $relation = $activity->$relationName();
                $existing = $relation->pluck('id')->toArray();
    
                foreach ($data as $item) {
                    if (!empty($item['id']) && in_array($item['id'], $existing)) {
                        $relation->where('id', $item['id'])->update($item);
                    } else {
                        $relation->create($item);
                    }
                }
            };
    
            foreach (['locations', 'categories'] as $relation) {
                if ($request->has($relation)) {
                    $updateOrCreateRelation($relation, $request->$relation);
                }
            }
    
            if ($request->has('attributes')) {
                $updateOrCreateRelation('attributes', $request->input('attributes'));
            }
    
            $pricing = $activity->pricing()->first();
    
            if ($request->has('pricing')) {
                $pricing = $activity->pricing()->firstOrCreate([]);
                $pricing->fill($request->pricing)->save();
            }
    
            $updateOrCreateChild = function ($data, $modelClass, $foreignKey) use ($pricing) {
                foreach ($data as $item) {
                    if (!empty($item['id'])) {
                        $model = $modelClass::find($item['id']);
                        if ($model) {
                            $model->fill($item)->save();
                        }
                    } else {
                        $item[$foreignKey] = $pricing->id;
                        $modelClass::create($item);
                    }
                }
            };
    
            if ($request->has('seasonal_pricing')) {
                $updateOrCreateChild($request->seasonal_pricing, \App\Models\ActivitySeasonalPricing::class, 'base_pricing_id');
            }
    
            if ($request->has('group_discounts')) {
                $updateOrCreateChild($request->group_discounts, \App\Models\ActivityGroupDiscount::class, 'base_pricing_id');
            }
    
            if ($request->has('early_bird_discount')) {
                $updateOrCreateChild([$request->early_bird_discount], \App\Models\ActivityEarlyBirdDiscount::class, 'base_pricing_id');
            }
    
            if ($request->has('last_minute_discount')) {
                $updateOrCreateChild([$request->last_minute_discount], \App\Models\ActivityLastMinuteDiscount::class, 'base_pricing_id');
            }
    
            if ($request->has('promo_codes')) {
                $updateOrCreateChild($request->promo_codes, \App\Models\ActivityPromoCode::class, 'base_pricing_id');
            }
    
            if ($request->has('availability')) {
                $activity->availability()->updateOrCreate([], $request->availability);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Activity updated successfully',
                'activity' => $activity->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage(),
            ], 500);
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
