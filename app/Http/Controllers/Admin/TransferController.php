<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transfer;
use App\Models\TransferVendorRoute;
use App\Models\TransferPricingAvailability;
use App\Models\TransferSchedule;
use App\Models\TransferMediaGallery;
use App\Models\TransferSeo;

class TransferController extends Controller
{
    /**
     * Display a listing of the transfers.
     */
    public function index(Request $request)
    {
        $perPage = 3;
        $page = $request->get('page', 1);
        $sortBy = $request->get('sort_by', 'id_desc');

        $vehicleType = $request->get('vehicle_type');
        $capacity = $request->get('capacity');
        $minPrice = $request->get('min_price', 0);
        $maxPrice = $request->get('max_price');
        $availabilityDate = $request->get('availability_date');

        $query = Transfer::query()
            ->with([
                'vendorRoutes.route',
                'pricingAvailability.pricingTier',
                'pricingAvailability.availability',
                'mediaGallery.media',
                'seo',
            ])
            ->when($vehicleType || $capacity, function ($q) use ($vehicleType, $capacity) {
                // Filter by vehicle attributes via whereHas
                $q->whereHas('vendorRoutes.vendor.vehicles', function ($q2) use ($vehicleType, $capacity) {
                    if ($vehicleType) {
                        $q2->where('vehicle_type', $vehicleType);
                    }
                    if ($capacity) {
                        $q2->where('capacity', '>=', $capacity);
                    }
                });
            })
            ->when($minPrice !== null && $maxPrice !== null, function ($q) use ($minPrice, $maxPrice) {
                $q->whereHas('pricingAvailability.pricingTier', function ($q2) use ($minPrice, $maxPrice) {
                    $q2->whereBetween('base_price', [$minPrice, $maxPrice]);
                });
            })
            ->when($availabilityDate, function ($q) use ($availabilityDate) {
                $q->whereHas('pricingAvailability.availability', function ($q2) use ($availabilityDate) {
                    $q2->whereDate('date', $availabilityDate);
                });
            });

        // Sorting
        switch ($sortBy) {
            case 'price_asc':
                $query->with(['pricingAvailability.pricingTier' => function($q){
                    $q->orderBy('base_price', 'asc');
                }]);
                break;
            case 'price_desc':
                $query->with(['pricingAvailability.pricingTier' => function($q){
                    $q->orderBy('base_price', 'desc');
                }]);
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'id_asc':
                $query->orderBy('id', 'asc');
                break;
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $transformed = $paginated->getCollection()->map(function ($transfer) {
            $data = $transfer->toArray();

            $data['media_gallery'] = collect($transfer->mediaGallery)->map(function ($media) {
                return [
                    'id'       => $media->id,
                    'media_id' => $media->media_id,
                    'name'     => $media->media->name ?? null,
                    'alt_text' => $media->media->alt_text ?? null,
                    'url'      => $media->media->url ?? null,
                ];
            });

            return $data;
        });

        return response()->json([
            'success'      => true,
            'data'         => $transformed,
            'current_page' => $paginated->currentPage(),
            'per_page'     => $paginated->perPage(),
            'total'        => $paginated->total(),
        ]);
    }

    /**
     * Store a newly created transfers in storage.
     */
    public function store(Request $request)
    {
        // Base validation rules
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'transfer_type' => 'required|string',
            'is_vendor' => 'required|boolean',
    
            // Vendor related
            'vendor_id' => 'nullable|integer|exists:vendors,id',
            'route_id' => 'nullable|integer|exists:vendor_routes,id',
    
            // Non-vendor location fields
            'pickup_location'  => 'nullable|string|max:255',
            'dropoff_location' => 'nullable|string|max:255',
            'vehicle_type'     => 'nullable|string|max:255',
            'inclusion'        => 'nullable|string',
    
            // Vendor pricing/availability
            'pricing_tier_id' => 'nullable|integer|exists:vendor_pricing_tiers,id',
            'availability_id' => 'nullable|integer|exists:vendor_availability_time_slots,id',
    
            // Non-vendor pricing fields
            'base_price'           => 'nullable|numeric',
            'currency'             => 'nullable|string|max:10',
            'price_type'           => 'nullable|string|max:255',
            'extra_luggage_charge' => 'nullable|numeric',
            'waiting_charge'       => 'nullable|numeric',
    
            // Schedule fields
            'availability_type' => 'nullable|in:always_available,specific_date,custom_schedule',
            'available_days'    => 'nullable|array',
            'available_days.*'  => 'string',
            'time_slots'        => 'nullable|array',
            'time_slots.*.start'=> 'required_with:time_slots|date_format:H:i',
            'time_slots.*.end'  => 'required_with:time_slots|date_format:H:i',
            'blackout_dates'    => 'nullable|array',
            'blackout_dates.*'  => 'date',
            'minimum_lead_time' => 'nullable|integer',
            'maximum_passengers'=> 'nullable|integer',
    
            // Media
            'media_gallery'     => 'nullable|array',
    
            // SEO
            'seo' => 'array',
            'seo.meta_title'       => 'nullable|string|max:255',
            'seo.meta_description' => 'nullable|string',
            'seo.keywords'         => 'nullable|string',
            'seo.og_image_url'     => 'nullable|string',
            'seo.canonical_url'    => 'nullable|string',
            'seo.schema_type'      => 'nullable|string',
            'seo.schema_data'      => 'nullable|array',
        ]);
    
        // Conditional validations
        if ($validatedData['is_vendor']) {
            $request->validate([
                'vendor_id' => 'required|integer|exists:vendors,id',
                'route_id' => 'required|integer|exists:vendor_routes,id',
                'pricing_tier_id' => 'required|integer|exists:vendor_pricing_tiers,id',
                'availability_id' => 'required|integer|exists:vendor_availability_time_slots,id',
            ]);
        } else {
            $request->validate([
                'pickup_location'      => 'required|string|max:255',
                'dropoff_location'     => 'required|string|max:255',
                'vehicle_type'         => 'required|string|max:255',
                'inclusion'            => 'required|string',
                'base_price'           => 'required|numeric',
                'currency'             => 'required|string|max:10',
                'price_type'           => 'required|string|max:255',
                'extra_luggage_charge' => 'required|numeric',
                'waiting_charge'       => 'required|numeric',      
            ]);
        }
    
        // Create Transfer
        $transfer = Transfer::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'transfer_type' => $validatedData['transfer_type'],
        ]);
    
        // Create TransferVendorRoute
        TransferVendorRoute::create([
            'transfer_id'      => $transfer->id,
            'is_vendor'        => $validatedData['is_vendor'],
            'vendor_id'        => $validatedData['is_vendor'] ? $validatedData['vendor_id'] : null,
            'route_id'         => $validatedData['is_vendor'] ? $validatedData['route_id'] : null,
            'pickup_location'  => !$validatedData['is_vendor'] ? $validatedData['pickup_location'] : null,
            'dropoff_location' => !$validatedData['is_vendor'] ? $validatedData['dropoff_location'] : null,
            'vehicle_type'     => !$validatedData['is_vendor'] ? $validatedData['vehicle_type'] : null,
            'inclusion'        => !$validatedData['is_vendor'] ? $validatedData['inclusion'] : null,
        ]);
    
        // Create Pricing Availability
        TransferPricingAvailability::create([
            'transfer_id'          => $transfer->id,
            'is_vendor'            => $validatedData['is_vendor'],
            'pricing_tier_id'      => $validatedData['is_vendor'] ? $validatedData['pricing_tier_id'] : null,
            'availability_id'      => $validatedData['is_vendor'] ? $validatedData['availability_id'] : null,
            'base_price'           => !$validatedData['is_vendor'] ? $validatedData['base_price'] : null,
            'currency'             => !$validatedData['is_vendor'] ? $validatedData['currency'] : null,
            'price_type'           => !$validatedData['is_vendor'] ? $validatedData['price_type'] : null,
            'extra_luggage_charge' => !$validatedData['is_vendor'] ? $validatedData['extra_luggage_charge'] : null,
            'waiting_charge'       => !$validatedData['is_vendor'] ? $validatedData['waiting_charge'] : null,
        ]);
    
        // Create Schedule
        TransferSchedule::create([
            'transfer_id'       => $transfer->id,
            'is_vendor'         => $validatedData['is_vendor'],
            'availability_type' => $validatedData['availability_type'] ?? 'null',
            // 'availability_type' => !empty($validatedData['availability_type']) ? implode(',', $validatedData['availability_type']) : null,
            'available_days'    => !empty($validatedData['available_days']) ? implode(',', $validatedData['available_days']) : null,
            'time_slots'        => !empty($validatedData['time_slots']) ? json_encode($validatedData['time_slots']) : null,
            'blackout_dates'    => !empty($validatedData['blackout_dates']) ? json_encode($validatedData['blackout_dates']) : null,
            'minimum_lead_time' => $validatedData['minimum_lead_time'] ?? null,
            'maximum_passengers'=> $validatedData['maximum_passengers'] ?? null,
        ]);
    
        // === Media Gallery ===
        if (!empty($validatedData['media_gallery'])) {
            foreach ($validatedData['media_gallery'] as $media) {
                TransferMediaGallery::create([
                    'transfer_id' => $transfer->id,
                    'media_id'    => $media['media_id'],
                ]);
            }
        }
    
        // Create SEO
        if (!empty($validatedData['seo'])) {
            TransferSeo::create([
                'transfer_id' => $transfer->id,
                'meta_title' => $validatedData['seo']['meta_title'] ?? '',
                'meta_description' => $validatedData['seo']['meta_description'] ?? '',
                'keywords' => $validatedData['seo']['keywords'] ?? '',
                'og_image_url' => $validatedData['seo']['og_image_url'] ?? null,
                'canonical_url' => $validatedData['seo']['canonical_url'] ?? null,
                'schema_type' => $validatedData['seo']['schema_type'] ?? null,
                // 'schema_data' => $validatedData['seo']['schema_data'] ?? null,
                'schema_data' => is_array($validatedData['seo']['schema_data'] ?? null)
                    ? json_encode($validatedData['seo']['schema_data'])
                    : ($validatedData['seo']['schema_data'] ?? null),
            ]);
        }
    
        return response()->json([
            'message' => 'Transfer created successfully',
            'transfer' => $transfer,
        ]);
    }       

    /**
     * Display the specified transfers.
     */
    public function show(string $id)
    {
        $transfer = Transfer::with(['vendorRoutes', 'pricingAvailability', 'mediaGallery.media', 'seo'])->find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }
        return response()->json($transfer);
    }

    /**
     * Update the specified transfers in storage.
     */
    public function update(Request $request, $id)
    {
        $transfer = Transfer::findOrFail($id);
    
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'transfer_type' => 'sometimes|string',
            'vendor_id' => 'sometimes|integer',
            'route_id' => 'sometimes|integer',
            'pricing_tier_id' => 'sometimes|integer',
            'availability_id' => 'sometimes|integer',
            'media' => 'nullable|array',
            'media.*.media_type' => 'required_with:media|string',
            'media.*.media_url' => 'required_with:media|url',
            'seo' => 'nullable|array',
            'seo.meta_title' => 'nullable|string|max:255',
            'seo.meta_description' => 'nullable|string',
            'seo.keywords' => 'nullable|string',
            'seo.og_image_url' => 'nullable|string',
            'seo.canonical_url' => 'nullable|string',
            'seo.schema_type' => 'nullable|string',
            'seo.schema_data' => 'nullable|json',
        ]);
    
        $transfer->fill($request->only(['name', 'description', 'transfer_type']));
        $transfer->save();
    
        if ($request->hasAny(['vendor_id', 'route_id'])) {
            TransferVendorRoute::updateOrCreate(
                ['transfer_id' => $transfer->id],
                [
                    'vendor_id' => $validatedData['vendor_id'] ?? null,
                    'route_id' => $validatedData['route_id'] ?? null,
                ]
            );
        }
    
        if ($request->hasAny(['pricing_tier_id', 'availability_id'])) {
            TransferPricingAvailability::updateOrCreate(
                ['transfer_id' => $transfer->id],
                [
                    'pricing_tier_id' => $validatedData['pricing_tier_id'] ?? null,
                    'availability_id' => $validatedData['availability_id'] ?? null,
                ]
            );
        }
    
        if ($request->has('media')) {
            TransferMedia::where('transfer_id', $transfer->id)->delete();
            foreach ($validatedData['media'] as $media) {
                TransferMedia::create([
                    'transfer_id' => $transfer->id,
                    'media_type' => $media['media_type'],
                    'media_url' => $media['media_url'],
                ]);
            }
        }
    
        if ($request->has('seo')) {
            TransferSeo::updateOrCreate(
                ['transfer_id' => $transfer->id],
                $validatedData['seo']
            );
        }
    
        return response()->json(['message' => 'Transfer updated successfully', 'transfer' => $transfer->fresh()]);
    }    

    /**
     * Create / Update the transfers in storage.
     */
    // public function save(Request $request, $id = null)
    // {
    //     $validatedData = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'transfer_type' => 'required|string',
    //         'vendor_id' => 'required|integer',
    //         'route_id' => 'required|integer',
    //         'pricing_tier_id' => 'required|integer',
    //         'availability_id' => 'required|integer',
    //         'media' => 'array',
    //         'media.*.media_type' => 'required|string',
    //         'media.*.media_url' => 'required|url',
    //         'seo' => 'array',
    //         'seo.meta_title' => 'nullable|string|max:255',
    //         'seo.meta_description' => 'nullable|string',
    //         'seo.keywords' => 'nullable|string',
    //         'seo.og_image_url' => 'nullable|url',
    //         'seo.canonical_url' => 'nullable|url',
    //         'seo.schema_type' => 'nullable|string',
    //         'seo.schema_data' => 'nullable|json',
    //     ]);

    //     // Create or update Transfer
    //     $transfer = Transfer::updateOrCreate(
    //         ['id' => $id],
    //         [
    //             'name' => $validatedData['name'],
    //             'description' => $validatedData['description'] ?? null,
    //             'transfer_type' => $validatedData['transfer_type'],
    //         ]
    //     );

    //     // Update or Create Vendor & Route
    //     TransferVendorRoute::updateOrCreate(
    //         ['transfer_id' => $transfer->id],
    //         [
    //             'vendor_id' => $validatedData['vendor_id'],
    //             'route_id' => $validatedData['route_id'],
    //         ]
    //     );

    //     // Update or Create Pricing & Availability
    //     TransferPricingAvailability::updateOrCreate(
    //         ['transfer_id' => $transfer->id],
    //         [
    //             'pricing_tier_id' => $validatedData['pricing_tier_id'],
    //             'availability_id' => $validatedData['availability_id'],
    //         ]
    //     );

    //     // Update or Create Media
    //     if (isset($validatedData['media'])) {
    //         TransferMedia::where('transfer_id', $transfer->id)->delete();
    //         foreach ($validatedData['media'] as $media) {
    //             TransferMedia::create([
    //                 'transfer_id' => $transfer->id,
    //                 'media_type' => $media['media_type'],
    //                 'media_url' => $media['media_url'],
    //             ]);
    //         }
    //     }

    //     // Update or Create SEO
    //     if (isset($validatedData['seo'])) {
    //         TransferSeo::updateOrCreate(
    //             ['transfer_id' => $transfer->id],
    //             $validatedData['seo']
    //         );
    //     }

    //     return response()->json(['message' => 'Transfer saved successfully', 'transfer' => $transfer]);
    // }

    /**
     * Remove the specified transfers from storage.
     */
    public function destroy(string $id)
    {
        $transfer = Transfer::find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }

        $transfer->delete();
        return response()->json(['message' => 'Transfer deleted successfully']);
    }
}
