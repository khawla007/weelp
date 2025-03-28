<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityLocation;
use App\Models\Attribute;
use App\Models\ActivityAttribute;
use App\Models\ActivityPricing;
use App\Models\ActivitySeasonalPricing;
use App\Models\ActivityGroupDiscount;
use App\Models\ActivityEarlyBirdDiscount;
use App\Models\ActivityLastMinuteDiscount;
use App\Models\ActivityPromoCode;
use App\Models\ActivityAvailability;

class ActivitySeeder extends Seeder {
    public function run() {
        $activities = [
            [
                'name' => 'Desert Safari Adventure',
                'slug' => 'desert-safari-adventure',
                'description' => 'Experience the thrill of a desert safari with dune bashing and camel rides.',
                'short_description' => 'Desert safari with dune bashing and camel rides.',
                'featured_images' => json_encode(['desert1.jpg', 'desert2.jpg']),
                'featured_activity' => true,
            ],
            [
                'name' => 'Sky Diving Experience',
                'slug' => 'sky-diving-experience',
                'description' => 'Jump from a plane at 13,000 feet and enjoy the ultimate adrenaline rush.',
                'short_description' => 'Sky diving at 13,000 feet.',
                'featured_images' => json_encode(['skydiving1.jpg', 'skydiving2.jpg']),
                'featured_activity' => false,
            ],
            [
                'name' => 'Scuba Diving Tour',
                'slug' => 'scuba-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_images' => json_encode(['scuba1.jpg', 'scuba2.jpg']),
                'featured_activity' => true,
            ],
            [
                'name' => 'Scuba Diving',
                'slug' => 'scuba-diving',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_images' => json_encode(['scuba1.jpg', 'scuba2.jpg']),
                'featured_activity' => true,
            ],
            [
                'name' => 'Nero Diving Tour',
                'slug' => 'nero-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_images' => json_encode(['scuba1.jpg', 'scuba2.jpg']),
                'featured_activity' => true,
            ],
            [
                'name' => 'Deep Diving Tour',
                'slug' => 'deep-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_images' => json_encode(['scuba1.jpg', 'scuba2.jpg']),
                'featured_activity' => true,
            ],
            [
                'name' => 'Sea Diving Tour',
                'slug' => 'sea-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_images' => json_encode(['scuba1.jpg', 'scuba2.jpg']),
                'featured_activity' => true,
            ],
            [
                'name' => 'Lake Diving Tour',
                'slug' => 'lake-diving-tour',
                'description' => 'Explore the underwater world with our professional scuba diving instructors.',
                'short_description' => 'Scuba diving with professional guides.',
                'featured_images' => json_encode(['scuba1.jpg', 'scuba2.jpg']),
                'featured_activity' => true,
            ],
        ];

        foreach ($activities as $activityData) {
            $activity = Activity::create($activityData);

            ActivityCategory::create([
                'activity_id' => $activity->id,
                'category_id' => rand(1, 3) 
            ]);
            ActivityCategory::create([
                'activity_id' => $activity->id,
                'category_id' => rand(2, 4) 
            ]);

            ActivityLocation::create([
                'activity_id' => $activity->id,
                'city_id' => rand(1, 4),
                'location_type' => 'primary',
                'location_label' => 'Main Location',
                'duration' => null
            ]);
        
            ActivityLocation::create([
                'activity_id' => $activity->id,
                'city_id' => rand(1, 4),
                'location_type' => 'additional',
                'location_label' => 'Highlight', // Custom value allowed
                'duration' => rand(5, 20)
            ]);

            // 🏷 Assign Multiple Attributes
            // ActivityAttribute::create([
            //     'activity_id' => $activity->id,
            //     'attribute_id' => rand(1, 4),
            //     'attribute_value' => '1 Hour'
            // ]);
            // ActivityAttribute::create([
            //     'activity_id' => $activity->id,
            //     'attribute_id' => rand(3, 4),
            //     'attribute_value' => 'Easy'
            // ]);

            // Fetch all attributes
            $attribute = Attribute::inRandomOrder()->first();
            
            if (!$attribute || empty($attribute->values)) {
                continue; // Skip if no attribute or empty values
            }

            // Decode JSON values and pick a random one
            $attributeValues = json_decode($attribute->values, true);
            $randomValue = is_array($attributeValues) ? $attributeValues[array_rand($attributeValues)] : null;

            if ($randomValue) {
                ActivityAttribute::create([
                    'activity_id' => $activity->id,
                    'attribute_id' => $attribute->id,
                    'attribute_value' => $randomValue, // Use a value from JSON
                ]);
            }

            // Pricing
            $pricing = ActivityPricing::create([
                'activity_id' => $activity->id,
                'regular_price' => rand(50, 500),
                'currency' => 'USD',
            ]);

            // ⏳ Seasonal Pricing (if enabled)
            // if ($pricing->enable_seasonal_pricing) {
                ActivitySeasonalPricing::create([
                    'activity_id' => $activity->id,
                    'enable_seasonal_pricing' => true,
                    'season_name' => 'Winter Special',
                    'season_start' => '2025-12-01',
                    'season_end'    => '2026-02-28',
                    'season_price' => rand(60, 400),
                ]);
            // }

            // 👫 Group Discounts
            ActivityGroupDiscount::create([
                'activity_id' => $activity->id,
                'min_people' => rand(5, 10),
                'discount_amount' => rand(10, 50),
                'discount_type' => 'percentage'
            ]);
            ActivityGroupDiscount::create([
                'activity_id' => $activity->id,
                'min_people' => rand(11, 20),
                'discount_amount' => rand(5, 30),
                'discount_type' => 'fixed'
            ]);

            // 🎟 Early Bird Discount (if enabled)
            // if ($pricing->enable_early_bird_discount) {
                ActivityEarlyBirdDiscount::create([
                    'activity_id' => $activity->id,
                    'enable_early_bird_discount' => true,
                    'days_before_start' => rand(10, 30),
                    'discount_amount' => rand(5, 20),
                    'discount_type' => 'percentage'
                ]);
            // }

            // ⏳ Last Minute Discount (if enabled)
            // if ($pricing->enable_last_minute_discount) {
                ActivityLastMinuteDiscount::create([
                    'activity_id' => $activity->id,
                    'enable_last_minute_discount' => true,
                    'days_before_start' => rand(1, 5),
                    'discount_amount' => rand(5, 15),
                    'discount_type' => 'fixed'
                ]);
            // }

            // 🎁 Promo Codes
            ActivityPromoCode::create([
                'activity_id' => $activity->id,
                'promo_code' => 'NEWYEAR50',
                'max_uses' => 100,
                'discount_amount' => 50,
                'discount_type' => 'percentage',
                'valid_from' => '2025-06-01',
                'valid_to' => '2025-08-31',
            ]);
            ActivityPromoCode::create([
                'activity_id' => $activity->id,
                'promo_code' => 'SUMMER25',
                'max_uses' => 50,
                'discount_amount' => 25,
                'discount_type' => 'fixed',
                'valid_from' => '2025-06-01',
                'valid_to' => '2025-08-31',
            ]);
            ActivityAvailability::create([
                'activity_id' => $activity->id,
                'date_based_activity' => $dateBased = fake()->boolean, // Random true or false
                'start_date' => $dateBased ? fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d') : null,
                'end_date' => $dateBased ? fake()->dateTimeBetween('+2 month', '+6 month')->format('Y-m-d') : null,
                'quantity_based_activity' => $quantityBased = fake()->boolean, // Random true or false
                'max_quantity' => $quantityBased ? fake()->numberBetween(1, 20) : null,
            ]);
        }
    }
}
