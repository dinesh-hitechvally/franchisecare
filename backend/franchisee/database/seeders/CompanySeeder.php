<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Company::create([
            'name' => 'Mate Franchise Care',
            'slug' => 'mate-franchise-care',
            'email' => 'support@franchisecare.com',
            'phone' => '+1-800-MATE-CARE',
            'address' => '123 Franchise Street',
            'city' => 'Business City',
            'state' => 'CA',
            'zip' => '90210',
            'country' => 'USA',
            'active' => true,
        ]);

        Company::create([
            'name' => 'Franchise Support International',
            'slug' => 'franchise-support-intl',
            'email' => 'info@franchisecare.com',
            'phone' => '+1-888-SUPPORT',
            'address' => '456 Enterprise Avenue',
            'city' => 'Corporate Hub',
            'state' => 'NY',
            'zip' => '10001',
            'country' => 'USA',
            'active' => true,
        ]);
    }
}
