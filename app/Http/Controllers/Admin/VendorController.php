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
        //
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
    public function update(Request $request, string $id)
    {
        //
    }


    /**
     * Create & Update the specified vendors in storage.
     */
    public function save(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();

            // Check if updating or creating
            $vendor = isset($data['id']) 
                ? Vendor::findOrFail($data['id']) 
                : new Vendor();

            $vendor->fill($data);
            $vendor->save();

            $vendorId = $vendor->id;

            // Sync Routes
            if (isset($data['routes'])) {
                foreach ($data['routes'] as $routeData) {
                    $route = isset($routeData['id']) 
                        ? VendorRoute::where('vendor_id', $vendorId)->find($routeData['id']) 
                        : new VendorRoute();

                    $route->fill(array_merge($routeData, ['vendor_id' => $vendorId]));
                    $route->save();
                }
            }

            // Sync Pricing Tiers
            if (isset($data['pricing_tiers'])) {
                foreach ($data['pricing_tiers'] as $tierData) {
                    $tier = isset($tierData['id']) 
                        ? VendorPricingTier::where('vendor_id', $vendorId)->find($tierData['id']) 
                        : new VendorPricingTier();

                    $tier->fill(array_merge($tierData, ['vendor_id' => $vendorId]));
                    $tier->save();
                }
            }

            // Sync Vehicles
            if (isset($data['vehicles'])) {
                foreach ($data['vehicles'] as $vehicleData) {
                    $vehicle = isset($vehicleData['id']) 
                        ? VendorVehicle::where('vendor_id', $vendorId)->find($vehicleData['id']) 
                        : new VendorVehicle();

                    $vehicle->fill(array_merge($vehicleData, ['vendor_id' => $vendorId]));
                    $vehicle->save();

                    // Sync Availability Slots
                    if (isset($vehicleData['availability_time_slots'])) {
                        foreach ($vehicleData['availability_time_slots'] as $slotData) {
                            $slot = isset($slotData['id']) 
                                ? VendorAvailabilityTimeSlot::where('vendor_id', $vendorId)->where('vehicle_id', $vehicle->id)->find($slotData['id']) 
                                : new VendorAvailabilityTimeSlot();

                            $slot->fill(array_merge($slotData, [
                                'vendor_id' => $vendorId,
                                'vehicle_id' => $vehicle->id
                            ]));
                            $slot->save();
                        }
                    }

                    // Sync Drivers
                    if (isset($vehicleData['drivers'])) {
                        foreach ($vehicleData['drivers'] as $driverData) {
                            $driver = isset($driverData['id']) 
                                ? VendorDriver::where('vendor_id', $vendorId)->find($driverData['id']) 
                                : new VendorDriver();

                            $driver->fill(array_merge($driverData, [
                                'vendor_id' => $vendorId,
                                'assigned_vehicle_id' => $vehicle->id,
                            ]));
                            $driver->save();

                            // Sync Driver Schedules
                            if (isset($driverData['schedules'])) {
                                foreach ($driverData['schedules'] as $scheduleData) {
                                    $schedule = isset($scheduleData['id']) 
                                        ? VendorDriverSchedule::where('driver_id', $driver->id)->where('vehicle_id', $vehicle->id)->find($scheduleData['id']) 
                                        : new VendorDriverSchedule();

                                    $schedule->fill(array_merge($scheduleData, [
                                        'driver_id' => $driver->id,
                                        'vehicle_id' => $vehicle->id,
                                    ]));
                                    $schedule->save();
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => isset($data['id']) ? 'Vendor updated successfully.' : 'Vendor created successfully.',
                'vendor_id' => $vendorId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
