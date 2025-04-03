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
    public function index()
    {
        $transfers = Transfer::with(['vendorRoutes', 'pricingAvailability', 'media', 'seo'])->get();
        return response()->json($transfers);
    }

    /**
     * Store a newly created transfers in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified transfers.
     */
    public function show(string $id)
    {
        $transfer = Transfer::with(['vendorRoute', 'pricingAvailability', 'media', 'seo'])->find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }
        return response()->json($transfer);
    }

    /**
     * Update the specified transfers in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Create / Update the transfers in storage.
     */
    public function save(Request $request, $id = null)
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

        // Create or update Transfer
        $transfer = Transfer::updateOrCreate(
            ['id' => $id],
            [
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'transfer_type' => $validatedData['transfer_type'],
            ]
        );

        // Update or Create Vendor & Route
        TransferVendorRoute::updateOrCreate(
            ['transfer_id' => $transfer->id],
            [
                'vendor_id' => $validatedData['vendor_id'],
                'route_id' => $validatedData['route_id'],
            ]
        );

        // Update or Create Pricing & Availability
        TransferPricingAvailability::updateOrCreate(
            ['transfer_id' => $transfer->id],
            [
                'pricing_tier_id' => $validatedData['pricing_tier_id'],
                'availability_id' => $validatedData['availability_id'],
            ]
        );

        // Update or Create Media
        if (isset($validatedData['media'])) {
            TransferMedia::where('transfer_id', $transfer->id)->delete();
            foreach ($validatedData['media'] as $media) {
                TransferMedia::create([
                    'transfer_id' => $transfer->id,
                    'media_type' => $media['media_type'],
                    'media_url' => $media['media_url'],
                ]);
            }
        }

        // Update or Create SEO
        if (isset($validatedData['seo'])) {
            TransferSeo::updateOrCreate(
                ['transfer_id' => $transfer->id],
                $validatedData['seo']
            );
        }

        return response()->json(['message' => 'Transfer saved successfully', 'transfer' => $transfer]);
    }

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
