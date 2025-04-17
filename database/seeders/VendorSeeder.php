<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VendorSeeder extends Seeder {
    public function run() {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable foreign key checks

        // Truncate Tables
        DB::table('vendors')->truncate();
        DB::table('vendor_routes')->truncate();
        DB::table('vendor_pricing_tiers')->truncate();
        DB::table('vendor_availability_time_slots')->truncate();
        DB::table('vendor_vehicles')->truncate();
        DB::table('vendor_drivers')->truncate();
        DB::table('vendor_driver_schedules')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Enable foreign key checks

        // Insert Vendors
        $vendors = [
            [
                'name' => 'Vendor One',
                'description' => 'Leading transportation provider.',
                'email' => 'vendor1@example.com',
                'phone' => '+123-456-7890',
                'address' => '123 Main St, City, Country',
                'status' => 'Active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Vendor Two',
                'description' => 'Reliable taxi services.',
                'email' => 'vendor2@example.com',
                'phone' => '+987-654-3210',
                'address' => '456 Elm St, City, Country',
                'status' => 'Inactive',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Vendor Three',
                'description' => 'Luxury car rentals.',
                'email' => 'vendor3@example.com',
                'phone' => '+111-222-3333',
                'address' => '789 Pine St, City, Country',
                'status' => 'Pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Vendor Four',
                'description' => 'Affordable rides for everyone.',
                'email' => 'vendor4@example.com',
                'phone' => '+444-555-6666',
                'address' => '101 Oak St, City, Country',
                'status' => 'Active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];
        DB::table('vendors')->insert($vendors);

        // Get Vendor IDs
        $vendorIds = DB::table('vendors')->pluck('id');

        // Insert Routes
        foreach ($vendorIds as $vendorId) {
            DB::table('vendor_routes')->insert([
                [
                    'vendor_id' => $vendorId,
                    'name' => 'Route A',
                    'description' => 'Main city route',
                    'start_point' => 'Downtown',
                    'end_point' => 'Airport',
                    'base_price' => 100.50,
                    'price_per_km' => 10.25,
                    'status' => 'Active',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'vendor_id' => $vendorId,
                    'name' => 'Route B',
                    'description' => 'Highway express route',
                    'start_point' => 'Mall',
                    'end_point' => 'Stadium',
                    'base_price' => 80.00,
                    'price_per_km' => 8.75,
                    'status' => 'Inactive',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ]);
        }

        // Insert Pricing Tiers
        foreach ($vendorIds as $vendorId) {
            DB::table('vendor_pricing_tiers')->insert([
                [
                    'vendor_id' => $vendorId,
                    'name' => 'Standard',
                    'description' => 'Regular pricing for all customers',
                    'base_price' => 50.00,
                    'price_per_km' => 5.00,
                    'min_distance' => 2,
                    'waiting_charge' => 15.00,
                    'night_charge_multiplier' => 1.5,
                    'peak_hour_multiplier' => 2.0,
                    'status' => 'Active',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ]);
        }

        // Insert Vehicles
        foreach ($vendorIds as $vendorId) {
            $vehicleId = DB::table('vendor_vehicles')->insertGetId([
                'vendor_id' => $vendorId,
                'vehicle_type' => 'Sedan',
                'capacity' => 4,
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2022,
                'license_plate' => 'AB-1234',
                'features' => 'Air Conditioning, GPS, Bluetooth',
                'status' => 'Active',
                'last_maintenance' => Carbon::now()->subMonth(),
                'next_maintenance' => Carbon::now()->addMonth(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Insert Drivers with unique email
            $driverId = DB::table('vendor_drivers')->insertGetId([
                'vendor_id' => $vendorId,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe' . $vendorId . '@example.com', // Ensure unique email
                'phone' => '+555-555-55' . $vendorId, // Unique phone
                'license_number' => 'XYZ12345' . $vendorId, // Unique license
                'license_expiry' => Carbon::now()->addYear(),
                'status' => 'Active',
                'assigned_vehicle_id' => $vehicleId,
                'languages' => json_encode(['English', 'Spanish']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('vendor_availability_time_slots')->insert([
                'vendor_id' => $vendorId, // Connect to vendor
                'vehicle_id' => $vehicleId, // Connect to vendor_vehicles
                'date' => Carbon::now()->format('Y-m-d'),
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'max_bookings' => 5,
                'price_multiplier' => 1.5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Insert Driver Schedule
            DB::table('vendor_driver_schedules')->insert([
                'driver_id' => $driverId,
                'vehicle_id' => $vehicleId,
                'date' => Carbon::now(),
                'shift' => 'Morning',
                'time' => Carbon::now()->format('H:i:s'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }


        echo "Vendor Seeder Successfully Executed!\n";
    }
}
