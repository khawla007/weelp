<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\CityLocationDetail;
use App\Models\CityTravelInfo;
use App\Models\CitySeason;
use App\Models\CityEvent;
use App\Models\CityAdditionalInfo;
use App\Models\CityFaq;
use App\Models\CitySeo;

class CitySeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ Insert Cities
        $cities = [
            [
                'state_id' => 1, // Rajasthan
                'name' => 'Jaipur',
                'slug' => 'jaipur',
                'description' => 'The Pink City of India.',
                'feature_image' => 'https://example.com/jaipur.jpg',
                'featured_destination' => true,
            ],
            [
                'state_id' => 1,
                'name' => 'Udaipur',
                'slug' => 'udaipur',
                'description' => 'The City of Lakes.',
                'feature_image' => 'https://example.com/udaipur.jpg',
                'featured_destination' => false,
            ]
        ];

        foreach ($cities as $data) {
            $city = City::create($data);

            // 2️⃣ Insert City Location Details
            CityLocationDetail::create([
                'city_id' => $city->id,
                'latitude' => '26.9124',
                'longitude' => '75.7873',
                'population' => 3000000,
                'currency' => 'INR',
                'timezone' => 'GMT+5:30',
                'language' => 'Hindi, Rajasthani',
                'local_cuisine' => 'Dal Baati Churma, Ghewar'
            ]);

            // 3️⃣ Insert Travel Information
            CityTravelInfo::create([
                'city_id' => $city->id,
                'airport' => 'Jaipur International Airport',
                'public_transportation' => 'Buses, Rickshaws, Metro',
                'taxi_available' => true,
                'rental_cars_available' => true,
                'hotels' => true,
                'hostels' => true,
                'apartments' => true,
                'resorts' => true,
                'visa_requirements' => 'No visa required for Indian citizens',
                'best_time_to_visit' => 'October - March',
                'travel_tips' => 'Wear comfortable walking shoes',
                'safety_information' => 'Safe but beware of pickpockets in crowded areas'
            ]);

            // 4️⃣ Insert Seasons
            CitySeason::create([
                'city_id' => $city->id,
                'name' => 'Winter',
                'months' => 'November - February',
                'weather' => 'Cool and pleasant',
                'activities' => 'Heritage Walks, Sightseeing, Shopping'
            ]);

            // 5️⃣ Insert Events
            CityEvent::create([
                'city_id' => $city->id,
                'name' => 'Jaipur Literature Festival',
                'type' => 'Cultural Festival',
                'date_time' => '2025-01-21 10:00:00',
                'location' => 'Jaipur, Rajasthan',
                'description' => 'A gathering of authors, thinkers, and readers from across the world.'
            ]);

            // 6️⃣ Insert Additional Information
            CityAdditionalInfo::create([
                'city_id' => $city->id,
                'title' => 'Must-Visit Places',
                'content' => 'Hawa Mahal, City Palace, Amer Fort, Jal Mahal'
            ]);

            // 7️⃣ Insert FAQs
            CityFaq::create([
                'city_id' => $city->id,
                'question_number' => 1,
                'question' => 'What is the best time to visit Jaipur?',
                'answer' => 'October to March is the best time due to pleasant weather.'
            ]);

            // 8️⃣ Insert SEO Data
            CitySeo::create([
                'city_id' => $city->id,
                'meta_title' => 'Explore Jaipur - The Pink City',
                'meta_description' => 'Discover the rich heritage of Jaipur, Rajasthan.',
                'keywords' => 'Jaipur, Rajasthan, Pink City, Amer Fort, Hawa Mahal',
                'og_image_url' => 'https://example.com/og-jaipur.jpg',
                'canonical_url' => 'https://example.com/jaipur',
                'schema_type' => 'TravelDestination',
                // 'schema_data' => json_encode([
                //     "@context" => "https://schema.org",
                //     "@type" => "TravelDestination",
                //     "name" => "Jaipur",
                //     "description" => "The capital of Rajasthan, known for its royal heritage.",
                //     "image" => "https://example.com/jaipur.jpg"
                // ], JSON_UNESCAPED_UNICODE)
                'schema_data' => [
                    "@context" => "https://schema.org",
                    "@type" => "TravelDestination",
                    "name" => "Jaipur",
                    "description" => "The capital of Rajasthan, known for its royal heritage.",
                    "image" => "https://example.com/jaipur.jpg"
                ],
            ]);
        }
    }
}
