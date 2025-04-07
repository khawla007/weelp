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
use Illuminate\Support\Str;
// use Illuminate\Validation\Rule;
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
            'featured_package' => 'boolean',
            'private_package' => 'boolean',
            'locations' => 'nullable|array',
            'infomration' => 'nullable|array',
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
        ]);
    
        try {
            DB::beginTransaction();
    
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
    
            //Information
            if ($request->has('information') && is_array($request->information)) {
                $existingInfo = $package->information()->get()->keyBy('id');
                $incomingInfoIds = [];
            
                foreach ($request->information as $info) {
                    $packageInfo = isset($info['id'])
                        ? PackageInformation::find($info['id']) ?? new PackageInformation()
                        : new PackageInformation();
            
                    $packageInfo->package_id = $package->id;
                    $packageInfo->section_title = $info['section_title'];
                    $packageInfo->content = $info['content'];
                    $packageInfo->save();
            
                    $incomingInfoIds[] = $packageInfo->id;
                }
            
                // Delete removed records
                PackageInformation::where('package_id', $package->id)
                    ->whereNotIn('id', $incomingInfoIds)
                    ->delete();
            }
            
            // Locations
            if ($request->has('locations')) {
                $existing = $package->locations()->get()->keyBy('city_id');
                $incoming = collect($request->locations)->pluck('city_id')->toArray();
    
                $toDelete = $existing->keys()->diff($incoming);
                if ($toDelete->count()) {
                    $package->locations()->whereIn('city_id', $toDelete)->delete();
                }
    
                foreach ($incoming as $city_id) {
                    PackageLocation::updateOrCreate([
                        'package_id' => $package->id,
                        'city_id' => $city_id,
                    ]);
                }
            }
    
            // Schedules
            $scheduleMap = [];
            $existingSchedules = $package->schedules->keyBy('day');
            if ($request->has('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $day = $schedule['day'];
                    if (isset($existingSchedules[$day])) {
                        $scheduleMap[$day] = $existingSchedules[$day]->id;
                    } else {
                        $newSchedule = PackageSchedule::create([
                            'package_id' => $package->id,
                            'day' => $day,
                        ]);
                        $scheduleMap[$day] = $newSchedule->id;
                    }
                }
            }
    
            // Handle Transfers
            if ($request->has('transfers')) {
                $existing = PackageTransfer::whereHas('schedule', fn($q) => $q->where('package_id', $package->id))->get();
                $existingMap = $existing->keyBy(fn($item) => $item->schedule_id . '-' . $item->transfer_id);
                $keepIds = [];
            
                foreach ($request->transfers as $transfer) {
                    $scheduleId = $scheduleMap[$transfer['day']] ?? null;
                    if (!$scheduleId) continue;
            
                    $key = $scheduleId . '-' . $transfer['transfer_id'];
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
            
                    $model = !empty($transfer['id']) ? $existing->firstWhere('id', $transfer['id']) : ($existingMap[$key] ?? null);
            
                    if ($model) {
                        $model->update($data);
                        $keepIds[] = $model->id;
                    } else {
                        $new = PackageTransfer::create($data);
                        $keepIds[] = $new->id;
                    }
                }
            
                $toDelete = $existing->pluck('id')->diff($keepIds);
                if ($toDelete->isNotEmpty()) {
                    PackageTransfer::whereIn('id', $toDelete)->delete();
                }
            }            

            // Handle Activities
            if ($request->has('activities')) {
                $existing = PackageActivity::whereHas('schedule', fn($q) => $q->where('package_id', $package->id))->get();
                $existingMap = $existing->keyBy(fn($item) => $item->schedule_id . '-' . $item->activity_id);
                $keepIds = [];
            
                foreach ($request->activities as $activity) {
                    $scheduleId = $scheduleMap[$activity['day']] ?? null;
                    if (!$scheduleId) continue;
            
                    $key = $scheduleId . '-' . $activity['activity_id'];
                    $data = [
                        'schedule_id' => $scheduleId,
                        'activity_id' => $activity['activity_id'],
                        'start_time' => $activity['start_time'],
                        'end_time' => $activity['end_time'],
                        'notes' => $activity['notes'],
                        'price' => $activity['price'],
                        'include_in_package' => $activity['include_in_package'],
                    ];
            
                    if (!empty($activity['id'])) {
                        $model = $existing->firstWhere('id', $activity['id']);
                    } else {
                        $model = $existingMap[$key] ?? null;
                    }
            
                    if ($model) {
                        $model->update($data);
                        $keepIds[] = $model->id;
                    } else {
                        $new = PackageActivity::create($data);
                        $keepIds[] = $new->id;
                    }
                }
            
                // Delete records not in request
                $toDelete = $existing->pluck('id')->diff($keepIds);
                if ($toDelete->isNotEmpty()) {
                    PackageActivity::whereIn('id', $toDelete)->delete();
                }
            }
        

            if ($request->has('itineraries')) {
                $existing = PackageItinerary::whereHas('schedule', fn($q) => $q->where('package_id', $package->id))->get();
                $existingMap = $existing->keyBy(fn($item) => $item->schedule_id . '-' . $item->itinerary_id);
                $keepIds = [];
            
                foreach ($request->itineraries as $item) {
                    $scheduleId = $scheduleMap[$item['day']] ?? null;
                    if (!$scheduleId) continue;
            
                    $key = $scheduleId . '-' . $item['itinerary_id'];
                    $data = [
                        'schedule_id' => $scheduleId,
                        'itinerary_id' => $item['itinerary_id'],
                        'start_time' => $item['start_time'],
                        'end_time' => $item['end_time'],
                        'notes' => $item['notes'],
                        'price' => $item['price'],
                        'include_in_package' => $item['include_in_package'],
                        'pickup_location' => $item['pickup_location'] ?? null,
                        'dropoff_location' => $item['dropoff_location'] ?? null,
                        'pax' => $item['pax'] ?? null,
                    ];
            
                    $model = !empty($item['id']) ? $existing->firstWhere('id', $item['id']) : ($existingMap[$key] ?? null);
            
                    if ($model) {
                        $model->update($data);
                        $keepIds[] = $model->id;
                    } else {
                        $new = PackageItinerary::create($data);
                        $keepIds[] = $new->id;
                    }
                }
            
                $toDelete = $existing->pluck('id')->diff($keepIds);
                if ($toDelete->isNotEmpty()) {
                    PackageItinerary::whereIn('id', $toDelete)->delete();
                }
            }
            
    
            // Pricing
            if ($request->has('pricing')) {
                $basePricing = PackageBasePricing::updateOrCreate(
                    ['package_id' => $package->id],
                    [
                        'currency' => $request->pricing['currency'],
                        'availability' => $request->pricing['availability'],
                        'start_date' => $request->pricing['start_date'],
                        'end_date' => $request->pricing['end_date'],
                    ]
                );
    
                // Price Variations
                if ($request->has('price_variations')) {
                    $existing = $basePricing->variations()->get()->keyBy('id');
                    $incoming = collect($request->price_variations);
                    $incomingIds = $incoming->pluck('id')->filter()->toArray();
    
                    $toDelete = array_diff($existing->keys()->toArray(), $incomingIds);
                    if ($toDelete) PackagePriceVariation::whereIn('id', $toDelete)->delete();
    
                    foreach ($incoming as $variation) {
                        $data = [
                            'base_pricing_id' => $basePricing->id,
                            'name' => $variation['name'],
                            'regular_price' => $variation['regular_price'],
                            'sale_price' => $variation['sale_price'],
                            'max_guests' => $variation['max_guests'],
                            'description' => $variation['description'],
                        ];
    
                        if (!empty($variation['id']) && isset($existing[$variation['id']])) {
                            $existing[$variation['id']]->update($data);
                        } else {
                            PackagePriceVariation::create($data);
                        }
                    }
                }
    
                // Blackout Dates
                if ($request->has('blackout_dates')) {
                    $existing = $basePricing->blackoutDates()->get()->keyBy('id');
                    $incoming = collect($request->blackout_dates);
                    $incomingIds = $incoming->pluck('id')->filter()->toArray();
    
                    $toDelete = array_diff($existing->keys()->toArray(), $incomingIds);
                    if ($toDelete) PackageBlackoutDate::whereIn('id', $toDelete)->delete();
    
                    foreach ($incoming as $date) {
                        $data = [
                            'base_pricing_id' => $basePricing->id,
                            'date' => $date['date'],
                            'reason' => $date['reason'],
                        ];
    
                        if (!empty($date['id']) && isset($existing[$date['id']])) {
                            $existing[$date['id']]->update($data);
                        } else {
                            PackageBlackoutDate::create($data);
                        }
                    }
                }
            }
    
            // Inclusions/Exclusions
            if ($request->has('inclusions_exclusions')) {
                $existing = $package->inclusionsExclusions()->get()->keyBy('id');
                $incoming = collect($request->inclusions_exclusions);
                $incomingIds = $incoming->pluck('id')->filter()->toArray();
    
                $toDelete = array_diff($existing->keys()->toArray(), $incomingIds);
                if ($toDelete) PackageInclusionExclusion::whereIn('id', $toDelete)->delete();
    
                foreach ($incoming as $ie) {
                    $data = [
                        'package_id' => $package->id,
                        'type' => $ie['type'],
                        'title' => $ie['title'],
                        'description' => $ie['description'],
                        'include_exclude' => $ie['include_exclude'] === 'include' ? 1 : 0,
                    ];
    
                    if (!empty($ie['id']) && isset($existing[$ie['id']])) {
                        $existing[$ie['id']]->update($data);
                    } else {
                        PackageInclusionExclusion::create($data);
                    }
                }
            }
    
            // Media Gallery
            if ($request->has('media_gallery')) {
                $existing = $package->mediaGallery()->get()->keyBy('id');
                $incoming = collect($request->media_gallery);
                $incomingIds = $incoming->pluck('id')->filter()->toArray();
    
                $toDelete = array_diff($existing->keys()->toArray(), $incomingIds);
                if ($toDelete) PackageMediaGallery::whereIn('id', $toDelete)->delete();
    
                foreach ($incoming as $media) {
                    $data = [
                        'package_id' => $package->id,
                        'url' => $media['url'],
                    ];
    
                    if (!empty($media['id']) && isset($existing[$media['id']])) {
                        $existing[$media['id']]->update($data);
                    } else {
                        PackageMediaGallery::create($data);
                    }
                }
            }
    

            // Package FAQs
            if ($request->has('faqs') && is_array($request->faqs)) {
                $existingFaqs = $package->faqs()->get()->keyBy('id');
                $keep = [];

                foreach ($request->faqs as $faq) {
                    $faqModel = isset($faq['id']) ? PackageFaq::find($faq['id']) : new PackageFaq();
                    if (!$faqModel) {
                        $faqModel = new PackageFaq();
                    }

                    $faqModel->package_id = $package->id;
                    $faqModel->question_number = $faq['question_number'] ?? null;
                    $faqModel->question = $faq['question'] ?? '';
                    $faqModel->answer = $faq['answer'] ?? '';
                    $faqModel->save();

                    $keep[] = $faqModel->id;
                }

                // Delete removed
                if (!empty($keep)) {
                    PackageFaq::where('package_id', $package->id)->whereNotIn('id', $keep)->delete();
                }
            }
            
            // SEO
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
    
            // Categories
            if ($request->has('categories')) {
                $existing = $package->categories()->get()->pluck('category_id')->toArray();
                $incoming = $request->categories;
    
                $toDelete = array_diff($existing, $incoming);
                if ($toDelete) {
                    $package->categories()->whereIn('category_id', $toDelete)->delete();
                }
    
                foreach ($incoming as $category_id) {
                    PackageCategory::updateOrCreate([
                        'package_id' => $package->id,
                        'category_id' => $category_id,
                    ]);
                }
            }

            // Attributes
            $attributes = $request->input('attributes', []);

            if (is_array($attributes) && count($attributes)) {
                $existingAttributes = $package->attributes()->get()->keyBy('attribute_id');
                $sentAttributeIds = [];

                foreach ($attributes as $attribute) {
                    if (!isset($attribute['attribute_id'])) {
                        continue;
                    }

                    $sentAttributeIds[] = $attribute['attribute_id'];

                    $packageAttr = PackageAttribute::firstOrNew([
                        'package_id' => $package->id,
                        'attribute_id' => $attribute['attribute_id'],
                    ]);

                    $packageAttr->attribute_value = $attribute['attribute_value'];
                    $packageAttr->save();
                }

                PackageAttribute::where('package_id', $package->id)
                    ->whereNotIn('attribute_id', $sentAttributeIds)
                    ->delete();
            }             
    
            // Tags
            if ($request->has('tags')) {
                $existing = $package->tags()->get()->pluck('tag_id')->toArray();
                $incoming = $request->tags;
    
                $toDelete = array_diff($existing, $incoming);
                if ($toDelete) {
                    $package->tags()->whereIn('tag_id', $toDelete)->delete();
                }
    
                foreach ($incoming as $tag_id) {
                    PackageTag::updateOrCreate([
                        'package_id' => $package->id,
                        'tag_id' => $tag_id,
                    ]);
                }
            }
    
            // Availability
            if ($request->has('availability')) {
                PackageAvailability::updateOrCreate(
                    ['package_id' => $package->id],
                    [
                        'date_based_package' => $request->availability['date_based_package'],
                        'start_date' => $request->availability['start_date'] ?? null,
                        'end_date' => $request->availability['end_date'] ?? null,
                        'quantity_based_package' => $request->availability['quantity_based_package'],
                        'max_quantity' => $request->availability['max_quantity'] ?? null,
                    ]
                );
            }
    
            DB::commit();
            return response()->json(['message' => $id ? 'Package updated' : 'Package created', 'package' => $package], 200);
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
