<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class InventoryItemsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure categories are seeded first
        $this->call(InventoryCategoriesSeeder::class);

        // Get or create units
        $units = [];
        $unitNames = ['units', '5L', '2.5L', '1KG', '500g', '150g', '1.5kg', '160g', '50g', '1L', 'pumps'];
        foreach ($unitNames as $unitName) {
            $units[$unitName] = Unit::firstOrCreate(['name' => $unitName])->id;
        }

        // Get category IDs
        $categories = InventoryCategory::pluck('id', 'slug')->toArray();

        // Default company_id (adjust as needed)
        $companyId = 1;

        $products = [
            // ==================== SHAMPOO ==================== (booking_usage: true)
            ['name' => 'Herbal Deluxe Shampoo', 'category' => 'shampoo', 'sku' => 'SH201', 'unit_price' => 87.35, 'unit' => '5L', 'quantity' => 50, 'min_stock' => 10, 'booking_usage' => true],
            ['name' => 'Flea & Tick rinse', 'category' => 'shampoo', 'sku' => 'BWFT', 'unit_price' => 95.00, 'unit' => '2.5L', 'quantity' => 30, 'min_stock' => 5, 'booking_usage' => true],
            ['name' => 'Puppy Shampoo', 'category' => 'shampoo', 'sku' => 'SH202', 'unit_price' => 85.00, 'unit' => '5L', 'quantity' => 40, 'min_stock' => 10, 'booking_usage' => true],
            ['name' => 'Oatmeal Shampoo', 'category' => 'shampoo', 'sku' => 'OAT20', 'unit_price' => 89.50, 'unit' => '5L', 'quantity' => 35, 'min_stock' => 8, 'booking_usage' => true],
            ['name' => 'Conditioner', 'category' => 'shampoo', 'sku' => 'CON50', 'unit_price' => 75.00, 'unit' => '5L', 'quantity' => 25, 'min_stock' => 5, 'booking_usage' => true],

            // ==================== OFFICE ====================
            ['name' => 'Shampoo pump', 'category' => 'office', 'sku' => 'PUMP', 'unit_price' => 9.95, 'unit' => 'units', 'quantity' => 100, 'min_stock' => 20],
            ['name' => 'Receipt Paper Roll', 'category' => 'office', 'sku' => 'RCPT01', 'unit_price' => 5.50, 'unit' => 'units', 'quantity' => 200, 'min_stock' => 50],
            ['name' => 'Appointment Cards (100)', 'category' => 'office', 'sku' => 'APPT100', 'unit_price' => 12.00, 'unit' => 'units', 'quantity' => 50, 'min_stock' => 10],
            ['name' => 'Pen Pack (10)', 'category' => 'office', 'sku' => 'PEN10', 'unit_price' => 8.00, 'unit' => 'units', 'quantity' => 30, 'min_stock' => 5],
            ['name' => 'Clipboards', 'category' => 'office', 'sku' => 'CLIP01', 'unit_price' => 15.00, 'unit' => 'units', 'quantity' => 20, 'min_stock' => 5],

            // ==================== TREATS ====================
            ['name' => 'Baked Bones Beef 1kg', 'category' => 'treats', 'sku' => 'BW111', 'unit_price' => 11.70, 'unit' => '1KG', 'quantity' => 60, 'min_stock' => 15],
            ['name' => 'Baked Bone Biscuits - Cheese 1kg', 'category' => 'treats', 'sku' => 'BW106', 'unit_price' => 11.70, 'unit' => '1KG', 'quantity' => 50, 'min_stock' => 10],
            ['name' => 'Baked Bone Biscuits - Peanut Butter 1kg', 'category' => 'treats', 'sku' => 'BW107', 'unit_price' => 11.70, 'unit' => '1KG', 'quantity' => 50, 'min_stock' => 10],
            ['name' => 'Chicken Jerky 500g', 'category' => 'treats', 'sku' => 'BW101', 'unit_price' => 11.50, 'unit' => '500g', 'quantity' => 40, 'min_stock' => 10],
            ['name' => 'Just Roo 1kg', 'category' => 'treats', 'sku' => 'BW114', 'unit_price' => 41.10, 'unit' => '1KG', 'quantity' => 25, 'min_stock' => 5],
            ['name' => 'Beef Liver Treats 150g', 'category' => 'treats', 'sku' => 'BW115', 'unit_price' => 6.90, 'unit' => '150g', 'quantity' => 80, 'min_stock' => 20],
            ['name' => 'Beef Liver Treats 1kg', 'category' => 'treats', 'sku' => 'BW116', 'unit_price' => 39.80, 'unit' => '1KG', 'quantity' => 30, 'min_stock' => 8],
            ['name' => 'Roo Jerky 500g', 'category' => 'treats', 'sku' => 'BW102', 'unit_price' => 11.50, 'unit' => '500g', 'quantity' => 40, 'min_stock' => 10],
            ['name' => 'Roo Sticks (25pc) 1.5kg', 'category' => 'treats', 'sku' => 'BW112', 'unit_price' => 38.65, 'unit' => '1.5kg', 'quantity' => 20, 'min_stock' => 5],
            ['name' => 'Pig Ears (4pc) 160g', 'category' => 'treats', 'sku' => 'BW117', 'unit_price' => 12.45, 'unit' => '160g', 'quantity' => 60, 'min_stock' => 15],
            ['name' => 'Chicken Breast 1kg', 'category' => 'treats', 'sku' => 'BW142', 'unit_price' => 56.00, 'unit' => '1KG', 'quantity' => 25, 'min_stock' => 5],
            ['name' => 'Grain Free Chicken Mini Sticks 500g', 'category' => 'treats', 'sku' => 'BW134', 'unit_price' => 17.40, 'unit' => '500g', 'quantity' => 35, 'min_stock' => 10],
            ['name' => 'Beef Training Treats (Pellets) 1kg', 'category' => 'treats', 'sku' => 'BW148', 'unit_price' => 22.50, 'unit' => '1KG', 'quantity' => 40, 'min_stock' => 10],
            ['name' => 'Chicken Training Treats (Pellets) 1kg', 'category' => 'treats', 'sku' => 'BW149', 'unit_price' => 22.50, 'unit' => '1KG', 'quantity' => 40, 'min_stock' => 10],
            ['name' => 'Roo Training Treats (Pellets) 1kg', 'category' => 'treats', 'sku' => 'BW150', 'unit_price' => 21.30, 'unit' => '1KG', 'quantity' => 40, 'min_stock' => 10],
            ['name' => 'Pig Snouts 1kg', 'category' => 'treats', 'sku' => 'BW156', 'unit_price' => 62.15, 'unit' => '1KG', 'quantity' => 15, 'min_stock' => 3],
            ['name' => 'Pig Ears (25pc) 1kg', 'category' => 'treats', 'sku' => 'BW160', 'unit_price' => 67.80, 'unit' => '1KG', 'quantity' => 20, 'min_stock' => 5],
            ['name' => 'Lamb Puff Cubes 50g', 'category' => 'treats', 'sku' => 'BW161', 'unit_price' => 5.60, 'unit' => '50g', 'quantity' => 100, 'min_stock' => 25],
            ['name' => 'Lamb Puff Cubes 1kg', 'category' => 'treats', 'sku' => 'BW162', 'unit_price' => 63.00, 'unit' => '1KG', 'quantity' => 20, 'min_stock' => 5],
            ['name' => 'Australian Veggie Tubes 500g', 'category' => 'treats', 'sku' => 'BW168', 'unit_price' => 15.25, 'unit' => '500g', 'quantity' => 35, 'min_stock' => 10],
            ['name' => 'Beef Micro Bones 500g', 'category' => 'treats', 'sku' => 'BW165', 'unit_price' => 21.10, 'unit' => '500g', 'quantity' => 30, 'min_stock' => 8],
            ['name' => 'Chicken Micro Bones 500g', 'category' => 'treats', 'sku' => 'BW166', 'unit_price' => 21.10, 'unit' => '500g', 'quantity' => 30, 'min_stock' => 8],
            ['name' => 'Kangaroo Micro Bones 500g', 'category' => 'treats', 'sku' => 'BW167', 'unit_price' => 21.10, 'unit' => '500g', 'quantity' => 30, 'min_stock' => 8],
            ['name' => 'Golden Paws 500g', 'category' => 'treats', 'sku' => 'BW169', 'unit_price' => 31.65, 'unit' => '500g', 'quantity' => 25, 'min_stock' => 5],

            // ==================== UNIFORMS ====================
            ['name' => 'BW Womens Polo', 'category' => 'uniforms', 'sku' => '001', 'unit_price' => 49.50, 'unit' => 'units', 'quantity' => 30, 'min_stock' => 5],
            ['name' => 'BW Mens Polo', 'category' => 'uniforms', 'sku' => '002', 'unit_price' => 49.50, 'unit' => 'units', 'quantity' => 30, 'min_stock' => 5],
            ['name' => 'BW Unisex Champion Jacket', 'category' => 'uniforms', 'sku' => '003', 'unit_price' => 58.00, 'unit' => 'units', 'quantity' => 20, 'min_stock' => 3],
            ['name' => 'BW New Winter Jacket', 'category' => 'uniforms', 'sku' => '004', 'unit_price' => 69.90, 'unit' => 'units', 'quantity' => 15, 'min_stock' => 3],
            ['name' => 'BW Olympus Vest (Mens sizing)', 'category' => 'uniforms', 'sku' => '005', 'unit_price' => 65.00, 'unit' => 'units', 'quantity' => 15, 'min_stock' => 3],
            ['name' => 'BW Cap', 'category' => 'uniforms', 'sku' => '006', 'unit_price' => 18.00, 'unit' => 'units', 'quantity' => 50, 'min_stock' => 10],
            ['name' => 'BW Apron', 'category' => 'uniforms', 'sku' => '007', 'unit_price' => 25.00, 'unit' => 'units', 'quantity' => 25, 'min_stock' => 5],

            // ==================== MARKETING ====================
            ['name' => 'BW Business Cards (1,000)', 'category' => 'marketing', 'sku' => '10016', 'unit_price' => 145.00, 'unit' => 'units', 'quantity' => 10, 'min_stock' => 2],
            ['name' => 'BW Flyers', 'category' => 'marketing', 'sku' => '10016-F', 'unit_price' => 0.00, 'unit' => 'units', 'quantity' => 0, 'min_stock' => 0, 'notes' => 'Price depends on copies selected'],
            ['name' => 'BW Magnets (500)', 'category' => 'marketing', 'sku' => '0016', 'unit_price' => 170.00, 'unit' => 'units', 'quantity' => 8, 'min_stock' => 2],
            ['name' => 'DD Business Cards (1,000)', 'category' => 'marketing', 'sku' => '10014', 'unit_price' => 145.00, 'unit' => 'units', 'quantity' => 10, 'min_stock' => 2],
            ['name' => 'DD Flyers', 'category' => 'marketing', 'sku' => '10017', 'unit_price' => 0.00, 'unit' => 'units', 'quantity' => 0, 'min_stock' => 0, 'notes' => 'Price depends on copies selected'],
            ['name' => 'A-Frame Sign', 'category' => 'marketing', 'sku' => 'AFRAME01', 'unit_price' => 89.00, 'unit' => 'units', 'quantity' => 5, 'min_stock' => 1],
            ['name' => 'Pull-Up Banner', 'category' => 'marketing', 'sku' => 'BANNER01', 'unit_price' => 120.00, 'unit' => 'units', 'quantity' => 5, 'min_stock' => 1],
            ['name' => 'Car Magnets (Pair)', 'category' => 'marketing', 'sku' => 'CARMAG01', 'unit_price' => 95.00, 'unit' => 'units', 'quantity' => 10, 'min_stock' => 2],
        ];

        foreach ($products as $product) {
            $unitId = $units[$product['unit']] ?? $units['units'];
            $categoryId = $categories[$product['category']] ?? $categories['General'] ?? null;

            InventoryItem::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'sku' => $product['sku'],
                ],
                [
                    'category_id' => $categoryId,
                    'name' => $product['name'],
                    'quantity' => $product['quantity'],
                    'min_stock' => $product['min_stock'],
                    'unit_price' => $product['unit_price'],
                    'unit_id' => $unitId,
                    'notes' => $product['notes'] ?? null,
                    'is_active' => true,
                    'booking_usage' => $product['booking_usage'] ?? false,
                ]
            );
        }

        $this->command->info('Inventory items seeded successfully!');
        $this->command->info('Total items: ' . count($products));
    }
}
