<?php

namespace Database\Seeders;

use App\Models\ForumThread;
use App\Models\ForumComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        // Sample topics
        $topics = ['General', 'Grooming', 'Maintenance & Trailers', 'Marketing', 'Operations', 'Products'];

        // Create sample threads
        $sampleThreads = [
            [
                'title' => 'Happy Easter',
                'content' => "Hi Team,\n\nFrom the NSO, we'd like to wish you and your families a safe and happy Easter. We hope you take this time to relax, recharge, and enjoy the break with your loved ones.",
                'topic' => 'General',
            ],
            [
                'title' => 'New Grooming Techniques',
                'content' => "Hey everyone! I've been experimenting with some new grooming techniques for difficult coats. Anyone interested in sharing tips?",
                'topic' => 'Grooming',
            ],
            [
                'title' => 'Trailer Maintenance Schedule',
                'content' => "What's everyone's maintenance schedule looking like? I'm trying to optimize mine and would love some input.",
                'topic' => 'Maintenance & Trailers',
            ],
            [
                'title' => 'Marketing Ideas for Winter',
                'content' => "Winter is coming and business tends to slow down. What marketing strategies have worked for you during the colder months?",
                'topic' => 'Marketing',
            ],
            [
                'title' => 'Best Products for Sensitive Skin',
                'content' => "I've been getting more requests for sensitive skin treatments. What products do you all recommend?",
                'topic' => 'Products',
            ],
        ];

        foreach ($sampleThreads as $threadData) {

            $thread = ForumThread::create([
                'author_id' => $users->random()->id,
                'title' => $threadData['title'],
                'content' => $threadData['content'],
                'topic' => $threadData['topic'],
                'likes_count' => rand(0, 15),
            ]);

            // Add some random comments
            $commentCount = rand(0, 3);
            for ($i = 0; $i < $commentCount; $i++) {
                ForumComment::create([
                    'thread_id' => $thread->id,
                    'author_id' => $users->random()->id,
                    'content' => $this->getRandomComment(),
                ]);
            }

        }

        $this->command->info('Forum seeded successfully with sample threads and comments.');

    }

    private function getRandomComment(): string
    {
        
        $comments = [
            'Thanks for sharing this!',
            'Great idea! I\'ll definitely try this out.',
            'We\'ve had similar experiences. Here\'s what worked for us...',
            'This is really helpful, thank you!',
            'I agree! We should definitely consider this.',
            'Has anyone else tried this approach?',
            'Love this! Keep us updated on how it goes.',
            'Interesting perspective. I hadn\'t thought of that.',
        ];

        return $comments[array_rand($comments)];

    }
}
