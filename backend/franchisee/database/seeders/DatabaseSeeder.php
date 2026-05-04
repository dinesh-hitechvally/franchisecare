<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\News;
use App\Models\ForumThread;
use App\Models\ForumComment;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Mate Support',
                'email' => 'support@franchisecare.com',
                'password' => bcrypt('password'),
                'role' => 'franchise_admin',
            ]);
        }

        // Seed News
        News::create([
            'title' => 'New icon on the map',
            'content' => 'Good news, you can now access the Forum from the Map. Its accessible from the main menu Tuesday, 31st Mar 2026',
            'category' => 'Notice',
            'author_id' => $user->id,
            'is_published' => true,
            'published_at' => '2026-03-31 10:00:00',
        ]);

        News::create([
            'title' => 'Next Week\'s Treat Special - Chicken Jerky',
            'content' => 'Hi Team, Chicken Jerky 500g are 10% off for 1 week from Monday 23th March - Only $13.50 for 500g Thursday, 19th Mar 2026',
            'category' => 'General',
            'author_id' => $user->id,
            'is_published' => true,
            'published_at' => '2026-03-19 10:00:00',
        ]);

        // Seed Forum
        $thread = ForumThread::create([
            'title' => 'Easter Wishes',
            'content' => "Hi Team,\n\nFrom the NSO, we'd like to wish you and your families a safe and happy Easter. We hope you take this time to relax, recharge, and enjoy the break with your loved ones.",
            'author_id' => $user->id,
            'topic' => 'General',
            'likes_count' => 6,
        ]);

        ForumComment::create([
            'thread_id' => $thread->id,
            'author_id' => $user->id,
            'content' => 'Happy Easter everyone!',
        ]);
    }
}
