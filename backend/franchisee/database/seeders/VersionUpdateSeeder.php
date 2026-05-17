<?php

namespace Database\Seeders;

use App\Models\VersionUpdate;
use Illuminate\Database\Seeder;

class VersionUpdateSeeder extends Seeder
{
    public function run(): void
    {
        $versions = [
            [
                'version_number' => '#2.7.5002',
                'month' => 'September',
                'year' => 2024,
                'changes' => [
                    'Display Social Notification on Dashboard',
                    'Hide Payment Type on Complete Booking Screen',
                ],
                'release_date' => '2024-09-15',
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'version_number' => '#2.1.1008',
                'month' => 'October',
                'year' => 2021,
                'changes' => [
                    'Display Social Notification on Dashboard',
                    'Hide Payment Type on Complete Booking Screen',
                ],
                'release_date' => '2021-10-28',
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'version_number' => '#2.1.1006',
                'month' => 'October',
                'year' => 2021,
                'changes' => [
                    'Caching issue Resolution',
                ],
                'release_date' => '2021-10-26',
                'is_published' => true,
                'sort_order' => 2,
            ],
            [
                'version_number' => '#2.1.1005',
                'month' => 'October',
                'year' => 2021,
                'changes' => [
                    'Resolve below issues:',
                    '1. Forum Image quality',
                    '2. Forum now only displays Daily chats on Daily chat section',
                    '3. Rebook now display correct date',
                    '4. Calendar search now display all fields (no empty fields)',
                    '5. Option to clear the name on create booking screen.',
                    '6. Introduction of Mark first 25 Complete on "Booking Notification" list.',
                ],
                'release_date' => '2021-10-25',
                'is_published' => true,
                'sort_order' => 3,
            ],
            [
                'version_number' => '#2.1.1004',
                'month' => 'October',
                'year' => 2021,
                'changes' => [
                    'Resolve Update Income Issues',
                ],
                'release_date' => '2021-10-24',
                'is_published' => true,
                'sort_order' => 4,
            ],
            [
                'version_number' => '#2.1.1003',
                'month' => 'October',
                'year' => 2021,
                'changes' => [
                    'Design fix for Blockout Pop up',
                ],
                'release_date' => '2021-10-23',
                'is_published' => true,
                'sort_order' => 5,
            ],
            [
                'version_number' => '#2.1.1002',
                'month' => 'October',
                'year' => 2021,
                'changes' => [
                    'Fix Calendar and Rebooking Settings.',
                ],
                'release_date' => '2021-10-22',
                'is_published' => true,
                'sort_order' => 6,
            ],
            [
                'version_number' => '#2.1.1001',
                'month' => 'October',
                'year' => 2021,
                'changes' => [
                    '1. Edit Customer Detail on Multiple screens (Create Booking, View Booking Detail etc)',
                    '2. Edit Pet Detail on multiple screens',
                    '3. Income (GST Inc/Ex)',
                    '4. Expense (GST Inc/EX)',
                    '5. Calendar Settings',
                    '6. Recurring Booking border color on calendar.',
                    '7. Calendar Color on View Booking and Create/Edit Booking',
                    '8. ReBooking',
                ],
                'release_date' => '2021-10-21',
                'is_published' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($versions as $version) {
            VersionUpdate::create($version);
        }
    }
}
