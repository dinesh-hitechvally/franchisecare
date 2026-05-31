<?php

namespace Database\Seeders;

use App\Models\TrainingCategory;
use App\Models\TrainingItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        // E-Learning Categories
        $elearningCategories = [
            ['name' => 'Breed Specific', 'type' => 'elearning', 'sort_order' => 1],
            ['name' => 'Operations', 'type' => 'elearning', 'sort_order' => 2],
            ['name' => 'Fundamentals', 'type' => 'elearning', 'sort_order' => 3],
            ['name' => 'Advanced', 'type' => 'elearning', 'sort_order' => 4],
            ['name' => 'Safety', 'type' => 'elearning', 'sort_order' => 5],
            ['name' => 'Behavior', 'type' => 'elearning', 'sort_order' => 6],
            ['name' => 'Wellbeing', 'type' => 'elearning', 'sort_order' => 7],
        ];

        foreach ($elearningCategories as $cat) {
            TrainingCategory::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'type' => $cat['type'],
                'sort_order' => $cat['sort_order'],
                'is_active' => true,
            ]);
        }

        // E-Learning Items
        $breedSpecific = TrainingCategory::where('name', 'Breed Specific')->first();
        $operations = TrainingCategory::where('name', 'Operations')->first();
        $fundamentals = TrainingCategory::where('name', 'Fundamentals')->first();
        $advanced = TrainingCategory::where('name', 'Advanced')->first();
        $safety = TrainingCategory::where('name', 'Safety')->first();
        $behavior = TrainingCategory::where('name', 'Behavior')->first();
        $wellbeing = TrainingCategory::where('name', 'Wellbeing')->first();

        $courses = [
            // Breed Specific
            ['category' => $breedSpecific, 'title' => 'PICA (Poodle)x', 'description' => 'Grooming techniques for Poodle crosses', 'duration' => '45 min'],
            ['category' => $breedSpecific, 'title' => 'PICA (Bichon)', 'description' => 'Grooming techniques for Bichon Frise', 'duration' => '40 min'],
            ['category' => $breedSpecific, 'title' => 'PICA (Cockapoo)', 'description' => 'Grooming techniques for Cockapoos', 'duration' => '42 min'],
            ['category' => $breedSpecific, 'title' => 'PICA (Cavoodle)', 'description' => 'Grooming techniques for Cavoodles', 'duration' => '38 min'],
            ['category' => $breedSpecific, 'title' => 'PICA (Goldendoodle)', 'description' => 'Grooming techniques for Goldendoodles', 'duration' => '50 min'],
            ['category' => $breedSpecific, 'title' => 'PICA (Labradoodle)', 'description' => 'Grooming techniques for Labradoodles', 'duration' => '48 min'],
            // Operations
            ['category' => $operations, 'title' => 'The Business of Dog Grooming', 'description' => 'Business management essentials for mobile groomers', 'duration' => '60 min'],
            ['category' => $operations, 'title' => 'Financial Management', 'description' => 'Managing finances and pricing strategies', 'duration' => '55 min'],
            ['category' => $operations, 'title' => 'Customer Service Excellence', 'description' => 'Building strong customer relationships', 'duration' => '35 min'],
            // Fundamentals
            ['category' => $fundamentals, 'title' => 'Back to Basics 1 - Bathing & Drying', 'description' => 'Fundamental bathing and drying techniques', 'duration' => '30 min'],
            ['category' => $fundamentals, 'title' => 'Back to Basics 2 - Coat Types', 'description' => 'Understanding different coat types and their care', 'duration' => '35 min'],
            ['category' => $fundamentals, 'title' => 'Back to Basics 3 - Nail Care', 'description' => 'Proper nail trimming and care techniques', 'duration' => '25 min'],
            ['category' => $fundamentals, 'title' => 'Back to Basics 4 - Ear Cleaning', 'description' => 'Safe ear cleaning procedures', 'duration' => '20 min'],
            // Advanced
            ['category' => $advanced, 'title' => 'Advanced Scissoring Techniques', 'description' => 'Professional scissoring skills', 'duration' => '75 min'],
            ['category' => $advanced, 'title' => 'Asian Fusion Styling', 'description' => 'Modern Asian fusion grooming styles', 'duration' => '65 min'],
            // Safety
            ['category' => $safety, 'title' => 'First Aid for Pets - PetTech', 'description' => 'Emergency first aid procedures for pets', 'duration' => '45 min'],
            // Behavior
            ['category' => $behavior, 'title' => 'Beyond the Fur: Understanding Dog Behaviour with K9 Consulting', 'description' => 'Dog behavior and psychology for groomers', 'duration' => '40 min'],
            // Wellbeing
            ['category' => $wellbeing, 'title' => 'Mental Health and Wellbeing in Business - Strawberry Seed Consulting', 'description' => 'Mental health awareness for business owners', 'duration' => '32 min'],
        ];

        foreach ($courses as $index => $course) {
            TrainingItem::create([
                'category_id' => $course['category']->id,
                'title' => $course['title'],
                'slug' => Str::slug($course['title']),
                'description' => $course['description'],
                'type' => 'course',
                'duration' => $course['duration'],
                'thumbnail' => '/images/courses/blue-wheelers-logo.jpg',
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }

        // Training Videos Category
        $videosCategory = TrainingCategory::create([
            'name' => 'Mate Training',
            'slug' => 'mate-training',
            'type' => 'videos',
            'description' => 'Learn how to use the Mate system effectively',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $videos = [
            ['title' => 'Mate - Intro', 'description' => 'Introduction to the Mate system and overview of features', 'duration' => '5:30'],
            ['title' => 'Mate - New Booking', 'description' => 'How to create and manage new bookings in the Mate system', 'duration' => '8:45'],
            ['title' => 'Mate - New Customer', 'description' => 'Adding and managing new customers and their pet profiles', 'duration' => '7:20'],
            ['title' => 'Mate - Adjusting preferences settings', 'description' => 'Customizing your Mate system preferences and settings', 'duration' => '6:15'],
            ['title' => 'Mate - Download and Read Blues News', 'description' => 'Accessing and reading company news and updates', 'duration' => '4:30'],
            ['title' => 'Mate - Setting Up and Adjusting Two-Factor Authentication', 'description' => 'Security setup and configuration for two-factor authentication', 'duration' => '5:45'],
            ['title' => 'Mate - How to Place Your Orders on Mate', 'description' => 'Ordering supplies and inventory through the Mate system', 'duration' => '9:10'],
            ['title' => 'Mate - Using online waivers', 'description' => 'Managing customer waivers and digital signatures', 'duration' => '6:40'],
            ['title' => 'Mate - Sending a customer a tax invoice / receipt', 'description' => 'Generating and sending invoices and receipts to customers', 'duration' => '7:55'],
            ['title' => 'Mate - How to complete your bookings', 'description' => 'Processing and finalizing completed bookings', 'duration' => '8:20'],
        ];

        foreach ($videos as $index => $video) {
            TrainingItem::create([
                'category_id' => $videosCategory->id,
                'title' => $video['title'],
                'slug' => Str::slug($video['title']),
                'description' => $video['description'],
                'type' => 'video',
                'duration' => $video['duration'],
                'thumbnail' => '/images/videos/' . Str::slug($video['title']) . '.jpg',
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }

        // Marketing Categories
        $physicalMarketing = TrainingCategory::create([
            'name' => 'Physical Marketing Ideas',
            'slug' => 'physical-marketing-ideas',
            'type' => 'marketing',
            'icon' => 'FileText',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $googleBusiness = TrainingCategory::create([
            'name' => 'Google My Business',
            'slug' => 'google-my-business',
            'type' => 'marketing',
            'icon' => 'MapPin',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $facebookMarketing = TrainingCategory::create([
            'name' => 'Facebook Groups',
            'slug' => 'facebook-groups',
            'type' => 'marketing',
            'icon' => 'Users',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Physical Marketing Items
        $physicalItems = [
            ['title' => 'Local Business Directories', 'description' => 'List your business in local directories like ARA (Australian Retailers Association)'],
            ['title' => 'Community Engagement', 'description' => 'Participate in local events, sponsorships, and community activities'],
            ['title' => 'Physical Marketing Materials', 'description' => 'Business cards, flyers, magnets, and promotional materials for local distribution'],
        ];

        foreach ($physicalItems as $index => $item) {
            TrainingItem::create([
                'category_id' => $physicalMarketing->id,
                'title' => $item['title'],
                'slug' => Str::slug($item['title']),
                'description' => $item['description'],
                'type' => 'document',
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }

        // Google My Business Tips
        $gmb_tips = [
            ['title' => 'Optimize Your Google My Business Profile', 'description' => 'Keep your business information up-to-date including hours, services, and contact details'],
            ['title' => 'Manage Multiple Locations', 'description' => 'If you have multiple service areas, set up location-specific profiles'],
            ['title' => 'Respond to Reviews', 'description' => 'Actively engage with customer reviews to build trust and improve your online reputation'],
            ['title' => 'Post Regular Updates', 'description' => 'Share updates, offers, and news through Google My Business posts'],
            ['title' => 'Add Photos Regularly', 'description' => 'Showcase your work with before/after photos and happy customers (with permission)'],
        ];

        foreach ($gmb_tips as $index => $tip) {
            TrainingItem::create([
                'category_id' => $googleBusiness->id,
                'title' => $tip['title'],
                'slug' => Str::slug($tip['title']),
                'description' => $tip['description'],
                'type' => 'document',
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }

        // Facebook Group Tips
        $fb_tips = [
            ['title' => 'Join Local Pet Groups', 'description' => 'Participate in local dog owner groups and pet communities'],
            ['title' => 'Create Engaging Content', 'description' => 'Share grooming tips, before/after photos, and educational content'],
            ['title' => 'Build Community', 'description' => 'Engage authentically with members, answer questions, and provide value'],
            ['title' => 'Run Special Promotions', 'description' => 'Offer exclusive deals for group members to drive bookings'],
            ['title' => 'Leverage User-Generated Content', 'description' => 'Encourage customers to share their pets\' grooming transformations'],
        ];

        foreach ($fb_tips as $index => $tip) {
            TrainingItem::create([
                'category_id' => $facebookMarketing->id,
                'title' => $tip['title'],
                'slug' => Str::slug($tip['title']),
                'description' => $tip['description'],
                'type' => 'document',
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }

        // Marketing Course
        TrainingItem::create([
            'category_id' => $facebookMarketing->id,
            'title' => 'Beyond the Likes: Mastering Social Media that Converts with Dan Nikas',
            'slug' => 'beyond-the-likes-mastering-social-media',
            'description' => 'A certified Global Trainer with 20+ years experience running high-performing social campaigns and creating content that builds trust and converts.',
            'type' => 'course',
            'instructor' => 'Dan Nikas, CEO & Director of Elite Brands',
            'highlights' => [
                'Solid social strategies you can use immediately',
                'Save time and market efficiently',
                'Stop chasing likes and start driving results',
                'No big budgets or marketing degree required',
            ],
            'sort_order' => 10,
            'is_featured' => true,
            'is_active' => true,
        ]);
    }
}
