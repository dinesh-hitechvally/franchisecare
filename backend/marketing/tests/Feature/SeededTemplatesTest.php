<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Template;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeededTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_templates_are_a_designed_welcome_email_and_two_short_texts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(TemplateSeeder::class); // re-running restores them, it never duplicates

        $all = Template::withoutGlobalScopes()->orderBy('id')->get()->keyBy('name');
        $this->assertEqualsCanonicalizing([TemplateSeeder::WELCOME_EMAIL, TemplateSeeder::WELCOME_SMS, TemplateSeeder::PROMO_SMS], $all->keys()->all());
        $this->assertCount(3, $all);

        // the email: a real design the visual designer can open, and the HTML that design renders to
        $email = $all[TemplateSeeder::WELCOME_EMAIL];
        $this->assertSame('email', $email->type);
        $this->assertSame('Welcome to {{company_name}}, {{first_name}}!', $email->subject);
        $this->assertSame(['header', 'hero', 'text', 'heading', 'features', 'divider', 'coupon', 'text', 'footer'], array_column($email->design['blocks'], 'type'));
        foreach (['Welcome, {{first_name}}!', TemplateSeeder::WELCOME_CODE, 'What you can expect', '{{unsubscribe_url}}', '{{company_name}}'] as $needle) {
            $this->assertStringContainsString($needle, $email->body);
        }
        $this->assertStringNotContainsString('Thanks for joining {{company_name}}.</p></div>', $email->body); // not the old one-line placeholder

        // the texts fit one message (160 characters) once the tags are filled in, use no emoji, and offer an opt-out
        foreach ([TemplateSeeder::WELCOME_SMS => TemplateSeeder::WELCOME_CODE, TemplateSeeder::PROMO_SMS => TemplateSeeder::PROMO_CODE] as $name => $code) {
            $sms = $all[$name];
            $this->assertSame('sms', $sms->type);
            $filled = str_replace(['{{first_name}}', '{{company_name}}'], ['Alexandra', 'Demo Company Pty Ltd'], $sms->body);
            $this->assertLessThanOrEqual(160, strlen($filled), $name);
            $this->assertSame(0, preg_match('/[^\x00-\x7F]/', $filled), "$name stays plain text");
            $this->assertStringContainsString($code, $sms->body);
            $this->assertStringContainsString('Reply STOP', $sms->body);
        }
    }

    public function test_the_welcome_campaign_carries_the_design_so_it_opens_in_the_designer(): void
    {
        $this->seed(DatabaseSeeder::class);

        $campaign = Campaign::withoutGlobalScopes()->where('name', 'Welcome newsletter')->first();
        $this->assertCount(9, $campaign->design['blocks']);
        $this->assertStringContainsString(TemplateSeeder::WELCOME_CODE, $campaign->body);
    }
}
