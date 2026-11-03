<?php

namespace Database\Seeders;

use App\Models\Template;

/** templates */
class TemplateSeeder extends DemoSeeder
{
    public const WELCOME_EMAIL = 'Welcome email';

    public function run(): void
    {
        $biz = $this->companyId();

        Template::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => self::WELCOME_EMAIL], [
            'type' => 'email',
            'subject' => 'Welcome, {{first_name}}!',
            'body' => '<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif"><h1>Hello {{first_name}},</h1><p>Thanks for joining {{company_name}}.</p></div>',
        ]);
        Template::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Promo SMS'], [
            'type' => 'sms',
            'body' => 'Hi {{first_name}}, 10% off this week at {{company_name}}! Reply STOP to opt out.',
        ]);
    }
}
