<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\VendorAvailabilityTimeSlot;
use App\Models\VendorDriver;
use App\Models\VendorDriverSchedule;
use App\Models\VendorPricingTier;
use App\Models\VendorRoute;
use App\Models\VendorVehicle;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    /**
     * Display a listing of the vendors.
    */

    public function index(Request $request)
    {
         $perPage = 3;
         $page = $request->get('page', 1);
         $sortBy = $request->get('sort_by', 'id_asc');
         $minPrice = $request->get('min_price', 0);
         $maxPrice = $request->get('max_price');
     
         $query = Vendor::query()
             ->with([
                 'routes',
                 'pricingTiers',
                 'availabilityTimeSlots',
                 'vehicles',
                 'drivers.schedules'
             ])
             ->addSelect([
                 'min_price' => DB::table('vendor_pricing_tiers')
                     ->selectRaw('MIN(base_price)')
                     ->whereColumn('vendor_id', 'vendors.id')
             ]);

        $query->whereHas('pricingTiers', function ($q) use ($minPrice, $maxPrice) {
            if ($minPrice !== null && $maxPrice !== null) {
                $q->whereBetween('base_price', [$minPrice, $maxPrice]);
            } elseif ($minPrice !== null) {
                $q->where('base_price', '>=', $minPrice);
            } elseif ($maxPrice !== null) {
                $q->where('base_price', '<=', $maxPrice);
            }
        });
     
         // Sorting logic
         switch ($sortBy) {
             case 'price_asc':
                 $query->orderBy('min_price', 'asc');
                 break;
             case 'price_desc':
                 $query->orderBy('min_price', 'desc');
                 break;
             case 'name_asc':
                 $query->orderBy('vendors.name', 'asc');
                 break;
             case 'name_desc':
                 $query->orderBy('vendors.name', 'desc');
                 break;
             case 'id_asc':
                default:
                $query->orderBy('vendors.id', 'asc');
                break;
             case 'id_desc':
                $query->orderBy('vendors.id', 'desc');
                break;
         }
     
         // Manual pagination
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

    public function store(Request $request, $requestType)
    {
        $type = str_replace('-', '_', $requestType);
        switch ($type) {
            case 'vendor':
                return $this->storeVendor($request);
            case 'route':
                return $this->storeRoute($request);
            case 'pricing_tier':
                return $this->storePricingTier($request);
            case 'vehicle':
                return $this->storeVehicle($request);
            case 'driver':
                return $this->storeDriver($request);
            case 'schedule':
                return $this->storeSchedule($request);
            case 'availability_time_slot':
                return $this->storeAvailabilityTimeSlot($request);
            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }
    }

    // === Store Methods ===
    // Vendor Base table store
    private function storeVendor($request)
    {
        $vendor = Vendor::create([
            'name' => $request->name,
            'description' => $request->description,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'status' => $request->status ?? 'Active',
        ]);

        return response()->json([
            'message' => 'Vendor created successfully',
            'data' => $vendor
        ], 201); // 201 = Created
    }

    // Pricing Tier store
    private function storePricingTier($request)
    {
        $vendorPricingTier = VendorPricingTier::create([
            'vendor_id' => $request->vendor_id,
            'name' => $request->name,
            'description' => $request->description,
            'base_price' => $request->base_price,
            'price_per_km' => $request->price_per_km,
            'min_distance' => $request->min_distance,
            'waiting_charge' => $request->waiting_charge,
            'night_charge_multiplier' => $request->night_charge_multiplier,
            'peak_hour_multiplier' => $request->peak_hour_multiplier,
            'status' => $request->status ?? 'Active',
        ]);

        return response()->json([
            'message' => 'Vendor Pricing Tier created successfully',
            'data' => $vendorPricingTier
        ], 201); // 201 = Created
    }

    // Route store
    private function storeRoute($request)
    {
        $vendorRoute = VendorRoute::create([
            'vendor_id' => $request->vendor_id,
            'name' => $request->name,
            'description' => $request->description ?? null,
            'start_point' => $request->start_point,
            'end_point' => $request->end_point,
            'base_price' => $request->base_price,
            'price_per_km' => $request->price_per_km,
            'status' => $request->status ?? 'Active',
        ]);

        return response()->json([
            'message' => 'Vendor Route created successfully',
            'data' => $vendorRoute
        ], 201); // 201 = Created
    }

    // Vehicle store
    private function storeVehicle($request)
    {
        $vendorVehicle = VendorVehicle::create([
            'vendor_id' => $request->vendor_id,
            'vehicle_type' => $request->vehicle_type,
            'capacity' => $request->capacity,
            'make' => $request->make,
            'model' => $request->model,
            'year' => $request->year,
            'license_plate' => $request->license_plate,
            'features' => $request->features ?? null,
            'status' => $request->status ?? 'Active',
            'last_maintenance' => $request->last_maintenance ?? now(),
            'next_maintenance' => $request->next_maintenance ?? now()->addMonth(),
        ]);

        return response()->json([
            'message' => 'Vendor Vehicle created successfully',
            'data' => $vendorVehicle
        ], 201);
    }

    // Driver store
    private function storeDriver($request)
    {
        $vendorDriver = VendorDriver::create([
            'vendor_id' => $request->vendor_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'license_number' => $request->license_number,
            'license_expiry' => $request->license_expiry,
            'status' => $request->status ?? 'Active',
            'assigned_vehicle_id' => $request->assigned_vehicle_id,
            'languages' => $request->languages,
        ]);

        return response()->json([
            'message' => 'Vendor Driver created successfully',
            'data' => $vendorDriver
        ], 201); // 201 = Created
    }

    // Schedule store
    private function storeSchedule($request)
    {
        $vendorDriverSchedule = VendorDriverSchedule::create([
            'driver_id' => $request->driver_id,
            'vehicle_id' => $request->vehicle_id,
            'date' => $request->date,
            'shift' => $request->shift,
            'time' => $request->time,
        ]);

        return response()->json([
            'message' => 'Vendor Driver Schedule created successfully',
            'data' => $vendorDriverSchedule
        ], 201); // 201 = Created
    }

    // Availability Time Slot store
    private function storeAvailabilityTimeSlot($request)
    {
        $vendorAvailabilityTimeSlot = VendorAvailabilityTimeSlot::create([
            'vendor_id' => $request->vendor_id,
            'vehicle_id' => $request->vehicle_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'max_bookings' => $request->max_bookings,
            'price_multiplier' => $request->price_multiplier,
        ]);

        return response()->json([
            'message' => 'Vendor Availability Time Slot created successfully',
            'data' => $vendorAvailabilityTimeSlot
        ], 201); // 201 = Created
    }

    // === Update function ===
    public function update(Request $request, $requestType, $id)
    {
        $type = str_replace('-', '_', $requestType);
        switch ($type) {
            case 'vendor':
                return $this->updateVendor($request, $id);
            case 'route':
                return $this->updateRoute($request, $id);
            case 'pricing_tier':
                return $this->updatePricingTiers($request);
            case 'vehicle':
                return $this->updateVehicle($request, $id);
            case 'driver':
                return $this->updateDriver($request, $id);
            case 'schedule':
                return $this->updateSchedule($request, $id);
            case 'availability_time_slot':
                return $this->updateAvailabilityTimeSlot($request);
            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }
    }

    // === Update Methods ===
    // Vendor Base table update
    private function updateVendor($request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $vendor->update([
            'name' => $request->name ?? $vendor->name,
            'description' => $request->description ?? $vendor->description,
            'email' => $request->email ?? $vendor->email,
            'phone' => $request->phone ?? $vendor->phone,
            'address' => $request->address ?? $vendor->address,
            'status' => $request->status ?? $vendor->status,
        ]);

        return response()->json([
            'message' => 'Vendor updated successfully',
            'data' => $vendor
        ]);
    }

    private function updateRoute(Request $request, $id)
    {
        $route = VendorRoute::findOrFail($id);

        $route->update([
            'vendor_id' => $request->vendor_id ?? $route->vendor_id,
            'name' => $request->name ?? $route->name,
            'description' => $request->description ?? $route->description,
            'start_point' => $request->start_point ?? $route->start_point,
            'end_point' => $request->end_point ?? $route->end_point,
            'base_price' => $request->base_price ?? $route->base_price,
            'price_per_km' => $request->price_per_km ?? $route->price_per_km,
            'status' => $request->status ?? $route->status,
        ]);

        return response()->json([
            'message' => 'Vendor Route updated successfully',
            'data' => $route
        ]);
    }

    private function updatePricingTiers(Request $request, $id)
    {
        $tier = VendorPricingTier::findOrFail($id);
    
        $tier->update([
            'vendor_id' => $request->vendor_id ?? $tier->vendor_id,
            'name' => $request->name ?? $tier->name,
            'description' => $request->description ?? $tier->description,
            'base_price' => $request->base_price ?? $tier->base_price,
            'price_per_km' => $request->price_per_km ?? $tier->price_per_km,
            'min_distance' => $request->min_distance ?? $tier->min_distance,
            'waiting_charge' => $request->waiting_charge ?? $tier->waiting_charge,
            'night_charge_multiplier' => $request->night_charge_multiplier ?? $tier->night_charge_multiplier,
            'peak_hour_multiplier' => $request->peak_hour_multiplier ?? $tier->peak_hour_multiplier,
            'status' => $request->status ?? $tier->status,
        ]);
    
        return response()->json([
            'message' => 'Vendor Pricing Tier updated successfully',
            'data' => $tier
        ]);
    }
    
    private function updateVehicle(Request $request, $id)
    {
        $vehicle = VendorVehicle::findOrFail($id);
    
        $vehicle->update([
            'vendor_id' => $request->vendor_id ?? $vehicle->vendor_id,
            'vehicle_type' => $request->vehicle_type ?? $vehicle->vehicle_type,
            'capacity' => $request->capacity ?? $vehicle->capacity,
            'make' => $request->make ?? $vehicle->make,
            'model' => $request->model ?? $vehicle->model,
            'year' => $request->year ?? $vehicle->year,
            'license_plate' => $request->license_plate ?? $vehicle->license_plate,
            'features' => $request->features ?? $vehicle->features,
            'status' => $request->status ?? $vehicle->status,
            'last_maintenance' => $request->last_maintenance ?? $vehicle->last_maintenance,
            'next_maintenance' => $request->next_maintenance ?? $vehicle->next_maintenance,
        ]);
    
        return response()->json([
            'message' => 'Vendor Vehicle updated successfully',
            'data' => $vehicle
        ]);
    }

    private function updateDriver(Request $request, $id)
    {
        $driver = VendorDriver::findOrFail($id);
    
        $driver->update([
            'vendor_id' => $request->vendor_id ?? $driver->vendor_id,
            'first_name' => $request->first_name ?? $driver->first_name,
            'last_name' => $request->last_name ?? $driver->last_name,
            'email' => $request->email ?? $driver->email,
            'phone' => $request->phone ?? $driver->phone,
            'license_number' => $request->license_number ?? $driver->license_number,
            'license_expiry' => $request->license_expiry ?? $driver->license_expiry,
            'status' => $request->status ?? $driver->status,
            'assigned_vehicle_id' => $request->assigned_vehicle_id ?? $driver->assigned_vehicle_id,
            'languages' => $request->languages ?? $driver->languages,
        ]);
    
        return response()->json([
            'message' => 'Vendor Driver updated successfully',
            'data' => $driver
        ]);
    }

    private function updateSchedule(Request $request, $id)
    {
        $schedule = VendorDriverSchedule::findOrFail($id);
    
        $schedule->update([
            'driver_id' => $request->driver_id ?? $schedule->driver_id,
            'vehicle_id' => $request->vehicle_id ?? $schedule->vehicle_id,
            'date' => $request->date ?? $schedule->date,
            'shift' => $request->shift ?? $schedule->shift,
            'time' => $request->time ?? $schedule->time,
        ]);
    
        return response()->json([
            'message' => 'Vendor Driver Schedule updated successfully',
            'data' => $schedule
        ]);
    }

    private function updateAvailabilityTimeSlot(Request $request, $id)
    {
        $slot = VendorAvailabilityTimeSlot::findOrFail($id);
    
        $slot->update([
            'vendor_id' => $request->vendor_id ?? $slot->vendor_id,
            'vehicle_id' => $request->vehicle_id ?? $slot->vehicle_id,
            'date' => $request->date ?? $slot->date,
            'start_time' => $request->start_time ?? $slot->start_time,
            'end_time' => $request->end_time ?? $slot->end_time,
            'max_bookings' => $request->max_bookings ?? $slot->max_bookings,
            'price_multiplier' => $request->price_multiplier ?? $slot->price_multiplier,
        ]);
    
        return response()->json([
            'message' => 'Vendor Availability Time Slot updated successfully',
            'data' => $slot
        ]);
    }
    
    /**
     * Display the specified vendors.
     */
    public function show(string $id)
    {
        $vendor = Vendor::with(['routes', 'pricingTiers', 'vehicles', 'drivers', 'availabilityTimeSlots'])->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        return response()->json($vendor);
    }

    /**
     * Remove the specified vendors from storage.
     */
    public function destroy(string $id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted successfully']);
    }
}
