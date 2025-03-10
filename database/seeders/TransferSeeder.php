<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Vendor;
use App\Models\VendorRoute;
use App\Models\VendorPricingTier;
use App\Models\VendorAvailabilityTimeSlot;
use App\Models\Transfer;
use App\Models\TransferPricingAvailability;
use App\Models\TransferVendorRoute;

class TransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ✅ Get 4 random vendors
        $vendors = Vendor::inRandomOrder()->limit(4)->get();

        foreach ($vendors as $vendor) {
            // ✅ Get a random route for this vendor
            $route = VendorRoute::where('vendor_id', $vendor->id)->inRandomOrder()->first();

            // ✅ Get a random pricing tier for this vendor
            $pricingTier = VendorPricingTier::where('vendor_id', $vendor->id)->inRandomOrder()->first();

            // ✅ Get a random availability for this vendor
            $availability = VendorAvailabilityTimeSlot::where('vendor_id', $vendor->id)->inRandomOrder()->first();

            if (!$route || !$pricingTier || !$availability) {
                continue; // Skip if any of them is missing
            }

            // ✅ Insert Transfer
            $transfer = Transfer::create([
                'name' => 'Transfer ' . $vendor->id,
                'description' => 'Description for Transfer ' . $vendor->id,
                'transfer_type' => 'One-way',
                'vendor_id' => $vendor->id,
                'route_id' => $route->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // ✅ Insert Transfer Pricing & Availability
            TransferPricingAvailability::create([
                'transfer_id' => $transfer->id,
                'vendor_pricing_tier_id' => $pricingTier->id,
                'vendor_availability_id' => $availability->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // ✅ Insert Transfer Vendor Route
            TransferVendorRoute::create([
                'transfer_id' => $transfer->id,
                'vendor_id' => $vendor->id,
                'route_id' => $route->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
