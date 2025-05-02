<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transfer;
use App\Models\TransferVendorRoute;
use App\Models\TransferPricingAvailability;
use App\Models\TransferMedia;
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
    
        // Filter params
        $vehicleType = $request->get('vehicle_type');
        $capacity = $request->get('capacity');
        $minPrice = $request->get('min_price', 0);
        $maxPrice = $request->get('max_price');
        $availabilityDate = $request->get('availability_date');
    
        $query = Transfer::query()
            ->select('transfers.*')
            ->join('transfer_vendor_routes', 'transfers.id', '=', 'transfer_vendor_routes.transfer_id')
            ->join('vendor_vehicles', 'transfer_vendor_routes.vendor_id', '=', 'vendor_vehicles.vendor_id')
            ->join('transfer_pricing_availabilities', 'transfers.id', '=', 'transfer_pricing_availabilities.transfer_id')
            ->join('vendor_pricing_tiers', 'transfer_pricing_availabilities.pricing_tier_id', '=', 'vendor_pricing_tiers.id')
            ->with([
                'vendorRoutes.route',
                'pricingAvailability.pricingTier',
                'pricingAvailability.availability',
                'media',
                'seo',
            ])
            ->when($vehicleType, fn($q) => $q->where('vendor_vehicles.vehicle_type', $vehicleType))
            ->when($capacity, fn($q) => $q->where('vendor_vehicles.capacity', '>=', $capacity))
            ->when($minPrice !== null && $maxPrice !== null, fn($q) =>
                $q->whereBetween('vendor_pricing_tiers.base_price', [$minPrice, $maxPrice])
            )
            ->when($availabilityDate, function ($q) use ($availabilityDate) {
                $q->join('vendor_availability_time_slots', 'transfer_pricing_availabilities.availability_id', '=', 'vendor_availability_time_slots.id')
                  ->whereDate('vendor_availability_time_slots.date', $availabilityDate);
            });
    
        // Sorting logic
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('vendor_pricing_tiers.base_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('vendor_pricing_tiers.base_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('transfers.name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('transfers.name', 'desc');
                break;
            case 'id_asc':
            default:
                $query->orderBy('transfers.id', 'asc');
                break;
            case 'id_desc':
                $query->orderBy('transfers.id', 'desc');
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
        ]);
    }       

    /**
     * Store a newly created transfers in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'transfer_type' => 'required|string',
            'vendor_id' => 'required|integer',
            'route_id' => 'required|integer',
            'pricing_tier_id' => 'required|integer',
            'availability_id' => 'required|integer',
            'media' => 'array',
            'media.*.media_type' => 'required|string',
            'media.*.media_url' => 'required|url',
            'seo' => 'array',
            'seo.meta_title' => 'nullable|string|max:255',
            'seo.meta_description' => 'nullable|string',
            'seo.keywords' => 'nullable|string',
            'seo.og_image_url' => 'nullable|url',
            'seo.canonical_url' => 'nullable|url',
            'seo.schema_type' => 'nullable|string',
            'seo.schema_data' => 'nullable|json',
        ]);
    
        $transfer = Transfer::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'transfer_type' => $validatedData['transfer_type'],
        ]);
    
        TransferVendorRoute::create([
            'transfer_id' => $transfer->id,
            'vendor_id' => $validatedData['vendor_id'],
            'route_id' => $validatedData['route_id'],
        ]);
    
        TransferPricingAvailability::create([
            'transfer_id' => $transfer->id,
            'pricing_tier_id' => $validatedData['pricing_tier_id'],
            'availability_id' => $validatedData['availability_id'],
        ]);
    
        if (!empty($validatedData['media'])) {
            foreach ($validatedData['media'] as $media) {
                TransferMedia::create([
                    'transfer_id' => $transfer->id,
                    'media_type' => $media['media_type'],
                    'media_id' => $media['media_id'],
                ]);
            }
        }
    
        if (!empty($validatedData['seo'])) {
            TransferSeo::create([
                'transfer_id' => $transfer->id,
                ...$validatedData['seo']
            ]);
        }
    
        return response()->json(['message' => 'Transfer created successfully', 'transfer' => $transfer]);
    }    

    /**
     * Display the specified transfers.
     */
    public function show(string $id)
    {
        $transfer = Transfer::with(['vendorRoutes', 'pricingAvailability', 'media', 'seo'])->find($id);
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
            'seo.og_image_url' => 'nullable|url',
            'seo.canonical_url' => 'nullable|url',
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
