<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryActivity;
use App\Models\ItineraryTransfer;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryPriceVariation;
use App\Models\ItineraryBlackoutDate;
use App\Models\ItineraryInclusionExclusion;
use App\Models\ItineraryMediaGallery;
use App\Models\ItinerarySeo;
use App\Models\ItineraryCategory;
use App\Models\ItineraryAttribute;
use App\Models\ItineraryTag;
use App\Models\ItineraryAvailability;

class ItinerarySeeder extends Seeder
{
    public function run()
    {

        // Foreign key checks ko disable karo
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Tables ko truncate ki jagah delete karo
        Itinerary::query()->delete();
        ItinerarySchedule::query()->delete();
        ItineraryLocation::query()->delete();
        ItineraryActivity::query()->delete();
        ItineraryTransfer::query()->delete();
        ItineraryBasePricing::query()->delete();
        ItineraryPriceVariation::query()->delete();
        ItineraryBlackoutDate::query()->delete();
        ItineraryInclusionExclusion::query()->delete();
        ItineraryMediaGallery::query()->delete();
        ItinerarySeo::query()->delete();
        ItineraryCategory::query()->delete();
        ItineraryAttribute::query()->delete();
        ItineraryTag::query()->delete();

        // Foreign key checks ko wapas enable karo
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $itineraries = [
            [
                'name' => 'Luxury Safari in Kenya',
                'slug' => Str::slug('Luxury Safari in Kenya'),
                'description' => 'Explore the luxury of Kenyas wild safari.',
                'featured' => true,
                'private' => false,
            ],
            [
                'name' => 'Adventure Trek in Nepal',
                'slug' => Str::slug('Adventure Trek in Nepal'),
                'description' => 'Experience the thrill of the Himalayas.',
                'featured' => true,
                'private' => false,
            ],
            [
                'name' => 'Beach Vacation in Maldives',
                'slug' => Str::slug('Beach Vacation in Maldives'),
                'description' => 'Relax on the white sands of Maldives.',
                'featured' => false,
                'private' => false,
            ],
            [
                'name' => 'Cultural Tour in Japan',
                'slug' => Str::slug('Cultural Tour in Japan'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
            [
                'name' => 'Cultural Tour in Kangra',
                'slug' => Str::slug('Cultural Tour in kangra'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
            [
                'name' => 'Cultural Tour in Lama Temple',
                'slug' => Str::slug('Cultural Tour in Lama Temple'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
            [
                'name' => 'Cultural Tour in Dharamshala',
                'slug' => Str::slug('Cultural Tour in Dharamshala'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
            [
                'name' => 'Cultural Tour in Rehan',
                'slug' => Str::slug('Cultural Tour in Rehan'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
        ];

        foreach ($itineraries as $itineraryData) {
            $itinerary = Itinerary::create($itineraryData);

            ItineraryLocation::create([
                'itinerary_id' => $itinerary->id,
                'city_id' => rand(1, 4),
            ]);

            for ($day = 1; $day <= 3; $day++) {
                $schedule = ItinerarySchedule::create([
                    'itinerary_id' => $itinerary->id,
                    'day' => $day,
                ]);

                ItineraryActivity::create([
                    'schedule_id' => $schedule->id,
                    'activity_id' => 1,
                    'start_time' => '09:00:00',
                    'end_time' => '11:00:00',
                    'notes' => 'Sample activity note',
                    'price' => 100.00,
                    'include_in_package' => true,
                ]);

                ItineraryTransfer::create([
                    'schedule_id' => $schedule->id,
                    'transfer_id' => 1,
                    'start_time' => '12:00:00',
                    'end_time' => '14:00:00',
                    'notes' => 'Sample transfer note',
                    'price' => 50.00,
                    'include_in_package' => true,
                    'pickup_location' => 'Airport',
                    'dropoff_location' => 'Hotel',
                    'pax' => 2,
                ]);
            }

            $basePricing = ItineraryBasePricing::create([
                'itinerary_id' => $itinerary->id,
                'currency' => 'USD',
                'availability' => 'Available',
                'start_date' => now(),
                'end_date' => now()->addMonth(),
            ]);

            ItineraryPriceVariation::create([
                'base_pricing_id' => $basePricing->id,
                'name' => 'Standard Package',
                'regular_price' => 1000.00,
                'sale_price' => 800.00,
                'max_guests' => 4,
                'description' => 'Standard package with discount',
            ]);

            ItineraryBlackoutDate::create([
                'base_pricing_id' => $basePricing->id,
                'date' => now()->addDays(7),
                'reason' => 'Holiday season',
            ]);

            ItineraryInclusionExclusion::create([
                'itinerary_id' => $itinerary->id,
                'type' => 'Meal',
                'title' => 'Breakfast included',
                'description' => 'Breakfast included in the package',
                'include_exclude' => true,
            ]);

            ItineraryMediaGallery::create([
                'itinerary_id' => $itinerary->id,
                'url' => 'https://example.com/sample-image.jpg',
            ]);

            ItinerarySeo::create([
                'itinerary_id' => $itinerary->id,
                'meta_title' => 'Sample Itinerary',
                'meta_description' => 'This is a sample itinerary for SEO testing.',
                'keywords' => 'itinerary, travel, sample',
                'og_image_url' => 'https://example.com/sample-og.jpg',
                'canonical_url' => 'https://example.com/sample',
                'schema_type' => 'Travel',
                'schema_data' => json_encode([
                    'type' => 'Travel',
                    'name' => $itinerary->name,
                ]),
            ]);

            ItineraryCategory::create([
                'itinerary_id' => $itinerary->id,
                'category_id' => 1,
            ]);

            ItineraryAttribute::create([
                'itinerary_id' => $itinerary->id,
                'attribute_id' => 1,
                'attribute_value' => '1 Hour'
            ]);

            ItineraryTag::create([
                'itinerary_id' => $itinerary->id,
                'tag_id' => 1,
            ]);

            ItineraryAvailability::create([
                'itinerary_id' => $itinerary->id,
                'date_based_itinerary' => $dateBased = fake()->boolean,
                'start_date' => $dateBased ? fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d') : null,
                'end_date' => $dateBased ? fake()->dateTimeBetween('+2 month', '+6 month')->format('Y-m-d') : null,
                'quantity_based_itinerary' => $quantityBased = fake()->boolean,
                'max_quantity' => $quantityBased ? fake()->numberBetween(1, 100) : null,
            ]);
        }
    }

}
