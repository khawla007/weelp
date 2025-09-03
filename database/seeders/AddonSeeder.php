<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Addon;

class AddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addons = [
            [
                'name' => 'Extra Luggage',
                'type' => 'service',
                'description' => 'Additional luggage allowance',
                'price' => 20.00,
                'sale_price' => 15.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'VIP Seat',
                'type' => 'service',
                'description' => 'Upgrade to VIP seating',
                'price' => 50.00,
                'sale_price' => 40.00,
                'price_calculation' => 'per_package',
                'active_status' => true,
            ],
            [
                'name' => 'Meal Package',
                'type' => 'food',
                'description' => 'Includes breakfast and lunch',
                'price' => 30.00,
                'sale_price' => null,
                'price_calculation' => 'per_itinerary',
                'active_status' => true,
            ],
            [
                'name' => 'Priority Boarding',
                'type' => 'service',
                'description' => 'Board before other passengers',
                'price' => 10.00,
                'sale_price' => null,
                'price_calculation' => 'per_itinerary',
                'active_status' => true,
            ],
            [
                'name' => 'Insurance',
                'type' => 'service',
                'description' => 'Travel insurance coverage',
                'price' => 25.00,
                'sale_price' => 20.00,
                'price_calculation' => 'per_itinerary',
                'active_status' => true,
            ],
            [
                'name' => 'WiFi Access',
                'type' => 'service',
                'description' => 'Onboard internet access',
                'price' => 5.00,
                'sale_price' => null,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Photography Package',
                'type' => 'service',
                'description' => 'Professional trip photos',
                'price' => 40.00,
                'sale_price' => 30.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Child Seat',
                'type' => 'equipment',
                'description' => 'Child safety seat',
                'price' => 10.00,
                'sale_price' => 8.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Driver Guide',
                'type' => 'service',
                'description' => 'Personal driver and guide',
                'price' => 100.00,
                'sale_price' => 80.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Souvenir Package',
                'type' => 'product',
                'description' => 'Gift items for travelers',
                'price' => 15.00,
                'sale_price' => 12.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Extended Warranty',
                'type' => 'service',
                'description' => 'Warranty extension for products',
                'price' => 12.00,
                'sale_price' => null,
                'price_calculation' => 'per_activity',
                'active_status' => false,
            ],
            [
                'name' => 'Spa Access',
                'type' => 'service',
                'description' => 'Full day spa access',
                'price' => 70.00,
                'sale_price' => 60.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Cocktail Package',
                'type' => 'food',
                'description' => 'Unlimited cocktails for 2 hours',
                'price' => 25.00,
                'sale_price' => 20.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
            [
                'name' => 'Photography Prints',
                'type' => 'product',
                'description' => 'Printed copies of trip photos',
                'price' => 18.00,
                'sale_price' => 15.00,
                'price_calculation' => 'per_activity',
                'active_status' => false,
            ],
            [
                'name' => 'Adventure Kit',
                'type' => 'equipment',
                'description' => 'Includes trekking sticks and gear',
                'price' => 35.00,
                'sale_price' => 30.00,
                'price_calculation' => 'per_activity',
                'active_status' => true,
            ],
        ];

        foreach ($addons as $addon) {
            Addon::create($addon);
        }
    }
}
