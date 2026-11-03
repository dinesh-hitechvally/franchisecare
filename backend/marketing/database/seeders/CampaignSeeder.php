<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\ContactList;
use App\Models\Segment;
use App\Models\Tag;
use App\Models\Template;

/** campaigns: one draft for each kind of audience (list, tag, segment, everyone). Drafts only, so seeding never sends anything. */
class CampaignSeeder extends DemoSeeder
{
    public function run(): void
    {
        $biz = $this->companyId();
        $list = ContactList::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => ContactListSeeder::NEWSLETTER])->value('id');
        $vip = Tag::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => TagSeeder::VIP])->value('id');
        $web = Tag::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => TagSeeder::WEBSITE_LEAD])->value('id');
        $segment = Segment::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => 'Gmail VIPs'])->value('id');
        $welcome = Template::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => TemplateSeeder::WELCOME_EMAIL])->first();

        $page = fn (string $heading, string $text) => '<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif"><h1>'.$heading.'</h1><p>'.$text.'</p></div>';

        $campaigns = [
            ['Welcome newsletter', 'email', 'Welcome to {{company_name}}, {{first_name}}!', $welcome->body, ['template_id' => $welcome->id, 'contact_list_id' => $list]],
            ['VIP early access', 'email', 'Early access for you, {{first_name}}', $page('Hello {{first_name}},', 'As one of our VIPs you get first look at what is coming next at {{company_name}}.'), ['tag_id' => $vip]],
            ['Gmail VIP offer', 'email', 'A little something for our Gmail VIPs', $page('Hi {{first_name}},', 'A special offer, just for you.'), ['segment_id' => $segment]],
            ['Monthly update (everyone)', 'email', 'What happened this month at {{company_name}}', $page('Monthly update', 'Here is what we have been up to, {{first_name}}.'), []],
            ['Weekend promo SMS', 'sms', null, 'Hi {{first_name}}, 10% off this weekend at {{company_name}}! Reply STOP to opt out.', ['tag_id' => $web]],
            ['Flash sale SMS (everyone)', 'sms', null, 'Hi {{first_name}}, flash sale today only at {{company_name}}! Reply STOP to opt out.', []],
        ];

        foreach ($campaigns as [$name, $type, $subject, $body, $audience]) {
            if (Campaign::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => $name])->exists()) {
                continue;
            }
            (new Campaign)->forceFill(['company_id' => $biz, 'name' => $name, 'type' => $type, 'subject' => $subject, 'body' => $body, 'status' => 'draft'] + $audience)->save();
        }
    }
}
