<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Form;
use App\Models\Segment;
use App\Models\Tag;
use App\Models\Template;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /** Demo account for local testing: demo@franchisecare.test / password123 (admin). Safe to re-run. */
    public function run(): void
    {
        $company = Company::firstOrCreate(['name' => 'Demo Company'], [
            'from_name' => 'Demo Company',
            'from_email' => 'hello@demo.test',
            'address' => '1 Example Street, Sydney NSW 2000',
        ]);
        $biz = $company->id;

        User::firstOrCreate(['email' => 'demo@franchisecare.test'], [
            'name' => 'Demo Owner', 'password' => 'password123', 'company_id' => $biz,
        ]);

        $list = ContactList::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Newsletter'], ['description' => 'Sample list']);
        $vip = Tag::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'VIP'], ['color' => '#e91e63']);
        $web = Tag::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Website lead'], ['color' => '#00897b']);

        $people = [
            ['Ann', 'Lee', 'ann@example.com', '+61400000001', 'manual', [$vip]],
            ['Bob', 'Ray', 'bob@example.com', '+61400000002', 'import', []],
            ['Cara', 'Stone', 'cara@gmail.com', '+61400000003', 'form', [$web]],
            ['Dev', 'Patel', 'dev@gmail.com', null, 'form', [$web, $vip]],
        ];
        foreach ($people as [$f, $l, $e, $p, $src, $tags]) {
            $c = Contact::withoutGlobalScopes()->where(['company_id' => $biz, 'email' => $e])->first();
            if (! $c) {
                $c = (new Contact)->forceFill(['company_id' => $biz, 'first_name' => $f, 'last_name' => $l, 'email' => $e, 'phone' => $p, 'source' => $src]);
                $c->save();
            }
            $c->lists()->syncWithoutDetaching([$list->id]);
            $c->tags()->syncWithoutDetaching(collect($tags)->pluck('id')->all());
        }

        $welcome = Template::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Welcome email'], [
            'type' => 'email',
            'subject' => 'Welcome, {{first_name}}!',
            'body' => '<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif"><h1>Hello {{first_name}},</h1><p>Thanks for joining {{company_name}}.</p></div>',
        ]);
        Template::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Promo SMS'], [
            'type' => 'sms',
            'body' => 'Hi {{first_name}}, 10% off this week at {{company_name}}! Reply STOP to opt out.',
        ]);

        Segment::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Gmail VIPs'], [
            'description' => 'VIP-tagged contacts on gmail',
            'match' => 'all',
            'rules' => [['field' => 'tag', 'op' => 'in', 'value' => $vip->id], ['field' => 'email_domain', 'op' => 'equals', 'value' => 'gmail.com']],
        ]);

        Form::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Website newsletter'], [
            'slug' => Str::lower(Str::random(10)),
            'title' => 'Join our newsletter',
            'description' => 'Tips and offers, straight to your inbox.',
            'fields' => [
                ['id' => 'f1', 'key' => 'first_name', 'type' => 'first_name', 'label' => 'First name', 'placeholder' => '', 'help' => '', 'required' => false, 'options' => []],
                ['id' => 'f2', 'key' => 'email', 'type' => 'email', 'label' => 'Email', 'placeholder' => 'you@example.com', 'help' => '', 'required' => true, 'options' => []],
                ['id' => 'f3', 'key' => 'interest', 'type' => 'dropdown', 'label' => 'What are you interested in?', 'placeholder' => 'Choose one', 'help' => '', 'required' => false, 'options' => ['Offers', 'News', 'Tips']],
                ['id' => 'f4', 'key' => 'consent', 'type' => 'checkbox', 'label' => 'I agree to receive emails', 'placeholder' => '', 'help' => '', 'required' => true, 'options' => []],
            ],
            'style' => ['pageBg' => '#f4f5f7', 'cardBg' => '#ffffff', 'text' => '#333333', 'accent' => '#3f51b5', 'btnText' => '#ffffff', 'radius' => 6, 'font' => 'sans', 'width' => 480],
            'button_text' => 'Subscribe', 'success_message' => 'Thanks for subscribing!',
            'list_ids' => [$list->id], 'tag_ids' => [$web->id], 'active' => true,
        ]);

        // a contact form: an inbox + email to the team + auto-reply, and its senders are NOT opted in to marketing
        Form::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Contact us'], [
            'type' => 'contact',
            'slug' => Str::lower(Str::random(10)),
            'title' => 'Contact us',
            'description' => "Send us a message and we'll get back to you.",
            'fields' => [
                ['id' => 'c1', 'key' => 'first_name', 'type' => 'first_name', 'label' => 'First name', 'placeholder' => '', 'help' => '', 'required' => true, 'options' => [], 'width' => 'half'],
                ['id' => 'c2', 'key' => 'last_name', 'type' => 'last_name', 'label' => 'Last name', 'placeholder' => '', 'help' => '', 'required' => false, 'options' => [], 'width' => 'half'],
                ['id' => 'c3', 'key' => 'email', 'type' => 'email', 'label' => 'Email', 'placeholder' => 'you@example.com', 'help' => '', 'required' => true, 'options' => [], 'width' => 'full'],
                ['id' => 'c4', 'key' => 'message', 'type' => 'textarea', 'label' => 'Message', 'placeholder' => 'How can we help?', 'help' => '', 'required' => true, 'options' => [], 'width' => 'full'],
            ],
            'style' => ['pageBg' => '#f4f5f7', 'cardBg' => '#ffffff', 'text' => '#333333', 'accent' => '#00897b', 'btnText' => '#ffffff', 'radius' => 6, 'font' => 'sans', 'width' => 480],
            'button_text' => 'Send message', 'success_message' => "Thanks — we'll be in touch soon.",
            'save_contact' => false, 'subscribe' => false, // an inbox: answers are kept with the form, nobody is added to contacts
            'notify_emails' => ['hello@demo.test'],
            'autoreply' => true, 'autoreply_subject' => 'We got your message',
            'autoreply_body' => "Hi {{first_name}},\n\nThanks for getting in touch with {{company_name}}. We'll reply within one business day.",
            'list_ids' => [], 'tag_ids' => [], 'active' => true,
        ]);

        // created paused (draft) so seeding never starts sending mail by itself
        Workflow::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Welcome new contacts'], [
            'trigger_type' => 'contact_created', 'trigger_config' => [],
            'status' => 'draft',
            'steps' => [
                ['type' => 'send_email', 'template_id' => $welcome->id],
                ['type' => 'wait', 'amount' => 3, 'unit' => 'days'],
                ['type' => 'add_tag', 'tag_id' => $web->id],
            ],
        ]);
    }
}
