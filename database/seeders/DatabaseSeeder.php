<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ActivityCategory;
use App\Models\ActivityTag;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Ashish Khawla',
            'email' => 'khawla@fanaticcoders.com',
            'password' => Hash::make('khawla@123#'),
            'role' => 'super_admin'
        ]);

        User::factory()->create([
            'name' => 'Akshay Chauhan',
            'email' => 'akshay@fanaticcoders.com',
            'password' => Hash::make('akshay@123#'),
            'role' => 'admin'
        ]);

        // Creating Multiple Customer Users
        $customers = [
            // ['name' => 'Akshay Chauhan', 'email' => 'akshay@fanaticcoders.com', 'password' => 'akshay@123#'],
            ['name' => 'Vishal Sandhu', 'email' => 'vishal@fanaticcoders.com', 'password' => 'vishal@123#'],
            ['name' => 'Atul Sharma', 'email' => 'atul@fanaticcoders.com', 'password' => 'atul@123#'],
            ['name' => 'Gurmeet Singh', 'email' => 'gurmeet@fanaticcoders.com', 'password' => 'gurmeet@123#'],
            ['name' => 'Abhinav Chaudhary', 'email' => 'abhinav@fanaticcoders.com', 'password' => 'abhinav@123#'],
            ['name' => 'Vikas Dhiman', 'email' => 'vikas@fanaticcoders.com', 'password' => 'vikas@123#'],
            ['name' => 'Anshul Guleria', 'email' => 'anshul@fanaticcoders.com', 'password' => 'anshul@123#'],
        ];

        foreach ($customers as $customer) {
            User::factory()->create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'password' => Hash::make($customer['password']),
                'role' => 'customer'
            ]);
        }

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
