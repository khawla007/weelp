<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Place;
use App\Models\PlaceLocationDetail;
use App\Models\PlaceTravelInfo;
use App\Models\PlaceSeason;
use App\Models\PlaceEvent;
use App\Models\PlaceAdditionalInfo;
use App\Models\PlaceFaq;
use App\Models\PlaceSeo;

class PlaceSeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ Insert Places
        $places = [
            [
                'city_id' => 1, // Jaipur
                'name' => 'Amber Fort',
                'slug' => 'amber-fort',
                'description' => 'A magnificent fort known for its artistic Hindu style elements.',
                'feature_image' => 'https://example.com/amber-fort.jpg',
                'featured_destination' => true,
            ],
            [
                'city_id' => 1,
                'name' => 'Hawa Mahal',
                'slug' => 'hawa-mahal',
                'description' => 'A palace made of red and pink sandstone, also called the Palace of Winds.',
                'feature_image' => 'https://example.com/hawa-mahal.jpg',
                'featured_destination' => false,
            ]
        ];

        foreach ($places as $data) {
            $place = Place::create($data);

            // 2️⃣ Insert Place Location Details
            PlaceLocationDetail::create([
                'place_id' => $place->id,
                'latitude' => '26.9855',
                'longitude' => '75.8513',
                'population' => null,
                'currency' => 'INR',
                'timezone' => 'GMT+5:30',
                'language' => 'Hindi, English',
                'local_cuisine' => 'Rajasthani Thali, Ghewar'
            ]);

            // 3️⃣ Insert Travel Information
            PlaceTravelInfo::create([
                'place_id' => $place->id,
                'airport' => 'Jaipur International Airport',
                'public_transportation' => 'Buses, Auto Rickshaws, Taxis',
                'taxi_available' => true,
                'rental_cars_available' => true,
                'hotels' => true,
                'hostels' => false,
                'apartments' => true,
                'resorts' => true,
                'visa_requirements' => 'No visa required for Indian citizens',
                'best_time_to_visit' => 'October - March',
                'travel_tips' => 'Wear comfortable shoes for walking around the fort.',
                'safety_information' => 'Safe but avoid isolated areas at night.'
            ]);

            // 4️⃣ Insert Seasons
            PlaceSeason::create([
                'place_id' => $place->id,
                'name' => 'Winter',
                'months' => 'November - February',
                'weather' => 'Cool and perfect for sightseeing',
                'activities' => 'Photography, Guided Tours, Light & Sound Show'
            ]);

            // 5️⃣ Insert Events
            PlaceEvent::create([
                'place_id' => $place->id,
                'name' => 'Light & Sound Show',
                'type' => 'Cultural Event',
                'date_time' => '2025-02-15 19:00:00',
                'location' => 'Amber Fort, Jaipur',
                'description' => 'A historical show depicting the history of Amber Fort.'
            ]);

            // 6️⃣ Insert Additional Information
            PlaceAdditionalInfo::create([
                'place_id' => $place->id,
                'title' => 'Interesting Facts',
                'content' => 'Amber Fort was built in 1592 and is a UNESCO World Heritage Site.'
            ]);

            // 7️⃣ Insert FAQs
            PlaceFaq::create([
                'place_id' => $place->id,
                'question_number' => 1,
                'question' => 'What is the best time to visit Amber Fort?',
                'answer' => 'October to March is the best time due to pleasant weather.'
            ]);

            // 8️⃣ Insert SEO Data
            PlaceSeo::create([
                'place_id' => $place->id,
                'meta_title' => 'Explore Amber Fort - Jaipur',
                'meta_description' => 'Amber Fort, a magnificent palace known for its artistic architecture.',
                'keywords' => 'Amber Fort, Jaipur, Rajasthan, Historical Places, Travel',
                'og_image_url' => 'https://example.com/og-amber-fort.jpg',
                'canonical_url' => 'https://example.com/amber-fort',
                'schema_type' => 'TravelDestination',
                // 'schema_data' => json_encode([
                //     "@context" => "https://schema.org",
                //     "@type" => "TravelDestination",
                //     "name" => "Amber Fort",
                //     "description" => "A historical fort in Jaipur, Rajasthan.",
                //     "image" => "https://example.com/amber-fort.jpg"
                // ], JSON_UNESCAPED_UNICODE)
                'schema_data' => [
                    "@context" => "https://schema.org",
                    "@type" => "TravelDestination",
                    "name" => "Amber Fort",
                    "description" => "A historical fort in Jaipur, Rajasthan.",
                    "image" => "https://example.com/amber-fort.jpg"
                ],
            ]);
        }
    }
}
