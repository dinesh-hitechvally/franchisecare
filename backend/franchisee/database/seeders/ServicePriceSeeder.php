<?php

namespace Database\Seeders;

use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

class ServicePriceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Full Groom Toy', 'my_price' => 50.00, 'default_price' => 90.00, 'color' => '#4169E1', 'my_time' => 38, 'default_time' => 90],
            ['name' => 'Full Groom Small', 'my_price' => 40.00, 'default_price' => 90.00, 'color' => '#4169E1', 'my_time' => 10, 'default_time' => 90],
            ['name' => 'Full Groom Medium', 'my_price' => 50.00, 'default_price' => 100.00, 'color' => '#4169E1', 'my_time' => 15, 'default_time' => 90],
            ['name' => 'Full Groom Large', 'my_price' => 75.00, 'default_price' => 110.00, 'color' => '#4169E1', 'my_time' => 20, 'default_time' => 90],
            ['name' => 'Hygiene Clip Toy', 'my_price' => 55.00, 'default_price' => 70.00, 'color' => '#4169E1', 'my_time' => 25, 'default_time' => 40],
            ['name' => 'Hygiene Clip Small', 'my_price' => 45.00, 'default_price' => 70.00, 'color' => '#4169E1', 'my_time' => 30, 'default_time' => 40],
            ['name' => 'Hygiene Clip Medium', 'my_price' => 55.00, 'default_price' => 80.00, 'color' => '#4169E1', 'my_time' => 35, 'default_time' => 40],
            ['name' => 'Hygiene Clip Large', 'my_price' => 65.00, 'default_price' => 90.00, 'color' => '#4169E1', 'my_time' => 40, 'default_time' => 45],
            ['name' => 'Washing Only Toy', 'my_price' => 45.00, 'default_price' => 50.00, 'color' => '#4169E1', 'my_time' => 45, 'default_time' => 30],
            ['name' => 'Washing Only Small', 'my_price' => 35.00, 'default_price' => 50.00, 'color' => '#4169E1', 'my_time' => 50, 'default_time' => 30],
            ['name' => 'Washing Only Medium', 'my_price' => 45.00, 'default_price' => 55.00, 'color' => '#4169E1', 'my_time' => 55, 'default_time' => 35],
            ['name' => 'Washing Only Large', 'my_price' => 55.00, 'default_price' => 70.00, 'color' => '#4169E1', 'my_time' => 60, 'default_time' => 35],
            ['name' => 'Medicated Wash', 'my_price' => 37.00, 'default_price' => 10.00, 'color' => '#4169E1', 'my_time' => 65, 'default_time' => 10],
            ['name' => 'Nail Clipping', 'my_price' => 5.00, 'default_price' => 20.00, 'color' => '#4169E1', 'my_time' => 70, 'default_time' => 10],
            ['name' => 'Flea Wash', 'my_price' => 10.00, 'default_price' => 10.00, 'color' => '#4169E1', 'my_time' => 75, 'default_time' => 10],
            ['name' => 'Deshed', 'my_price' => 30.00, 'default_price' => 10.00, 'color' => '#4169E1', 'my_time' => 80, 'default_time' => 5],
            ['name' => 'Flea Treatments', 'my_price' => 15.00, 'default_price' => 15.00, 'color' => '#4169E1', 'my_time' => 85, 'default_time' => 5],
            ['name' => 'Blue Wheelers Treats', 'my_price' => 50.00, 'default_price' => 10.00, 'color' => '#4169E1', 'my_time' => 90, 'default_time' => 0],
            ['name' => 'Card Surcharge', 'my_price' => 0.00, 'default_price' => 0.00, 'color' => '#4169E1', 'my_time' => 95, 'default_time' => 0],
            ['name' => 'Accessories', 'my_price' => 0.00, 'default_price' => 0.00, 'color' => '#4169E1', 'my_time' => 100, 'default_time' => 0],
            ['name' => 'Other', 'my_price' => 8.00, 'default_price' => 0.00, 'color' => '#4169E1', 'my_time' => 105, 'default_time' => 1],
        ];

        // You can set company_id = 1 for testing, or loop through companies
        foreach ($services as $service) {
            ServicePrice::create(array_merge($service, ['company_id' => 1]));
        }
    }
}
