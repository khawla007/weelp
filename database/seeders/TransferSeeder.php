<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transfer;
use App\Models\TransferVendorRoute;
use App\Models\TransferPricingAvailability;
use App\Models\TransferMediaGallery;
use App\Models\TransferSeo;

class TransferSeeder extends Seeder
{
    public function run()
    {
        $transfers = [
            [
                'name'            => 'Airport Transfer',
                'description'     => 'Airport to Hotel transfer service',
                'transfer_type'   => 'One-way',
                'vendor_id'       => 1,
                'route_id'        => 1,
                'pricing_tier_id' => 1,
                'availability_id' => 1,
                'media'           => [
                    ['media_type' => 'photo', 'media_url' => 'https://example.com/photo1.jpg'],
                    ['media_type' => 'video', 'media_url' => 'https://example.com/video1.mp4']
                ],
                'seo'             => [
                    'meta_title'       => 'Airport Transfer Service',
                    'meta_description' => 'Comfortable and reliable airport transfer service.',
                    'keywords'         => 'airport, transfer, hotel',
                    'og_image_url'     => 'https://example.com/og-image1.jpg',
                    'canonical_url'    => 'https://example.com/airport-transfer',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode([
                        '@context' => 'https://schema.org',
                        '@type'    => 'Service',
                        'name'     => 'Airport Transfer'
                    ])
                ]
            ],
            [
                'name'            => 'City Tour',
                'description'     => 'Full day city tour with private guide',
                'transfer_type'   => 'Round-trip',
                'vendor_id'       => 2,
                'route_id'        => 4,
                'pricing_tier_id' => 2,
                'availability_id' => 2,
                'media'           => [
                    ['media_type' => 'photo', 'media_url' => 'https://example.com/photo2.jpg'],
                    ['media_type' => 'video', 'media_url' => 'https://example.com/video2.mp4']
                ],
                'seo'             => [
                    'meta_title'       => 'City Tour',
                    'meta_description' => 'Enjoy a full day city tour with a professional guide.',
                    'keywords'         => 'city, tour, guide',
                    'og_image_url'     => 'https://example.com/og-image2.jpg',
                    'canonical_url'    => 'https://example.com/city-tour',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode([
                        '@context' => 'https://schema.org',
                        '@type'    => 'Service',
                        'name'     => 'City Tour'
                    ])
                ]
            ],
            [
                'name'            => 'Hotel Transfer',
                'description'     => 'Hotel to Train Station transfer service',
                'transfer_type'   => 'One-way',
                'vendor_id'       => 3,
                'route_id'        => 5,
                'pricing_tier_id' => 3,
                'availability_id' => 3,
                'media'           => [
                    ['media_type' => 'photo', 'media_url' => 'https://example.com/photo3.jpg'],
                    ['media_type' => 'video', 'media_url' => 'https://example.com/video3.mp4']
                ],
                'seo'             => [
                    'meta_title'       => 'Hotel Transfer',
                    'meta_description' => 'Easy and reliable hotel transfer service.',
                    'keywords'         => 'hotel, transfer, train',
                    'og_image_url'     => 'https://example.com/og-image3.jpg',
                    'canonical_url'    => 'https://example.com/hotel-transfer',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode([
                        '@context' => 'https://schema.org',
                        '@type'    => 'Service',
                        'name'     => 'Hotel Transfer'
                    ])
                ]
            ],
            [
                'name'            => 'Adventure Trip',
                'description'     => 'Mountain hiking trip with professional guide',
                'transfer_type'   => 'Round-trip',
                'vendor_id'       => 4,
                'route_id'        => 7,
                'pricing_tier_id' => 4,
                'availability_id' => 4,
                'media'           => [
                    ['media_type' => 'photo', 'media_url' => 'https://example.com/photo4.jpg'],
                    ['media_type' => 'video', 'media_url' => 'https://example.com/video4.mp4']
                ],
                'seo'             => [
                    'meta_title'       => 'Adventure Trip',
                    'meta_description' => 'Exciting adventure trip with professional guide.',
                    'keywords'         => 'adventure, trip, guide',
                    'og_image_url'     => 'https://example.com/og-image4.jpg',
                    'canonical_url'    => 'https://example.com/adventure-trip',
                    'schema_type'      => 'Service',
                    'schema_data'      => json_encode([
                        '@context' => 'https://schema.org',
                        '@type'    => 'Service',
                        'name'     => 'Adventure Trip'
                    ])
                ]
            ],
        ];

        foreach ($transfers as $data) {
            $transfer = Transfer::create([
                'name'          => $data['name'],
                'description'   => $data['description'],
                'transfer_type' => $data['transfer_type'],
            ]);

            // Vendor & Route  
            TransferVendorRoute::create([
                'transfer_id' => $transfer->id,
                'vendor_id' => $data['vendor_id'],
                'route_id' => $data['route_id'],
            ]);

            // Pricing & Availability  
            TransferPricingAvailability::create([
                'transfer_id' => $transfer->id,
                'pricing_tier_id' => $data['pricing_tier_id'],
                'availability_id' => $data['availability_id'],
            ]);

            // Media  
            foreach ($data['media'] as $media) {
                TransferMediaGallery::create([
                    'transfer_id' => $transfer->id,
                    'media_type' => $media['media_type'],
                    // 'media_url' => $media['media_url'],
                    'media_id' => rand(1, 5),
                ]);
            }

            // SEO  
            TransferSeo::create([
                'transfer_id' => $transfer->id,
                'meta_title' => $data['seo']['meta_title'],
                'meta_description' => $data['seo']['meta_description'],
                'keywords' => $data['seo']['keywords'],
                'og_image_url' => $data['seo']['og_image_url'],
                'canonical_url' => $data['seo']['canonical_url'],
                'schema_type' => $data['seo']['schema_type'],
                'schema_data' => $data['seo']['schema_data'],
            ]);
        }
    }
}
