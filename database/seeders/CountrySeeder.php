<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\CountryLocationDetail;
use App\Models\CountryTravelInfo;
use App\Models\CountrySeason;
use App\Models\CountryEvent;
use App\Models\CountryAdditionalInfo;
use App\Models\CountryFaq;
use App\Models\CountrySeo;

class CountrySeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ Insert Countries
        $countries = [
            [
                'name' => 'India',
                'country_code' => '+91',
                'slug' => 'india',
                'description' => 'A beautiful country',
                'feature_image' => 'https://example.com/india.jpg',
                'featured_destination' => true,
            ],
            [
                'name' => 'United States',
                'country_code' => '+1',
                'slug' => 'united-states',
                'description' => 'USA details',
                'feature_image' => 'https://example.com/usa.jpg',
                'featured_destination' => false,
            ],
        ];

        foreach ($countries as $data) {
            $country = Country::create($data);

            // 2️⃣ Insert Country Details
            CountryLocationDetail::create([
                'country_id' => $country->id,
                'latitude' => '20.5937',
                'longitude' => '78.9629',
                'capital_city' => 'New Delhi',
                'population' => 1393409038,
                'currency' => 'INR',
                'timezone' => 'GMT+5:30',
                'language' => 'Hindi, English',
                'local_cuisine' => 'Dal Makhani, Biryani, Samosa'
            ]);

            // 3️⃣ Insert Travel Information
            CountryTravelInfo::create([
                'country_id' => $country->id,
                'airport' => 'Indira Gandhi International Airport',
                'public_transportation' => 'Metro, Buses, Trains',
                'taxi_available' => true,
                'rental_cars_available' => true,
                'hotels' => true,
                'hostels' => true,
                'apartments' => true,
                'resorts' => true,
                'visa_requirements' => 'Visa on arrival available',
                'best_time_to_visit' => 'October - March',
                'travel_tips' => 'Carry local currency',
                'safety_information' => 'Safe for tourists'
            ]);

            // 4️⃣ Insert Seasons
            CountrySeason::create([
                'country_id' => $country->id,
                'name' => 'Winter',
                'months' => 'December - February',
                'weather' => 'Cold with snowfall in northern regions',
                'activities' => 'Skiing, Trekking'
            ]);

            // 5️⃣ Insert Events
            CountryEvent::create([
                'country_id' => $country->id,
                'name' => 'Diwali',
                'type' => 'Festival',
                'date_time' => '2025-10-24 18:00:00',
                'location' => 'All over India',
                'description' => 'Festival of Lights celebrated across India'
            ]);

            // 6️⃣ Insert Additional Information
            CountryAdditionalInfo::create([
                'country_id' => $country->id,
                'title' => 'Famous Tourist Attractions',
                'content' => 'Taj Mahal, Jaipur, Kerala Backwaters'
            ]);

            // 7️⃣ Insert FAQs
            CountryFaq::create([
                'country_id' => $country->id,
                'question_number' => 1,
                'question' => 'Do I need a visa to visit India?',
                'answer' => 'Yes, but Visa on arrival is available for many countries.'
            ]);

            // 8️⃣ Insert SEO Data
            CountrySeo::create([
                'country_id' => $country->id,
                'meta_title' => 'Visit India - Travel Guide',
                'meta_description' => 'Explore the beauty of India with our ultimate travel guide.',
                'keywords' => 'India, Travel, Taj Mahal, Tourism',
                'og_image_url' => 'https://example.com/og-india.jpg',
                'canonical_url' => 'https://example.com/india',
                'schema_type' => 'TravelDestination',
                'schema_data' => json_encode([
                    "@context" => "https://schema.org",
                    "@type" => "TravelDestination",
                    "name" => "India",
                    "description" => "A beautiful country with rich heritage.",
                    "image" => "https://example.com/india.jpg"
                ], JSON_UNESCAPED_UNICODE)
            ]);
        }
    }
}
