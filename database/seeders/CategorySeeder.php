<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ActivityCategory;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creating Activity Categories
        $categories = [
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'All sports-related activities'],
            ['name' => 'Music', 'slug' => 'music', 'description' => 'Musical events and activities'],
            ['name' => 'Fitness', 'slug' => 'fitness', 'description' => 'Health and fitness activities'],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Learning and educational activities'],
        ];

        foreach ($categories as $category) {
            ActivityCategory::create($category);
        }
    }
}