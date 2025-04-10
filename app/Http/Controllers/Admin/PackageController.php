<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageInformation;
use App\Models\PackageLocation;
use App\Models\PackageSchedule;
use App\Models\PackageTransfer;
use App\Models\PackageActivity;
use App\Models\PackageItinerary;
use App\Models\PackageBasePricing;
use App\Models\PackagePriceVariation;
use App\Models\PackageBlackoutDate;
use App\Models\PackageInclusionExclusion;
use App\Models\PackageMediaGallery;
use App\Models\PackageCategory;
use App\Models\PackageAttribute;
use App\Models\PackageTag;
use App\Models\PackageFaq;
use App\Models\PackageSeo;
use App\Models\PackageAvailability;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:packages,slug',
            'description' => 'nullable|string',
            'featured_package' => 'boolean',
            'private_package' => 'boolean',
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
    
            $package = Package::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description ?? null,
                'featured_package' => $request->boolean('featured_package'),
                'private_package' => $request->boolean('private_package'),
            ]);
    
            // === Information ===
            if ($request->has('information')) {
                foreach ($request->information as $info) {
                    PackageInformation::create([
                        'package_id' => $package->id,
                        'section_title' => $info['section_title'] ?? '',
                        'content' => $info['content'] ?? '',
                    ]);
                }
            }
    
            // === Locations ===
            if ($request->has('locations')) {
                foreach ($request->locations as $location) {
                    PackageLocation::create([
                        'package_id' => $package->id,
                        'city_id' => $location['city_id'],
                    ]);
                }
            }
    
            // === Schedules ===
            $scheduleMap = [];
            if ($request->has('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $record = PackageSchedule::create([
                        'package_id' => $package->id,
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
                        PackageTransfer::create([
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
                        ]);
                    }
                }
            }
    
            // === Activities ===
            if ($request->has('activities')) {
                foreach ($request->activities as $activity) {
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
    
            // === Itineraries ===
            if ($request->has('itineraries')) {
                foreach ($request->itineraries as $itinerary) {
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
                            'pickup_location' => $itinerary['pickup_location'] ?? null,
                            'dropoff_location' => $itinerary['dropoff_location'] ?? null,
                            'pax' => $itinerary['pax'] ?? null,
                        ]);
                    }
                }
            }
    
            // === Pricing ===
            if ($request->has('pricing')) {
                $basePricing = PackageBasePricing::create([
                    'package_id' => $package->id,
                    'currency' => $request->pricing['currency'],
                    'availability' => $request->pricing['availability'],
                    'start_date' => $request->pricing['start_date'],
                    'end_date' => $request->pricing['end_date'],
                ]);
    
                if ($request->has('price_variations')) {
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
    
                if ($request->has('blackout_dates')) {
                    foreach ($request->blackout_dates as $date) {
                        PackageBlackoutDate::create([
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
                    PackageInclusionExclusion::create([
                        'package_id' => $package->id,
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
                    PackageMediaGallery::create([
                        'package_id' => $package->id,
                        'url' => $media['url'],
                    ]);
                }
            }
    
            // === FAQs ===
            if ($request->has('faqs')) {
                foreach ($request->faqs as $faq) {
                    PackageFaq::create([
                        'package_id' => $package->id,
                        'question_number' => $faq['question_number'] ?? null,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                }
            }
    
            // === SEO ===
            if ($request->has('seo')) {
                PackageSeo::create([
                    'package_id' => $package->id,
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
                    PackageCategory::create([
                        'package_id' => $package->id,
                        'category_id' => $category_id,
                    ]);
                }
            }

            if ($request->has('attributes')) {
            
                foreach ($request->input('attributes') as $attribute) {
                    PackageAttribute::create([
                        'package_id' => $package->id,
                        'attribute_id' => $attribute['attribute_id'],
                        'attribute_value' => $attribute['attribute_value'],
                    ]);
                }
            }
    
            // === Tags ===
            if ($request->has('tags')) {
                foreach ($request->tags as $tag_id) {
                    PackageTag::create([
                        'package_id' => $package->id,
                        'tag_id' => $tag_id,
                    ]);
                }
            }
    
            // === Availability ===
            if ($request->has('availability')) {
                PackageAvailability::create([
                    'package_id' => $package->id,
                    'date_based_package' => $request->availability['date_based_package'],
                    'start_date' => $request->availability['start_date'] ?? null,
                    'end_date' => $request->availability['end_date'] ?? null,
                    'quantity_based_package' => $request->availability['quantity_based_package'],
                    'max_quantity' => $request->availability['max_quantity'] ?? null,
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Package created successfully',
                'package' => $package
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
     * Display the specified Package.
     */
    public function show(string $id)
    {
        // $package = Package::find($id);

        $package = Package::with([
            'locations.city',
            'categories.category',
            'attributes.attribute',
            'tags.tag',
            'schedules.transfers',
            'schedules.activities',
            'schedules.itineraries',
            'basePricing.variations',
            'inclusionsExclusions',
            'mediaGallery',
            'availability',
            'seo',
        ])->find($id);
        
        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        // Transform response
        $packageData = $package->toArray();
    
        // Replace location city object with just `city_name`
        $packageData['locations'] = collect($package->locations)->map(function ($location) {
            return [
                'id' => $location->id,
                'package_id' => $location->package_id,
                'city_id' => $location->city_id,
                'city_name' => $location->city->name ?? null,
            ];
        });
    
        // Replace attributes with just `attribute_name`
        $packageData['attributes'] = collect($package->attributes)->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'attribute_id' => $attribute->attribute_id,
                'attribute_name' => $attribute->attribute->name ?? null,
                'attribute_value' => $attribute->attribute_value,
            ];
        });
    
        // Replace categories with just `category_name`
        $packageData['categories'] = collect($package->categories)->map(function ($category) {
            return [
                'id' => $category->id,
                'category_id' => $category->category_id,
                'category_name' => $category->category->name ?? null,
            ];
        });
        $packageData['tags'] = collect($package->tags)->map(function ($tag) {
            return [
                'id' => $tag->id,
                'tag_id' => $tag->tag_id,
                'tag_name' => $tag->tag->name ?? null,
            ];
        });

        return response()->json($packageData);
    }

    /**
     * Update the specified Package in storage.
     */
    public function update(Request $request, $id)
    {
        $package = Package::findOrFail($id);
    
        $rules = [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:packages,slug,' . $package->id,
            'description' => 'nullable|string',
            'featured_package' => 'boolean',
            'private_package' => 'boolean',
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
    
            $package->fill($request->only([
                'name', 'slug', 'description', 'featured_package', 'private_package'
            ]));
            $package->save();
    
            $scheduleMap = [];
    
            $updateOrCreateRelation = function ($relationName, $data, $extra = []) use ($package) {
                $relation = $package->$relationName();
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
                foreach ($package->schedules as $schedule) {
                    $scheduleMap[$schedule->day] = $schedule->id;
                }
            }

            $scheduleMap = $package->schedules()->pluck('id', 'day')->toArray();

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
                $updateOrCreateSimple(\App\Models\PackageActivity::class, $request->activities, $scheduleMap);

            }
            
            if ($request->has('transfers')) {
                $updateOrCreateSimple(\App\Models\PackageTransfer::class, $request->transfers, $scheduleMap);

            }
            
            if ($request->has('itineraries')) {
                $updateOrCreateSimple(\App\Models\PackageItinerary::class, $request->itineraries, $scheduleMap);

            }

            $pricing = $package->basePricing()->first();

            // If pricing is present in request, create or update it
            if ($request->has('pricing')) {
                $pricing = $package->basePricing()->firstOrCreate([]);
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
                $updateOrCreateChild('priceVariations', $request->price_variations, \App\Models\PackagePriceVariation::class, 'base_pricing_id');
            }
            
            if ($request->has('blackout_dates')) {
                $updateOrCreateChild('blackoutDates', $request->blackout_dates, \App\Models\PackageBlackoutDate::class, 'base_pricing_id');
            }

            if ($request->has('categories')) {
                foreach ($request->categories as $category) {
                    if (!empty($category['id'])) {
                        $package->categories()->where('id', $category['id'])->update(['category_id' => $category['category_id']]);
                    } else {
                        $package->categories()->create(['category_id' => $tag['category_id']]);
                    }
                }
            }

            if ($request->has('tags')) {
                foreach ($request->tags as $tag) {
                    if (!empty($tag['id'])) {
                        $package->tags()->where('id', $tag['id'])->update(['tag_id' => $tag['tag_id']]);
                    } else {
                        $package->tags()->create(['tag_id' => $tag['tag_id']]);
                    }
                }
            }
    
            if ($request->has('attributes')) {
                $updateOrCreateRelation('attributes', $request->input('attributes'));
            }
    
            if ($request->has('availability')) {
                $package->availability()->updateOrCreate([], $request->availability);
            }
    
            if ($request->has('seo')) {
                $seoData = $request->seo;
                if (isset($seoData['schema_data']) && is_array($seoData['schema_data'])) {
                    $seoData['schema_data'] = json_encode($seoData['schema_data']);
                }
                $package->seo()->updateOrCreate([], $seoData);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Package updated successfully',
                'package' => $package->fresh()
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
