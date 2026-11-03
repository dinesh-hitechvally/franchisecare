<?php

namespace Database\Seeders;

use App\Models\PeopleList;
use App\Models\Form;
use App\Models\Tag;
use Illuminate\Support\Str;

/** forms: a newsletter sign-up and a contact form */
class FormSeeder extends DemoSeeder
{
    public function run(): void
    {
        $biz = $this->companyId();
        $list = PeopleList::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => PeopleListSeeder::NEWSLETTER])->first();
        $web = Tag::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => TagSeeder::WEBSITE_LEAD])->first();

        Form::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Website newsletter'], [
            'slug' => Str::lower(Str::random(10)),
            'title' => 'Join our newsletter',
            'description' => 'Tips and offers, straight to your inbox.',
            'fields' => [
                ['id' => 'f1', 'key' => 'first_name', 'type' => 'first_name', 'label' => 'First name', 'placeholder' => '', 'help' => '', 'required' => false, 'options' => [], 'width' => 'half'],
                ['id' => 'f2', 'key' => 'last_name', 'type' => 'last_name', 'label' => 'Last name', 'placeholder' => '', 'help' => '', 'required' => false, 'options' => [], 'width' => 'half'],
                ['id' => 'f3', 'key' => 'email', 'type' => 'email', 'label' => 'Email', 'placeholder' => 'you@example.com', 'help' => '', 'required' => true, 'options' => []],
                ['id' => 'f4', 'key' => 'phone', 'type' => 'phone', 'label' => 'Mobile', 'placeholder' => '+61 400 000 000', 'help' => '', 'required' => false, 'options' => []],
                ['id' => 'f5', 'key' => 'interest', 'type' => 'dropdown', 'label' => 'What are you interested in?', 'placeholder' => 'Choose one', 'help' => '', 'required' => false, 'options' => ['Offers', 'News', 'Tips']],
                ['id' => 'f6', 'key' => 'subscribe', 'type' => 'subscribe', 'label' => 'Subscribe me to the newsletter', 'placeholder' => '', 'help' => '', 'required' => true, 'options' => []],
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
            'save_person' => false, 'subscribe' => false, // an inbox: answers are kept with the form, nobody is added to people
            'notify_emails' => ['hello@demo.test'],
            'autoreply' => true, 'autoreply_subject' => 'We got your message',
            'autoreply_body' => "Hi {{first_name}},\n\nThanks for getting in touch with {{company_name}}. We'll reply within one business day.",
            'list_ids' => [], 'tag_ids' => [], 'active' => true,
        ]);
    }
}
