<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\State;
use App\Models\StateDetail;
use App\Models\StateTravelInfo;
use App\Models\StateSeason;
use App\Models\StateEvent;
use App\Models\StateAdditionalInfo;
use App\Models\StateFaq;
use App\Models\StateSeo;

class StateSeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ Insert States
        $states = [
            [
                'country_id' => 1, // India
                'name' => 'Rajasthan',
                'slug' => 'rajasthan',
                'description' => 'The land of kings and palaces.',
                'feature_image' => 'https://example.com/rajasthan.jpg',
                'featured_destination' => true,
            ],
            [
                'country_id' => 1,
                'name' => 'Goa',
                'slug' => 'goa',
                'description' => 'The party capital of India.',
                'feature_image' => 'https://example.com/goa.jpg',
                'featured_destination' => false,
            ]
        ];

        foreach ($states as $data) {
            $state = State::create($data);

            // 2️⃣ Insert State Details
            StateDetail::create([
                'state_id' => $state->id,
                'latitude' => '26.9124',
                'longitude' => '75.7873',
                'capital_city' => 'Jaipur',
                'population' => 80000000,
                'currency' => 'INR',
                'timezone' => 'GMT+5:30',
                'language' => 'Hindi, Rajasthani',
                'local_cuisine' => 'Dal Baati Churma, Gatte ki Sabzi'
            ]);

            // 3️⃣ Insert Travel Information
            StateTravelInfo::create([
                'state_id' => $state->id,
                'airport' => 'Jaipur International Airport',
                'public_transportation' => 'Buses, Rickshaws, Trains',
                'taxi_available' => true,
                'rental_cars_available' => true,
                'hotels' => true,
                'hostels' => true,
                'apartments' => true,
                'resorts' => true,
                'visa_requirements' => 'No separate visa needed for domestic tourists',
                'best_time_to_visit' => 'October - March',
                'travel_tips' => 'Carry light cotton clothes during summer',
                'safety_information' => 'Safe but be cautious of local scams'
            ]);

            // 4️⃣ Insert Seasons
            StateSeason::create([
                'state_id' => $state->id,
                'name' => 'Winter',
                'months' => 'November - February',
                'weather' => 'Pleasant during the day, cold at night',
                'activities' => 'Camel Safari, Sightseeing'
            ]);

            // 5️⃣ Insert Events
            StateEvent::create([
                'state_id' => $state->id,
                'name' => 'Pushkar Fair',
                'type' => 'Cultural Festival',
                'date_time' => '2025-11-14 10:00:00',
                'location' => 'Pushkar, Rajasthan',
                'description' => 'A vibrant fair with camels, cultural performances, and shopping'
            ]);

            // 6️⃣ Insert Additional Information
            StateAdditionalInfo::create([
                'state_id' => $state->id,
                'title' => 'Must-Visit Places',
                'content' => 'Jaipur, Udaipur, Jaisalmer, Mount Abu'
            ]);

            // 7️⃣ Insert FAQs
            StateFaq::create([
                'state_id' => $state->id,
                'question_number' => 1,
                'question' => 'What is the best time to visit Rajasthan?',
                'answer' => 'October to March is the best time for pleasant weather.'
            ]);

            // 8️⃣ Insert SEO Data
            StateSeo::create([
                'state_id' => $state->id,
                'meta_title' => 'Visit Rajasthan - Travel Guide',
                'meta_description' => 'Explore the royal heritage of Rajasthan with our ultimate travel guide.',
                'keywords' => 'Rajasthan, Travel, Jaipur, Jaisalmer, Udaipur',
                'og_image_url' => 'https://example.com/og-rajasthan.jpg',
                'canonical_url' => 'https://example.com/rajasthan',
                'schema_type' => 'TravelDestination',
                'schema_data' => json_encode([
                    "@context" => "https://schema.org",
                    "@type" => "TravelDestination",
                    "name" => "Rajasthan",
                    "description" => "The land of kings and royal heritage.",
                    "image" => "https://example.com/rajasthan.jpg"
                ], JSON_UNESCAPED_UNICODE)
            ]);
        }
    }
}
