<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Package;
use App\Models\PackageLocation;
use App\Models\PackageInformation;
use App\Models\PackageSchedule;
use App\Models\PackageActivity;
use App\Models\PackageTransfer;
use App\Models\PackageBasePricing;
use App\Models\PackagePriceVariation;
use App\Models\PackageBlackoutDate;
use App\Models\PackageInclusionExclusion;
use App\Models\PackageMediaGallery;
use App\Models\PackageFaq;
use App\Models\PackageSeo;
use App\Models\PackageCategory;
use App\Models\PackageAttribute;
use App\Models\PackageTag;
use App\Models\PackageAvailability;

class PackageSeeder extends Seeder
{
    public function run()
    {

        // Foreign key checks ko disable karo
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Tables ko truncate ki jagah delete karo
        Package::query()->delete();
        PackageInformation::query()->delete();
        PackageSchedule::query()->delete();
        PackageActivity::query()->delete();
        PackageTransfer::query()->delete();
        PackageBasePricing::query()->delete();
        PackagePriceVariation::query()->delete();
        PackageBlackoutDate::query()->delete();
        PackageInclusionExclusion::query()->delete();
        PackageMediaGallery::query()->delete();
        PackageSeo::query()->delete();
        PackageCategory::query()->delete();
        PackageAttribute::query()->delete();
        PackageTag::query()->delete();

        // Foreign key checks ko wapas enable karo
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $packages = [
            [
                'name' => 'Holiday In Goa',
                'slug' => Str::slug('Holiday In Goa'),
                'description' => 'Explore the luxury of Kenyas wild safari.',
                'featured' => true,
                'private' => false,
            ],
            [
                'name' => 'Holidays In Kerala',
                'slug' => Str::slug('Holidays In Kerala'),
                'description' => 'Experience the thrill of the Himalayas.',
                'featured' => true,
                'private' => false,
            ],
            [
                'name' => 'Honeymoon Package in Udisa',
                'slug' => Str::slug('Honeymoon Package in Udisa'),
                'description' => 'Relax on the white sands of Maldives.',
                'featured' => false,
                'private' => false,
            ],
            [
                'name' => 'Vacation In Pathankot',
                'slug' => Str::slug('Vacation In Pathankot'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
            [
                'name' => 'Holidays In Karnatka',
                'slug' => Str::slug('Holidays In Karnatka'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
            [
                'name' => 'Holidays in Dharamshala',
                'slug' => Str::slug('Holidays in Dharamshala'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
            [
                'name' => 'Holidays Package in Kashmir',
                'slug' => Str::slug('Holidays Package in Kashmir'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
            [
                'name' => 'Holidays Package in Triund',
                'slug' => Str::slug('Holidays Package in Triund'),
                'description' => 'Explore the rich culture of Japan.',
                'featured' => true,
                'private' => true,
            ],
        ];

        foreach ($packages as $PackageData) {
            $package = Package::create($PackageData);


            PackageInformation::create([
                'package_id' => $package->id,
                'section_title' => 'Famous Tourist Attractions',
                'content' => 'Taj Mahal, Jaipur, Kerala Backwaters'
            ]);

            PackageLocation::create([
                'package_id' => $package->id,
                'city_id' => rand(1, 4),
            ]);

            for ($day = 1; $day <= 3; $day++) {
                $schedule = PackageSchedule::create([
                    'package_id' => $package->id,
                    'day' => $day,
                ]);

                PackageActivity::create([
                    'schedule_id' => $schedule->id,
                    'activity_id' => 1,
                    'start_time' => '09:00:00',
                    'end_time' => '11:00:00',
                    'notes' => 'Sample activity note',
                    'price' => 100.00,
                    'include_in_package' => true,
                ]);

                PackageTransfer::create([
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

            $basePricing = PackageBasePricing::create([
                'package_id' => $package->id,
                'currency' => 'USD',
                'availability' => 'Available',
                'start_date' => now(),
                'end_date' => now()->addMonth(),
            ]);

            PackagePriceVariation::create([
                'base_pricing_id' => $basePricing->id,
                'name' => 'Standard Package',
                'regular_price' => 1000.00,
                'sale_price' => 800.00,
                'max_guests' => 4,
                'description' => 'Standard package with discount',
            ]);

            PackageBlackoutDate::create([
                'base_pricing_id' => $basePricing->id,
                'date' => now()->addDays(7),
                'reason' => 'Holiday season',
            ]);

            PackageInclusionExclusion::create([
                'package_id' => $package->id,
                'type' => 'Meal',
                'title' => 'Breakfast included',
                'description' => 'Breakfast included in the package',
                'include_exclude' => true,
            ]);

            PackageMediaGallery::create([
                'package_id' => $package->id,
                'url' => 'https://example.com/sample-image.jpg',
            ]);

            $packageId = $package->id;

            $lastQuestion = PackageFaq::where('package_id', $packageId)
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
                PackageFaq::create([
                    'package_id' => $package->id,
                    'question_number' => $questionNumber,
                    'question' => $faq['question'],
                    'answer' => $faq['answer']
                ]);
                $questionNumber++;
            }

            PackageSeo::create([
                'package_id' => $package->id,
                'meta_title' => 'Sample Package',
                'meta_description' => 'This is a sample Package for SEO testing.',
                'keywords' => 'Package, travel, sample',
                'og_image_url' => 'https://example.com/sample-og.jpg',
                'canonical_url' => 'https://example.com/sample',
                'schema_type' => 'Travel',
                'schema_data' => json_encode([
                    'type' => 'Travel',
                    'name' => $package->name,
                ]),
            ]);

            PackageCategory::create([
                'package_id' => $package->id,
                'category_id' => 1,
            ]);

            PackageAttribute::create([
                'package_id' => $package->id,
                'attribute_id' => 1,
                'attribute_value' => '1 Hour'
            ]);

            PackageTag::create([
                'package_id' => $package->id,
                'tag_id' => 1,
            ]);

            PackageAvailability::create([
                'package_id' => $package->id,
                'date_based_Package' => $dateBased = fake()->boolean,
                'start_date' => $dateBased ? fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d') : null,
                'end_date' => $dateBased ? fake()->dateTimeBetween('+2 month', '+6 month')->format('Y-m-d') : null,
                'quantity_based_Package' => $quantityBased = fake()->boolean,
                'max_quantity' => $quantityBased ? fake()->numberBetween(1, 100) : null,
            ]);
        }
    }

}
