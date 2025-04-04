<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attribute;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Define Attributes with Values
        $attributes = [
            [
                'name' => 'Duration',
                'type' => 'single_select',
                'description' => 'How long the lasts',
                'values' => ['1 Hour', '2 Hours', 'Half Day', 'Full Day'],
                'default_value' => '2 Hours'
            ],
            [
                'name' => 'Difficulty Level',
                'type' => 'single_select',
                'description' => 'difficulty level',
                'values' => ['Easy', 'Medium', 'Hard'],
                'default_value' => 'Medium'
            ],
            [
                'name' => 'Group Size',
                'type' => 'single_select',
                'description' => 'Maximum number of participants',
                'values' => ['1-5', '6-10', '11-20', '20+'],
                'default_value' => '6-10'
            ],
            [
                'name' => 'Age Restriction',
                'type' => 'single_select',
                'description' => 'Minimum age required for the',
                'values' => ['All Ages', '12+', '18+'],
                'default_value' => '12+'
            ]
        ];

        // Insert Attributes
        foreach ($attributes as $data) {
            Attribute::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'],
                'default_value' => $data['default_value'],
                // 'values' => in_array($data['type'], ['single_select', 'multi_select']) ? json_encode($data['values']) : null
                'values' => in_array($data['type'], ['single_select', 'multi_select']) ? implode(',', $data['values']) : null
            ]);
        }
    }
}
