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
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:Itinerarys,slug',
            'description' => 'nullable|string',
            'featured_itinerary' => 'boolean',
            'private_itinerary' => 'boolean',
            'locations' => 'nullable|array',
            'information' => 'nullable|array',
            'schedules' => 'nullable|array',
            'activities' => 'nullable|array',
            'transfers' => 'nullable|array',
            'pricing' => 'nullable|array',
            'price_variations' => 'nullable|array',
            'blackout_dates' => 'nullable|array',
            'inclusions_exclusions' => 'nullable|array',
            'media_gallery' => 'nullable|array',
            'faqs' => 'nullable|array',
            'seo' => 'nullable|array',
            'categories' => 'nullable|array',
            'attributes' => 'nullable|array',
            'tags' => 'nullable|array',
            'availability' => 'nullable|array',
        ];
    
        $request->validate($rules);
    
        try {
            DB::beginTransaction();
    
            $itinerary = Itinerary::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description ?? null,
                'featured_itinerary' => $request->boolean('featured_itinerary'),
                'private_itinerary' => $request->boolean('private_itinerary'),
            ]);
    
            // === Information ===
            if ($request->has('information')) {
                foreach ($request->information as $info) {
                    ItineraryInformation::create([
                        'itinerary_id' => $itinerary->id,
                        'section_title' => $info['section_title'] ?? '',
                        'content' => $info['content'] ?? '',
                    ]);
                }
            }
    
            // === Locations ===
            if ($request->has('locations')) {
                foreach ($request->locations as $location) {
                    ItineraryLocation::create([
                        'itinerary_id' => $itinerary->id,
                        'city_id' => $location['city_id'],
                    ]);
                }
            }
    
            // === Schedules ===
            $scheduleMap = [];
            if ($request->has('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $record = ItinerarySchedule::create([
                        'itinerary_id' => $itinerary->id,
                        'day' => $schedule['day'],
                    ]);
                    $scheduleMap[$schedule['day']] = $record->id;
                }
            }
    
            // === Transfers ===
            if ($request->has('transfers')) {
                foreach ($request->transfers as $transfer) {
                    $scheduleId = $scheduleMap[$transfer['day']] ?? null;
                    if ($scheduleId) {
                        ItineraryTransfer::create([
                            'schedule_id' => $scheduleId,
                            'transfer_id' => $transfer['transfer_id'],
                            'start_time' => $transfer['start_time'],
                            'end_time' => $transfer['end_time'],
                            'notes' => $transfer['notes'],
                            'price' => $transfer['price'],
                            'include_in_itinerary' => $transfer['include_in_itinerary'],
                            'pickup_location' => $transfer['pickup_location'] ?? null,
                            'dropoff_location' => $transfer['dropoff_location'] ?? null,
                            'pax' => $transfer['pax'] ?? null,
                        ]);
                    }
                }
            }
    
            // === Activities ===
            if ($request->has('activities')) {
                foreach ($request->activities as $activity) {
                    $scheduleId = $scheduleMap[$activity['day']] ?? null;
                    if ($scheduleId) {
                        ItineraryActivity::create([
                            'schedule_id' => $scheduleId,
                            'activity_id' => $activity['activity_id'],
                            'start_time' => $activity['start_time'],
                            'end_time' => $activity['end_time'],
                            'notes' => $activity['notes'],
                            'price' => $activity['price'],
                            'include_in_itinerary' => $activity['include_in_itinerary'],
                        ]);
                    }
                }
            }
    
            // === Pricing ===
            if ($request->has('pricing')) {
                $basePricing = ItineraryBasePricing::create([
                    'itinerary_id' => $itinerary->id,
                    'currency' => $request->pricing['currency'],
                    'availability' => $request->pricing['availability'],
                    'start_date' => $request->pricing['start_date'],
                    'end_date' => $request->pricing['end_date'],
                ]);
    
                if ($request->has('price_variations')) {
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
    
                if ($request->has('blackout_dates')) {
                    foreach ($request->blackout_dates as $date) {
                        ItineraryBlackoutDate::create([
                            'base_pricing_id' => $basePricing->id,
                            'date' => $date['date'],
                            'reason' => $date['reason'],
                        ]);
                    }
                }
            }
    
            // === Inclusions/Exclusions ===
            if ($request->has('inclusions_exclusions')) {
                foreach ($request->inclusions_exclusions as $ie) {
                    ItineraryInclusionExclusion::create([
                        'itinerary_id' => $itinerary->id,
                        'type' => $ie['type'],
                        'title' => $ie['title'],
                        'description' => $ie['description'],
                        'include_exclude' => $ie['include_exclude'] === 'include' ? 1 : 0,
                    ]);
                }
            }
    
            // === Media Gallery ===
            if ($request->has('media_gallery')) {
                foreach ($request->media_gallery as $media) {
                    ItineraryMediaGallery::create([
                        'itinerary_id' => $itinerary->id,
                        'url' => $media['url'],
                    ]);
                }
            }
    
            // === FAQs ===
            if ($request->has('faqs')) {
                foreach ($request->faqs as $faq) {
                    ItineraryFaq::create([
                        'itinerary_id' => $itinerary->id,
                        'question_number' => $faq['question_number'] ?? null,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                }
            }
    
            // === SEO ===
            if ($request->has('seo')) {
                ItinerarySeo::create([
                    'itinerary_id' => $itinerary->id,
                    'meta_title' => $request->seo['meta_title'],
                    'meta_description' => $request->seo['meta_description'],
                    'keywords' => $request->seo['keywords'],
                    'og_image_url' => $request->seo['og_image_url'],
                    'canonical_url' => $request->seo['canonical_url'],
                    'schema_type' => $request->seo['schema_type'],
                    'schema_data' => is_array($request->seo['schema_data']) 
                        ? json_encode($request->seo['schema_data']) 
                        : $request->seo['schema_data'],
                ]);
            }
    
            // === Categories ===
            if ($request->has('categories')) {
                foreach ($request->categories as $category_id) {
                    ItineraryCategory::create([
                        'itinerary_id' => $itinerary->id,
                        'category_id' => $category_id,
                    ]);
                }
            }

            if ($request->has('attributes')) {
            
                foreach ($request->input('attributes') as $attribute) {
                    ItineraryAttribute::create([
                        'itinerary_id' => $itinerary->id,
                        'attribute_id' => $attribute['attribute_id'],
                        'attribute_value' => $attribute['attribute_value'],
                    ]);
                }
            }
    
            // === Tags ===
            if ($request->has('tags')) {
                foreach ($request->tags as $tag_id) {
                    ItineraryTag::create([
                        'itinerary_id' => $itinerary->id,
                        'tag_id' => $tag_id,
                    ]);
                }
            }
    
            // === Availability ===
            if ($request->has('availability')) {
                ItineraryAvailability::create([
                    'itinerary_id' => $itinerary->id,
                    'date_based_itinerary' => $request->availability['date_based_itinerary'],
                    'start_date' => $request->availability['start_date'] ?? null,
                    'end_date' => $request->availability['end_date'] ?? null,
                    'quantity_based_itinerary' => $request->availability['quantity_based_itinerary'],
                    'max_quantity' => $request->availability['max_quantity'] ?? null,
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Itinerary created successfully',
                'itinerary' => $itinerary
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage(),
            ], 500);
        }
    }     


    /**
     * Create or Update Itinerary.
    */
    // public function save(Request $request, $id = null)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'slug' => 'required|string|unique:itineraries,slug,' . $id,
    //         'description' => 'nullable|string',
    //         'featured_itinerary' => 'boolean',
    //         'private_itinerary' => 'boolean',
    //         'locations' => 'nullable|array',
    //         'schedules' => 'nullable|array',
    //         'activities' => 'nullable|array',
    //         'transfers' => 'nullable|array',
    //         'pricing' => 'nullable|array',
    //         'price_variations' => 'nullable|array',
    //         'blackout_dates' => 'nullable|array',
    //         'inclusions_exclusions' => 'nullable|array',
    //         'media_gallery' => 'nullable|array',
    //         'seo' => 'nullable|array',
    //         'categories' => 'nullable|array',
    //         'attributes' => 'nullable|array',
    //         'tags' => 'nullable|array',
    //         'availability' => 'nullable|array',
    //     ]);
    
    //     try {
    //         DB::beginTransaction();
    
    //         // Create or Update Itinerary
    //         $itinerary = Itinerary::updateOrCreate(
    //             ['id' => $id], 
    //             [
    //                 'name' => $request->name,
    //                 'slug' => $request->slug,
    //                 'description' => $request->description,
    //                 'featured_itinerary' => $request->featured_itinerary ?? false,
    //                 'private_itinerary' => $request->private_itinerary ?? false,
    //             ]
    //         );
    
    //         // Handle Locations
    //         if ($request->has('locations')) {
    //             $itinerary->locations()->delete();
    //             foreach ($request->locations as $location) {
    //                 ItineraryLocation::create([
    //                     'itinerary_id' => $itinerary->id,
    //                     'city_id' => $location['city_id'],
    //                 ]);
    //             }
    //         }

    //         $scheduleMap = [];

    //         // Build map from existing schedules: [day => id]
    //         $existingSchedules = $itinerary->schedules->keyBy('day');
            
    //         if ($request->has('schedules')) {
    //             foreach ($request->schedules as $schedule) {
    //                 $day = $schedule['day'];
            
    //                 if (isset($existingSchedules[$day])) {
    //                     // ✅ Use existing schedule
    //                     $scheduleMap[$day] = $existingSchedules[$day]->id;
    //                 } else {
    //                     // 🆕 If new day, create it
    //                     $newSchedule = ItinerarySchedule::create([
    //                         'itinerary_id' => $itinerary->id,
    //                         'day' => $day,
    //                     ]);
    //                     $scheduleMap[$day] = $newSchedule->id;
    //                 }
    //             }
    //         }

    //         if ($request->has('activities')) {
    //             $existingIds = ItineraryActivity::whereHas('schedule', fn($q) => $q->where('itinerary_id', $itinerary->id))->pluck('id')->toArray();
    //             $sentIds = collect($request->activities)->pluck('id')->filter()->toArray();
            
    //             $toDelete = array_diff($existingIds, $sentIds);
    //             if ($toDelete) ItineraryActivity::whereIn('id', $toDelete)->delete();
            
    //             foreach ($request->activities as $activity) {
    //                 $scheduleId = $scheduleMap[$activity['day']] ?? null;
    //                 if (!$scheduleId) continue;
            
    //                 $data = [
    //                     'schedule_id' => $scheduleId,
    //                     'activity_id' => $activity['activity_id'],
    //                     'start_time' => $activity['start_time'],
    //                     'end_time' => $activity['end_time'],
    //                     'notes' => $activity['notes'],
    //                     'price' => $activity['price'],
    //                     'include_in_Itinerary' => $activity['include_in_Itinerary'],
    //                 ];
            
    //                 if (!empty($activity['id']) && in_array($activity['id'], $existingIds)) {
    //                     ItineraryActivity::where('id', $activity['id'])->update($data);
    //                 } else {
    //                     ItineraryActivity::create($data);
    //                 }
    //             }
    //         }

    //         if ($request->has('transfers')) {
    //             $existingIds = ItineraryTransfer::whereHas('schedule', fn($q) => $q->where('itinerary_id', $itinerary->id))->pluck('id')->toArray();
    //             $sentIds = collect($request->transfers)->pluck('id')->filter()->toArray();
            
    //             $toDelete = array_diff($existingIds, $sentIds);
    //             if ($toDelete) ItineraryTransfer::whereIn('id', $toDelete)->delete();
            
    //             foreach ($request->transfers as $transfer) {
    //                 $scheduleId = $scheduleMap[$transfer['day']] ?? null;
    //                 if (!$scheduleId) continue;
            
    //                 $data = [
    //                     'schedule_id' => $scheduleId,
    //                     'transfer_id' => $transfer['transfer_id'],
    //                     'start_time' => $transfer['start_time'],
    //                     'end_time' => $transfer['end_time'],
    //                     'notes' => $transfer['notes'],
    //                     'price' => $transfer['price'],
    //                     'include_in_Itinerary' => $transfer['include_in_Itinerary'],
    //                     'pickup_location' => $transfer['pickup_location'] ?? null,
    //                     'dropoff_location' => $transfer['dropoff_location'] ?? null,
    //                     'pax' => $transfer['pax'] ?? null,
    //                 ];
            
    //                 if (!empty($transfer['id']) && in_array($transfer['id'], $existingIds)) {
    //                     ItineraryTransfer::where('id', $transfer['id'])->update($data);
    //                 } else {
    //                     ItineraryTransfer::create($data);
    //                 }
    //             }
    //         }
                        
    
    //         // Handle Pricing
    //         if ($request->has('pricing')) {
    //             // Create or Update the Base Pricing
    //             $basePricing = ItineraryBasePricing::updateOrCreate(
    //                 ['itinerary_id' => $itinerary->id],
    //                 [
    //                     'currency' => $request->pricing['currency'],
    //                     'availability' => $request->pricing['availability'],
    //                     'start_date' => $request->pricing['start_date'],
    //                     'end_date' => $request->pricing['end_date'],
    //                 ]
    //             );

    //             // Handle Price Variations
    //             if ($request->has('price_variations')) {
    //                 // Remove existing price variations for this base pricing
    //                 $basePricing->variations()->delete();

    //                 foreach ($request->price_variations as $variation) {
    //                     ItineraryPriceVariation::create([
    //                         'base_pricing_id' => $basePricing->id,
    //                         'name' => $variation['name'],
    //                         'regular_price' => $variation['regular_price'],
    //                         'sale_price' => $variation['sale_price'],
    //                         'max_guests' => $variation['max_guests'],
    //                         'description' => $variation['description'],
    //                     ]);
    //                 }
    //             }
    //         }

    //         // Handle Blackout Dates
    //         if ($request->has('blackout_dates')) {
    //             // Remove existing blackout dates for this base pricing
    //             $basePricing->blackoutDates()->delete();

    //             foreach ($request->blackout_dates as $date) {
    //                 ItineraryBlackoutDate::create([
    //                     'base_pricing_id' => $basePricing->id,
    //                     'date' => $date['date'],
    //                     'reason' => $date['reason'],
    //                 ]);
    //             }
    //         }
    
    //         // Handle Inclusions & Exclusions
    //         if ($request->has('inclusions_exclusions')) {
    //             $itinerary->inclusionsExclusions()->delete();
    //             foreach ($request->inclusions_exclusions as $ie) {
    //                 ItineraryInclusionExclusion::create([
    //                     'itinerary_id' => $itinerary->id,
    //                     'type' => $ie['type'],
    //                     'title' => $ie['title'],
    //                     'description' => $ie['description'],
    //                     // Convert 'include'/'exclude' to 1/0 if the column is integer type
    //                     'include_exclude' => $ie['include_exclude'] === 'include' ? 1 : 0,
    //                 ]);
    //             }
    //         }
    
    //         // Handle Media Gallery
    //         if ($request->has('media_gallery')) {
    //             $itinerary->mediaGallery()->delete();
    //             foreach ($request->media_gallery as $media) {
    //                 ItineraryMediaGallery::create([
    //                     'itinerary_id' => $itinerary->id,
    //                     'url' => $media['url'],
    //                 ]);
    //             }
    //         }
    
    //         // Handle SEO
    //         if ($request->has('seo')) {
    //             ItinerarySeo::updateOrCreate(
    //                 ['itinerary_id' => $itinerary->id],
    //                 [
    //                     'meta_title' => $request->seo['meta_title'],
    //                     'meta_description' => $request->seo['meta_description'],
    //                     'keywords' => $request->seo['keywords'],
    //                     'og_image_url' => $request->seo['og_image_url'],
    //                     'canonical_url' => $request->seo['canonical_url'],
    //                     'schema_type' => $request->seo['schema_type'],
    //                     'schema_data' => json_encode($request->seo['schema_data']),
    //                 ]
    //             );
    //         }

    //         // Handle Categories
    //         if ($request->has('categories')) {
    //             $itinerary->categories()->delete();
    //             foreach ($request->categories as $category_id) {
    //                 ItineraryCategory::updateOrCreate([
    //                     'itinerary_id' => $itinerary->id,
    //                     'category_id' => $category_id,
    //                 ]);
    //             }
    //         }

    //         // Handle Attributes
    //         if ($request->has('attributes')) {
    //             $itinerary->attributes()->delete(); 
            
    //             $attributes = $request->input('attributes', []); 
            
    //             foreach ($attributes as $attribute) {
    //                 ItineraryAttribute::updateOrCreate(
    //                     [
    //                         'itinerary_id' => $itinerary->id,
    //                         'attribute_id' => $attribute['attribute_id'],
    //                     ],
    //                     [
    //                         'attribute_value' => $attribute['attribute_value'],
    //                     ]
    //                 );
    //             }
    //         }

    //         // Handle Tags
    //         if ($request->has('tags')) {
    //             $itinerary->tags()->delete();
    //             foreach ($request->tags as $tag_id) {
    //                 ItineraryTag::updateOrCreate([
    //                     'itinerary_id' => $itinerary->id,
    //                     'tag_id' => $tag_id,
    //                 ]);
    //             }
    //         }

    //         // Handle Availability
    //         if ($request->has('availability')) {
    //             ItineraryAvailability::updateOrCreate(
    //                 ['itinerary_id' => $itinerary->id],
    //                 [
    //                     'date_based_itinerary' => $request->availability['date_based_itinerary'],
    //                     'start_date' => $request->availability['start_date'] ?? null,
    //                     'end_date' => $request->availability['end_date'] ?? null,
    //                     'quantity_based_itinerary' => $request->availability['quantity_based_itinerary'],
    //                     'max_quantity' => $request->availability['max_quantity'] ?? null,
    //                 ]
    //             );
    //         }
    
    //         DB::commit();
    //         return response()->json(['message' => $id ? 'Itinerary updated' : 'Itinerary created', 'itinerary' => $itinerary], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
    //     }
    // }


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


        // Transform response
        $itineraryData = $itinerary->toArray();
    
        // Replace location city object with just `city_name`
        $itineraryData['locations'] = collect($itinerary->locations)->map(function ($location) {
            return [
                'id' => $location->id,
                'itinerary_id' => $location->itinerary_id,
                'city_id' => $location->city_id,
                'city_name' => $location->city->name ?? null,
            ];
        });
    
        // Replace attributes with just `attribute_name`
        $itineraryData['attributes'] = collect($itinerary->attributes)->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'attribute_id' => $attribute->attribute_id,
                'attribute_name' => $attribute->attribute->name ?? null,
                'attribute_value' => $attribute->attribute_value,
            ];
        });
    
        // Replace categories with just `category_name`
        $itineraryData['categories'] = collect($itinerary->categories)->map(function ($category) {
            return [
                'id' => $category->id,
                'category_id' => $category->category_id,
                'category_name' => $category->category->name ?? null,
            ];
        });
        $itineraryData['tags'] = collect($itinerary->tags)->map(function ($tag) {
            return [
                'id' => $tag->id,
                'tag_id' => $tag->tag_id,
                'tag_name' => $tag->tag->name ?? null,
            ];
        });

        return response()->json($itineraryData);
    }

    /**
     * Update the specified Itinerary in storage.
     */
    public function update(Request $request, $id)
    {
        $itinerary = Itinerary::findOrFail($id);
    
        $rules = [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:itinerarys,slug,' . $itinerary->id,
            'description' => 'nullable|string',
            'featured_itinerary' => 'boolean',
            'private_itinerary' => 'boolean',
            'locations' => 'nullable|array',
            'information' => 'nullable|array',
            'schedules' => 'nullable|array',
            'activities' => 'nullable|array',
            'transfers' => 'nullable|array',
            'itineraries' => 'nullable|array',
            'pricing' => 'nullable|array',
            'price_variations' => 'nullable|array',
            'blackout_dates' => 'nullable|array',
            'inclusions_exclusions' => 'nullable|array',
            'media_gallery' => 'nullable|array',
            'faqs' => 'nullable|array',
            'seo' => 'nullable|array',
            'categories' => 'nullable|array',
            'attributes' => 'nullable|array',
            'tags' => 'nullable|array',
            'availability' => 'nullable|array',
        ];
    
        $request->validate($rules);
    
        try {
            DB::beginTransaction();
    
            $itinerary->fill($request->only([
                'name', 'slug', 'description', 'featured_itinerary', 'private_itinerary'
            ]));
            $itinerary->save();
    
            $scheduleMap = [];
    
            $updateOrCreateRelation = function ($relationName, $data, $extra = []) use ($itinerary) {
                $relation = $itinerary->$relationName();
                $existing = $relation->pluck('id')->toArray();
    
                foreach ($data as $item) {
                    $attributes = array_merge($item, $extra);
                    if (!empty($item['id']) && in_array($item['id'], $existing)) {
                        $relation->where('id', $item['id'])->update($attributes);
                    } else {
                        $relation->create($attributes);
                    }
                }
            };
    
            foreach (['information', 'locations', 'faqs', 'inclusionsExclusions', 'mediaGallery'] as $relation) {
                if ($request->has(Str::snake($relation))) {
                    $updateOrCreateRelation($relation, $request->{Str::snake($relation)});
                }
            }
    
            if ($request->has('schedules')) {
                $updateOrCreateRelation('schedules', $request->schedules);
                foreach ($itinerary->schedules as $schedule) {
                    $scheduleMap[$schedule->day] = $schedule->id;
                }
            }

            $scheduleMap = $itinerary->schedules()->pluck('id', 'day')->toArray();

            $updateOrCreateSimple = function ($modelClass, $data, $scheduleMap = []) {
                foreach ($data as $item) {
                    if (!empty($item['id'])) {
                        $model = $modelClass::find($item['id']);
                        if ($model) {
                            if (isset($item['day']) && empty($item['schedule_id'])) {
                                $scheduleId = $scheduleMap[$item['day']] ?? null;
                                if ($scheduleId) {
                                    $item['schedule_id'] = $scheduleId;
                                }
                                unset($item['day']);
                            }

                            $model->fill($item);
                            $model->save();
                        }
                    }
                }
            };
   
            
            
            if ($request->has('activities')) {
                $updateOrCreateSimple(\App\Models\ItineraryActivity::class, $request->activities, $scheduleMap);

            }
            
            if ($request->has('transfers')) {
                $updateOrCreateSimple(\App\Models\ItineraryTransfer::class, $request->transfers, $scheduleMap);

            }

            $pricing = $itinerary->basePricing()->first();

            // If pricing is present in request, create or update it
            if ($request->has('pricing')) {
                $pricing = $itinerary->basePricing()->firstOrCreate([]);
                $pricing->fill($request->pricing)->save();
            }

            $updateOrCreateChild = function ($relation, $data, $modelClass, $foreignKey) use ($pricing) {
                foreach ($data as $item) {
                    if (!empty($item['id'])) {
                        $model = $modelClass::find($item['id']);
                        if ($model) {
                            $model->fill($item);
                            $model->save();
                        }
                    } else {
                        $item[$foreignKey] = $pricing->id;
                        $modelClass::create($item);
                    }
                }
            };
            
            if ($request->has('price_variations')) {
                $updateOrCreateChild('priceVariations', $request->price_variations, \App\Models\ItineraryPriceVariation::class, 'base_pricing_id');
            }
            
            if ($request->has('blackout_dates')) {
                $updateOrCreateChild('blackoutDates', $request->blackout_dates, \App\Models\ItineraryBlackoutDate::class, 'base_pricing_id');
            }

            if ($request->has('categories')) {
                foreach ($request->categories as $category) {
                    if (!empty($category['id'])) {
                        $itinerary->categories()->where('id', $category['id'])->update(['category_id' => $category['category_id']]);
                    } else {
                        $itinerary->categories()->create(['category_id' => $tag['category_id']]);
                    }
                }
            }

            if ($request->has('tags')) {
                foreach ($request->tags as $tag) {
                    if (!empty($tag['id'])) {
                        $itinerary->tags()->where('id', $tag['id'])->update(['tag_id' => $tag['tag_id']]);
                    } else {
                        $itinerary->tags()->create(['tag_id' => $tag['tag_id']]);
                    }
                }
            }
    
            if ($request->has('attributes')) {
                $updateOrCreateRelation('attributes', $request->input('attributes'));
            }
    
            if ($request->has('availability')) {
                $itinerary->availability()->updateOrCreate([], $request->availability);
            }
    
            if ($request->has('seo')) {
                $seoData = $request->seo;
                if (isset($seoData['schema_data']) && is_array($seoData['schema_data'])) {
                    $seoData['schema_data'] = json_encode($seoData['schema_data']);
                }
                $itinerary->seo()->updateOrCreate([], $seoData);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'itinerary updated successfully',
                'itinerary' => $itinerary->fresh()
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
