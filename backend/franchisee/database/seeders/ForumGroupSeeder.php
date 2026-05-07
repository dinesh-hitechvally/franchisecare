<?php

namespace Database\Seeders;

use App\Models\ForumGroup;
use Illuminate\Database\Seeder;

class ForumGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Topic Groups (Public)
        $topics = [
            ['name' => 'Grooming', 'description' => 'Grooming tips, techniques, and discussions'],
            ['name' => 'Maintenance & Trailers', 'description' => 'Equipment maintenance and trailer discussions'],
            ['name' => 'Marketing', 'description' => 'Marketing strategies and ideas'],
            ['name' => 'Mate', 'description' => 'General Mate discussions'],
            ['name' => 'Operations', 'description' => 'Operational discussions and best practices'],
            ['name' => 'Products', 'description' => 'Product recommendations and reviews'],
        ];

        foreach ($topics as $topic) {
            ForumGroup::create([
                'name' => $topic['name'],
                'description' => $topic['description'],
                'type' => 'topic',
                'is_public' => true,
            ]);
        }

        // State Groups
        $states = [
            ['name' => 'ACT', 'description' => 'Australian Capital Territory'],
            ['name' => 'ACT - Service Providers And Tradies', 'description' => 'ACT service providers'],
            ['name' => 'NSW', 'description' => 'New South Wales'],
            ['name' => 'NSW - Service Providers And Tradies', 'description' => 'NSW service providers'],
            ['name' => 'NT', 'description' => 'Northern Territory'],
            ['name' => 'NT - Service Providers And Tradies', 'description' => 'NT service providers'],
            ['name' => 'QLD', 'description' => 'Queensland'],
            ['name' => 'QLD - Service Providers And Tradies', 'description' => 'QLD service providers'],
            ['name' => 'SA', 'description' => 'South Australia'],
            ['name' => 'SA - Service Providers And Tradies', 'description' => 'SA service providers'],
            ['name' => 'TAS', 'description' => 'Tasmania'],
            ['name' => 'TAS - Service Providers And Tradies', 'description' => 'TAS service providers'],
            ['name' => 'VIC', 'description' => 'Victoria'],
            ['name' => 'VIC - Service Providers And Tradies', 'description' => 'VIC service providers'],
            ['name' => 'WA', 'description' => 'Western Australia'],
            ['name' => 'WA - Service Providers And Tradies', 'description' => 'WA service providers'],
        ];

        foreach ($states as $state) {
            ForumGroup::create([
                'name' => $state['name'],
                'description' => $state['description'],
                'type' => 'state',
                'is_public' => true,
            ]);
        }

        // Custom/User Groups
        $customGroups = [
            ['name' => 'Mate App Testers', 'description' => 'Testing and feedback for the Mate App'],
            ['name' => 'State Support Team', 'description' => 'Support team for state-level issues'],
            ['name' => 'Marketing Champions', 'description' => 'Share marketing ideas and success stories'],
            ['name' => 'New Franchisees', 'description' => 'Support group for new franchise owners'],
        ];

        foreach ($customGroups as $customGroup) {
            ForumGroup::create([
                'name' => $customGroup['name'],
                'description' => $customGroup['description'],
                'type' => 'custom',
                'is_public' => true,
            ]);
        }

        $this->command->info('Forum groups seeded successfully.');
    }
}
