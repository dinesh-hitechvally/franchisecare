<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo account for local testing: demo@franchisecare.test / password123 (admin). Safe to re-run.
     * One seeder class per table; the order below is the order the tables depend on each other.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            ContactListSeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
            ContactListMemberSeeder::class,
            ContactTagSeeder::class,
            TemplateSeeder::class,
            SegmentSeeder::class,
            CampaignSeeder::class,
            FormSeeder::class,
            WorkflowSeeder::class,
        ]);
    }
}
