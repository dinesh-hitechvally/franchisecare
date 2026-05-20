<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use Illuminate\Database\Seeder;

class InventoryCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Office Supplies',
                'slug' => 'office',
                'color' => 'bg-gray-100 text-gray-700',
                'description' => 'Office supplies and stationery items',
                'sort_order' => 1,
            ],
            [
                'name' => 'Shampoo',
                'slug' => 'shampoo',
                'color' => 'bg-blue-100 text-blue-700',
                'description' => 'Shampoos, conditioners and grooming products',
                'sort_order' => 2,
            ],
            [
                'name' => 'Treats',
                'slug' => 'treats',
                'color' => 'bg-green-100 text-green-700',
                'description' => 'Dog treats and snacks',
                'sort_order' => 3,
            ],
            [
                'name' => 'Uniforms',
                'slug' => 'uniforms',
                'color' => 'bg-purple-100 text-purple-700',
                'description' => 'Staff uniforms and apparel',
                'sort_order' => 4,
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'color' => 'bg-orange-100 text-orange-700',
                'description' => 'Marketing materials, flyers, business cards',
                'sort_order' => 5,
            ],
            [
                'name' => 'General',
                'slug' => 'General',
                'color' => 'bg-slate-100 text-slate-700',
                'description' => 'General inventory items',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            InventoryCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'company_id' => null, // Global categories
                    'name' => $category['name'],
                    'color' => $category['color'],
                    'description' => $category['description'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Inventory categories seeded successfully!');
        $this->command->info('Total categories: ' . count($categories));
    }
}
