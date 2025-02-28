<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ActivityTag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creating Activity Tags
        $tags = [
            ['name' => 'Outdoor', 'slug' => 'outdoor', 'description' => 'Activities conducted outdoors'],
            ['name' => 'Indoor', 'slug' => 'indoor', 'description' => 'Activities conducted indoors'],
            ['name' => 'Beginner', 'slug' => 'beginner', 'description' => 'Beginner-friendly activities'],
            ['name' => 'Advanced', 'slug' => 'advanced', 'description' => 'Activities for advanced users'],
        ];

        foreach ($tags as $tag) {
            ActivityTag::create($tag);
        }
    }
}
