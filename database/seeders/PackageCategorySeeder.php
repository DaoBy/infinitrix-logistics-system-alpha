<?php

namespace Database\Seeders;

use App\Models\PackageCategory;
use Illuminate\Database\Seeder;

class PackageCategorySeeder extends Seeder
{
    public function run()
    {
        // Clear existing data first
        PackageCategory::truncate();

        $categories = [
            [
                'name' => 'Small Pouch',
                'code' => 'piece',
                'description' => 'Small documents, letters, and compact tools',
                'dimensions' => ['length' => 25, 'height' => 1, 'width' => 15],
                'image' => 'images/presets/small_pouch.png', // Remove leading slash
                'sort_order' => 1
            ],          
            [
                'name' => 'Medium Box',
                'code' => 'carton',
                'description' => 'Books, shoes, small appliances',
                'dimensions' => ['length' => 30, 'height' => 20, 'width' => 25],
                'image' => 'images/presets/medium_box.png',
                'sort_order' => 3
            ],
            [
                'name' => 'Large Box',
                'code' => 'carton',
                'description' => 'Safety gear, toolkits, clothing',
                'dimensions' => ['length' => 50, 'height' => 35, 'width' => 40],
                'image' => 'images/presets/large_box.png',
                'sort_order' => 4
            ],
            [
                'name' => 'Extra Large Box',
                'code' => 'carton',
                'description' => 'Power tools, helmets, small furniture',
                'dimensions' => ['length' => 70, 'height' => 50, 'width' => 50],
                'image' => 'images/presets/xl_box.png',
                'sort_order' => 5
            ],
            [
                'name' => 'Large Sack',
                'code' => 'sack',
                'description' => 'Work gloves, fabric, plastic fittings',
                'dimensions' => ['length' => 60, 'height' => 40, 'width' => 40],
                'image' => 'images/presets/large_sack.png',
                'sort_order' => 7
            ],
            [
                'name' => 'Standard Roll',
                'code' => 'roll',
                'description' => 'Blueprints, rolls of wire, posters',
                'dimensions' => ['length' => 50, 'height' => 10, 'width' => 10],
                'image' => 'images/presets/standard_roll.png',
                'sort_order' => 8
            ],
            [
                'name' => 'Bundle Roll',
                'code' => 'bundle',
                'description' => 'Metal pipes, PVC tubes, carpets',
                'dimensions' => ['length' => 100, 'height' => 10, 'width' => 10],
                'image' => 'images/presets/bundle_roll.png',
                'sort_order' => 9
            ],
        ];

        foreach ($categories as $category) {
            PackageCategory::create($category);
        }

        $this->command->info('Package categories seeded successfully!');
    }
}