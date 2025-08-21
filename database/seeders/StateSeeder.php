<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\State;
use App\Models\StateMediaGallery;
use App\Models\StateLocationDetail;
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
        // Insert States
        $states = [
            [
                'country_id' => 1, // India
                'name' => 'Rajasthan',
                'code' => 'RJ',
                'slug' => 'rajasthan',
                'description' => 'The land of kings and palaces.',
                'feature_image' => 'https://example.com/rajasthan.jpg',
                'featured_destination' => true,
            ],
            [
                'country_id' => 1,
                'name' => 'Goa',
                'code' => 'GA',
                'slug' => 'goa',
                'description' => 'The party capital of India.',
                'feature_image' => 'https://example.com/goa.jpg',
                'featured_destination' => false,
            ]
        ];

        $mediaIds = range(1, 5);

        foreach ($states as $data) {
            $state = State::create($data);

            // Country_Media (Array of Objects )
            $randomMedias = collect($mediaIds)->random(3); // ek state ko 3 random media milega
            foreach ($randomMedias as $mediaId) {
                StateMediaGallery::create([
                    'state_id' => $state->id,
                    'media_id'   => $mediaId,
                ]);
            }

            // Insert State Details
            StateLocationDetail::create([
                'state_id' => $state->id,
                'latitude' => '26.9124',
                'longitude' => '75.7873',
                'capital_city' => 'Jaipur',
                'population' => 80000000,
                'currency' => 'INR',
                'timezone' => 'GMT+5:30',
                'language' => ['Hindi', 'Rajasthani'],
                'local_cuisine' => ['Dal Baati Churma', 'Gatte ki Sabzi']
            ]);

            // Insert Travel Information
            StateTravelInfo::create([
                'state_id' => $state->id,
                'airport' => 'Jaipur International Airport',
                'public_transportation' => ['Buses', 'Rickshaws', 'Trains'],
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

            // Insert Seasons
            StateSeason::create([
                'state_id' => $state->id,
                'name' => 'Winter',
                'months' => ['November', 'February'],
                'weather' => 'Pleasant during the day, cold at night',
                'activities' => ['Camel Safari', 'Sightseeing']
            ]);

            // Insert Events
            StateEvent::create([
                'state_id' => $state->id,
                'name' => 'Pushkar Fair',
                'type' => ['Cultural', 'Festival'],
                'date' => '2025-11-14',
                'location' => 'Pushkar, Rajasthan',
                'description' => 'A vibrant fair with camels, cultural performances, and shopping'
            ]);

            // Insert Additional Information
            StateAdditionalInfo::create([
                'state_id' => $state->id,
                'title' => 'Must-Visit Places',
                'content' => 'Jaipur, Udaipur, Jaisalmer, Mount Abu'
            ]);

            $stateId = $state->id;

            $lastQuestion = StateFaq::where('state_id', $stateId)
            ->orderBy('question_number', 'desc')
            ->first();

            $questionNumber = $lastQuestion ? $lastQuestion->question_number + 1 : 1;

            $faqs = [
                [
                    'question' => 'Do I need a visa to visit India?',
                    'answer' => 'Yes, but Visa on arrival is available for many countries.'
                ],
                [
                    'question' => 'What is the currency in India?',
                    'answer' => 'The Indian Rupee (INR) is the official currency.'
                ]
            ];
            
            foreach ($faqs as $faq) {
                StateFaq::create([
                    'state_id' => $state->id,
                    'question_number' => $questionNumber,
                    'question' => $faq['question'],
                    'answer' => $faq['answer']
                ]);
                $questionNumber++;
            }

            // 8️⃣ Insert SEO Data
            StateSeo::create([
                'state_id' => $state->id,
                'meta_title' => 'Visit Rajasthan - Travel Guide',
                'meta_description' => 'Explore the royal heritage of Rajasthan with our ultimate travel guide.',
                'keywords' => 'Rajasthan, Travel, Jaipur, Jaisalmer, Udaipur',
                'og_image_url' => 'https://example.com/og-rajasthan.jpg',
                'canonical_url' => 'https://example.com/rajasthan',
                'schema_type' => 'TravelDestination',
                'schema_data' => [
                    "@context" => "https://schema.org",
                    "@type" => "TravelDestination",
                    "name" => "Rajasthan",
                    "description" => "The land of kings and royal heritage.",
                    "image" => "https://example.com/rajasthan.jpg"
                ],
            ]);
        }
    }
}
