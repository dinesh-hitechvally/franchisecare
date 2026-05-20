<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Service;
use App\Models\ServiceInventoryUsage;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ServiceInventoryUsageSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure inventory items exist first
        $this->call(InventoryItemsSeeder::class);

        // Get or create pumps unit
        $pumpsUnit = Unit::firstOrCreate(
            ['name' => 'pumps'],
            ['abbreviation' => 'pmp']
        );

        // Get services
        $services = Service::all()->keyBy('name');

        // Get inventory items with booking_usage enabled
        $inventoryItems = InventoryItem::where('booking_usage', true)
            ->get()
            ->keyBy('name');

        if ($services->isEmpty() || $inventoryItems->isEmpty()) {
            $this->command->warn('No services or booking_usage inventory items found. Skipping service inventory usage seeding.');
            return;
        }

        // Define service inventory usage rules
        // Each service uses certain shampoo products
        $usageRules = [
            'Full Groom - Small' => [
                ['inventory' => 'Herbal Deluxe Shampoo', 'quantity' => 2],
                ['inventory' => 'Conditioner', 'quantity' => 1],
            ],
            'Full Groom - Medium' => [
                ['inventory' => 'Herbal Deluxe Shampoo', 'quantity' => 3],
                ['inventory' => 'Conditioner', 'quantity' => 2],
            ],
            'Full Groom - Large' => [
                ['inventory' => 'Herbal Deluxe Shampoo', 'quantity' => 4],
                ['inventory' => 'Conditioner', 'quantity' => 3],
            ],
            'Bath & Tidy - Small' => [
                ['inventory' => 'Herbal Deluxe Shampoo', 'quantity' => 2],
            ],
            'Bath & Tidy - Medium' => [
                ['inventory' => 'Herbal Deluxe Shampoo', 'quantity' => 3],
            ],
            'Bath & Tidy - Large' => [
                ['inventory' => 'Herbal Deluxe Shampoo', 'quantity' => 4],
            ],
            'Puppy Groom' => [
                ['inventory' => 'Puppy Shampoo', 'quantity' => 1],
            ],
            'Flea Treatment' => [
                ['inventory' => 'Flea & Tick rinse', 'quantity' => 2],
            ],
            'Hydrobath Only' => [
                ['inventory' => 'Oatmeal Shampoo', 'quantity' => 2],
            ],
        ];

        $count = 0;

        foreach ($usageRules as $serviceName => $usages) {
            $service = $services->get($serviceName);
            if (!$service) {
                continue;
            }

            foreach ($usages as $usage) {
                $inventoryItem = $inventoryItems->get($usage['inventory']);
                if (!$inventoryItem) {
                    continue;
                }

                ServiceInventoryUsage::updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'inventory_id' => $inventoryItem->id,
                    ],
                    [
                        'quantity_per_booking' => $usage['quantity'],
                        'unit_id' => $pumpsUnit->id,
                        'notes' => null,
                        'is_active' => true,
                    ]
                );

                $count++;
            }
        }

        $this->command->info("Service inventory usage rules seeded successfully!");
        $this->command->info("Total rules created: {$count}");
    }
}
