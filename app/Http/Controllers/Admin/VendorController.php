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
     
         // 🔍 Filter by base_price from vendor_pricing_tiers
        //  if ($maxPrice !== null) {
        //      $query->whereHas('pricingTiers', function ($q) use ($minPrice, $maxPrice) {
        //          $q->whereBetween('base_price', [$minPrice, $maxPrice]);
        //      });
        //  }
        $query->whereHas('pricingTiers', function ($q) use ($minPrice, $maxPrice) {
            if ($minPrice !== null && $maxPrice !== null) {
                $q->whereBetween('base_price', [$minPrice, $maxPrice]);
            } elseif ($minPrice !== null) {
                $q->where('base_price', '>=', $minPrice);
            } elseif ($maxPrice !== null) {
                $q->where('base_price', '<=', $maxPrice);
            }
        });
     
         // 📦 Sorting logic
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
     
         // 🧮 Manual pagination
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
     * Store a newly created vendors in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'required|email|unique:vendors,email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'nullable|string',
    
            'routes' => 'nullable|array',
            'pricing_tiers' => 'nullable|array',
            'vehicles' => 'nullable|array',
            'drivers' => 'nullable|array',
            'driver_schedules' => 'nullable|array',
            'availability_time_slots' => 'nullable|array',
        ];
    
        $request->validate($rules);
    
        try {
            DB::beginTransaction();
    
            $vendor = Vendor::create([
                'name' => $request->name,
                'description' => $request->description,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => $request->status ?? 'Active',
            ]);
    
            // === Routes ===
            foreach ($request->routes ?? [] as $route) {
                VendorRoute::create([
                    'vendor_id' => $vendor->id,
                    'name' => $route['name'],
                    'description' => $route['description'] ?? null,
                    'start_point' => $route['start_point'],
                    'end_point' => $route['end_point'],
                    'base_price' => $route['base_price'],
                    'price_per_km' => $route['price_per_km'],
                    'status' => $route['status'] ?? 'Active',
                ]);
            }
    
            // === Pricing Tiers ===
            foreach ($request->pricing_tiers ?? [] as $tier) {
                VendorPricingTier::create([
                    'vendor_id' => $vendor->id,
                    'name' => $tier['name'],
                    'description' => $tier['description'] ?? null,
                    'base_price' => $tier['base_price'],
                    'price_per_km' => $tier['price_per_km'],
                    'min_distance' => $tier['min_distance'],
                    'waiting_charge' => $tier['waiting_charge'],
                    'night_charge_multiplier' => $tier['night_charge_multiplier'],
                    'peak_hour_multiplier' => $tier['peak_hour_multiplier'],
                    'status' => $tier['status'] ?? 'Active',
                ]);
            }
    
            // === Vehicles ===
            foreach ($request->vehicles ?? [] as $vehicle) {
                $vehicleModel = VendorVehicle::create([
                    'vendor_id' => $vendor->id,
                    'vehicle_type' => $vehicle['vehicle_type'],
                    'capacity' => $vehicle['capacity'],
                    'make' => $vehicle['make'],
                    'model' => $vehicle['model'],
                    'year' => $vehicle['year'],
                    'license_plate' => $vehicle['license_plate'],
                    'features' => $vehicle['features'] ?? null,
                    'status' => $vehicle['status'] ?? 'Active',
                    'last_maintenance' => $vehicle['last_maintenance'] ?? now(),
                    'next_maintenance' => $vehicle['next_maintenance'] ?? now()->addMonth(),
                ]);
            }
    
            // === Driver (Global, not vehicle specific) ===
            foreach ($request->drivers ?? [] as $driver) {
                VendorDriver::create([
                    'vendor_id' => $vendor->id,
                    'first_name' => $driver['first_name'], // make sure this ID exists
                    'last_name' => $driver['last_name'],
                    'email' => $driver['email'],
                    'phone' => $driver['phone'],
                    'license_number' => $driver['license_number'],
                    'license_expiry' => $driver['license_expiry'],
                    'status' => $driver['status'],
                    'assigned_vehicle_id' => $driver['assigned_vehicle_id'],
                    'languages' => $driver['languages'],
                ]);
            }

            // === Driver Schedule (Global, not vehicle specific) ===
            foreach ($request->driver_schedules ?? [] as $schedule) {
                VendorDriverSchedule::create([
                    'driver_id' => $schedule['driver_id'], 
                    'vehicle_id' => $schedule['vehicle_id'],
                    'date' => $schedule['date'],
                    'shift' => $schedule['shift'],
                    'time' => $schedule['time'],
                ]);
            }

            // === Availability Time Slots (Global, not vehicle specific) ===
            foreach ($request->availability_time_slots ?? [] as $slot) {
                VendorAvailabilityTimeSlot::create([
                    'vendor_id' => $vendor->id,
                    'vehicle_id' => $slot['vehicle_id'], // make sure this ID exists
                    'date' => $slot['date'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'max_bookings' => $slot['max_bookings'],
                    'price_multiplier' => $slot['price_multiplier'],
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Vendor created successfully',
                'vendor' => $vendor
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
     * Display the specified vendors.
     */
    public function show(string $id)
    {
        $vendor = Vendor::with(['routes', 'pricingTiers', 'vehicles', 'drivers'])->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        return response()->json($vendor);
    }

    /**
     * Update the specified vendors in storage.
     */
    // public function update(Request $request, string $id)
    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
    
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'sometimes|required|email|unique:vendors,email,' . $vendor->id,
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'nullable|string',
    
            'routes' => 'nullable|array',
            'pricing_tiers' => 'nullable|array',
            'vehicles' => 'nullable|array',
            'availability_time_slots' => 'nullable|array',
        ];
    
        $request->validate($rules);
    
        try {
            DB::beginTransaction();
    
            // === Update Vendor fields ===
            $vendor->update($request->only([
                'name', 'description', 'email', 'phone', 'address', 'status'
            ]));
    
            // === Routes Partial Update or Create ===
            foreach ($request->routes ?? [] as $route) {
                if (isset($route['id'])) {
                    VendorRoute::where('id', $route['id'])->update([
                        'name' => $route['name'],
                        'description' => $route['description'] ?? null,
                        'start_point' => $route['start_point'],
                        'end_point' => $route['end_point'],
                        'base_price' => $route['base_price'],
                        'price_per_km' => $route['price_per_km'],
                        'status' => $route['status'] ?? 'Active',
                    ]);
                } else {
                    VendorRoute::create([
                        'vendor_id' => $vendor->id,
                        'name' => $route['name'],
                        'description' => $route['description'] ?? null,
                        'start_point' => $route['start_point'],
                        'end_point' => $route['end_point'],
                        'base_price' => $route['base_price'],
                        'price_per_km' => $route['price_per_km'],
                        'status' => $route['status'] ?? 'Active',
                    ]);
                }
            }
    
            // === Pricing Tiers Partial Update or Create ===
            foreach ($request->pricing_tiers ?? [] as $tier) {
                if (isset($tier['id'])) {
                    VendorPricingTier::where('id', $tier['id'])->update([
                        'name' => $tier['name'],
                        'description' => $tier['description'] ?? null,
                        'base_price' => $tier['base_price'],
                        'price_per_km' => $tier['price_per_km'],
                        'min_distance' => $tier['min_distance'],
                        'waiting_charge' => $tier['waiting_charge'],
                        'night_charge_multiplier' => $tier['night_charge_multiplier'],
                        'peak_hour_multiplier' => $tier['peak_hour_multiplier'],
                        'status' => $tier['status'] ?? 'Active',
                    ]);
                } else {
                    VendorPricingTier::create([
                        'vendor_id' => $vendor->id,
                        'name' => $tier['name'],
                        'description' => $tier['description'] ?? null,
                        'base_price' => $tier['base_price'],
                        'price_per_km' => $tier['price_per_km'],
                        'min_distance' => $tier['min_distance'],
                        'waiting_charge' => $tier['waiting_charge'],
                        'night_charge_multiplier' => $tier['night_charge_multiplier'],
                        'peak_hour_multiplier' => $tier['peak_hour_multiplier'],
                        'status' => $tier['status'] ?? 'Active',
                    ]);
                }
            }
    
            // === Vehicles Partial Update or Create ===
            foreach ($request->vehicles ?? [] as $vehicle) {
                if (isset($vehicle['id'])) {
                    VendorVehicle::where('id', $vehicle['id'])->update([
                        'vehicle_type' => $vehicle['vehicle_type'],
                        'capacity' => $vehicle['capacity'],
                        'make' => $vehicle['make'],
                        'model' => $vehicle['model'],
                        'year' => $vehicle['year'],
                        'license_plate' => $vehicle['license_plate'],
                        'features' => $vehicle['features'] ?? null,
                        'status' => $vehicle['status'] ?? 'Active',
                        'last_maintenance' => $vehicle['last_maintenance'] ?? now(),
                        'next_maintenance' => $vehicle['next_maintenance'] ?? now()->addMonth(),
                    ]);
                } else {
                    VendorVehicle::create([
                        'vendor_id' => $vendor->id,
                        'vehicle_type' => $vehicle['vehicle_type'],
                        'capacity' => $vehicle['capacity'],
                        'make' => $vehicle['make'],
                        'model' => $vehicle['model'],
                        'year' => $vehicle['year'],
                        'license_plate' => $vehicle['license_plate'],
                        'features' => $vehicle['features'] ?? null,
                        'status' => $vehicle['status'] ?? 'Active',
                        'last_maintenance' => $vehicle['last_maintenance'] ?? now(),
                        'next_maintenance' => $vehicle['next_maintenance'] ?? now()->addMonth(),
                    ]);
                }
            }
    
            // === Availability Time Slots Partial Update or Create ===
            foreach ($request->availability_time_slots ?? [] as $slot) {
                if (isset($slot['id'])) {
                    VendorAvailabilityTimeSlot::where('id', $slot['id'])->update([
                        'vehicle_id' => $slot['vehicle_id'],
                        'date' => $slot['date'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'max_bookings' => $slot['max_bookings'],
                        'price_multiplier' => $slot['price_multiplier'],
                    ]);
                } else {
                    VendorAvailabilityTimeSlot::create([
                        'vendor_id' => $vendor->id,
                        'vehicle_id' => $slot['vehicle_id'],
                        'date' => $slot['date'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'max_bookings' => $slot['max_bookings'],
                        'price_multiplier' => $slot['price_multiplier'],
                    ]);
                }
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Vendor updated successfully',
                'vendor' => $vendor
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage(),
            ], 500);
        }
    }    


    /**
     * Create & Update the specified vendors in storage.
     */
    // public function save(Request $request)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $data = $request->all();

    //         // Check if updating or creating
    //         $vendor = isset($data['id']) 
    //             ? Vendor::findOrFail($data['id']) 
    //             : new Vendor();

    //         $vendor->fill($data);
    //         $vendor->save();

    //         $vendorId = $vendor->id;

    //         // Sync Routes
    //         if (isset($data['routes'])) {
    //             foreach ($data['routes'] as $routeData) {
    //                 $route = isset($routeData['id']) 
    //                     ? VendorRoute::where('vendor_id', $vendorId)->find($routeData['id']) 
    //                     : new VendorRoute();

    //                 $route->fill(array_merge($routeData, ['vendor_id' => $vendorId]));
    //                 $route->save();
    //             }
    //         }

    //         // Sync Pricing Tiers
    //         if (isset($data['pricing_tiers'])) {
    //             foreach ($data['pricing_tiers'] as $tierData) {
    //                 $tier = isset($tierData['id']) 
    //                     ? VendorPricingTier::where('vendor_id', $vendorId)->find($tierData['id']) 
    //                     : new VendorPricingTier();

    //                 $tier->fill(array_merge($tierData, ['vendor_id' => $vendorId]));
    //                 $tier->save();
    //             }
    //         }

    //         // Sync Vehicles
    //         if (isset($data['vehicles'])) {
    //             foreach ($data['vehicles'] as $vehicleData) {
    //                 $vehicle = isset($vehicleData['id']) 
    //                     ? VendorVehicle::where('vendor_id', $vendorId)->find($vehicleData['id']) 
    //                     : new VendorVehicle();

    //                 $vehicle->fill(array_merge($vehicleData, ['vendor_id' => $vendorId]));
    //                 $vehicle->save();

    //                 // Sync Availability Slots
    //                 if (isset($vehicleData['availability_time_slots'])) {
    //                     foreach ($vehicleData['availability_time_slots'] as $slotData) {
    //                         $slot = isset($slotData['id']) 
    //                             ? VendorAvailabilityTimeSlot::where('vendor_id', $vendorId)->where('vehicle_id', $vehicle->id)->find($slotData['id']) 
    //                             : new VendorAvailabilityTimeSlot();

    //                         $slot->fill(array_merge($slotData, [
    //                             'vendor_id' => $vendorId,
    //                             'vehicle_id' => $vehicle->id
    //                         ]));
    //                         $slot->save();
    //                     }
    //                 }

    //                 // Sync Drivers
    //                 if (isset($vehicleData['drivers'])) {
    //                     foreach ($vehicleData['drivers'] as $driverData) {
    //                         $driver = isset($driverData['id']) 
    //                             ? VendorDriver::where('vendor_id', $vendorId)->find($driverData['id']) 
    //                             : new VendorDriver();

    //                         $driver->fill(array_merge($driverData, [
    //                             'vendor_id' => $vendorId,
    //                             'assigned_vehicle_id' => $vehicle->id,
    //                         ]));
    //                         $driver->save();

    //                         // Sync Driver Schedules
    //                         if (isset($driverData['schedules'])) {
    //                             foreach ($driverData['schedules'] as $scheduleData) {
    //                                 $schedule = isset($scheduleData['id']) 
    //                                     ? VendorDriverSchedule::where('driver_id', $driver->id)->where('vehicle_id', $vehicle->id)->find($scheduleData['id']) 
    //                                     : new VendorDriverSchedule();

    //                                 $schedule->fill(array_merge($scheduleData, [
    //                                     'driver_id' => $driver->id,
    //                                     'vehicle_id' => $vehicle->id,
    //                                 ]));
    //                                 $schedule->save();
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => isset($data['id']) ? 'Vendor updated successfully.' : 'Vendor created successfully.',
    //             'vendor_id' => $vendorId
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Something went wrong.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


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
