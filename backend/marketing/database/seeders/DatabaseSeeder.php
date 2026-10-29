<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Demo account for local testing: demo@franchisecare.test / password123 */
    public function run(): void
    {
        $business = Business::firstOrCreate(['name' => 'Demo Business'], [
            'from_name' => 'Demo Business',
            'from_email' => 'hello@demo.test',
            'address' => '1 Example Street, Sydney NSW 2000',
        ]);

        User::firstOrCreate(['email' => 'demo@franchisecare.test'], [
            'name' => 'Demo Owner',
            'password' => 'password123',
            'business_id' => $business->id,
        ]);

        $list = ContactList::withoutGlobalScopes()->firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Newsletter'],
            ['description' => 'Sample list'],
        );

        foreach ([['Ann', 'Lee', 'ann@example.com', '+61400000001'], ['Bob', 'Ray', 'bob@example.com', '+61400000002']] as [$f, $l, $e, $p]) {
            $c = Contact::withoutGlobalScopes()->firstOrCreate(
                ['business_id' => $business->id, 'email' => $e],
                ['first_name' => $f, 'last_name' => $l, 'phone' => $p],
            );
            $c->lists()->syncWithoutDetaching([$list->id]);
        }

        Template::withoutGlobalScopes()->firstOrCreate(['business_id' => $business->id, 'name' => 'Welcome email'], [
            'type' => 'email',
            'subject' => 'Welcome, {{first_name}}!',
            'body' => '<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif"><h1>Hello {{first_name}},</h1><p>Thanks for joining {{business_name}}.</p></div>',
        ]);
        Template::withoutGlobalScopes()->firstOrCreate(['business_id' => $business->id, 'name' => 'Promo SMS'], [
            'type' => 'sms',
            'body' => 'Hi {{first_name}}, 10% off this week at {{business_name}}! Reply STOP to opt out.',
        ]);
    }
}
