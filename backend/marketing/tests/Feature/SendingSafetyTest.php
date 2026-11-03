<?php

namespace Tests\Feature;

use App\Jobs\SendMessageJob;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\EmailLog;
use App\Models\Template;
use App\Services\MessageRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use ReflectionMethod;
use Tests\TestCase;

class SendingSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $email = 'o@x.com', string $co = 'Acme'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => $co, 'name' => 'Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    protected function campaign(array $h, array $over = []): int
    {
        return $this->postJson('/api/campaigns', $over + ['name' => 'Spring', 'type' => 'email', 'subject' => 'Hello', 'body' => '<h1>Hi</h1><p>Visit <a href="https://example.com/a">our shop</a></p>'], $h)->assertCreated()->json('id');
    }

    public function test_every_email_has_a_plain_text_part_and_a_one_click_unsubscribe_header(): void
    {
        $h = $this->owner();
        $this->postJson('/api/contacts', ['email' => 'ann@x.com'], $h)->assertCreated();
        $id = $this->campaign($h);
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();

        $sent = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $token = \App\Models\Contact::withoutGlobalScopes()->value('unsubscribe_token');
        $this->assertStringContainsString("/api/unsubscribe/$token", $sent->getHeaders()->get('List-Unsubscribe')->getBodyAsString());
        $this->assertSame('List-Unsubscribe=One-Click', $sent->getHeaders()->get('List-Unsubscribe-Post')->getBodyAsString());
        $this->assertStringContainsString('Visit our shop (https://example.com/a)', $sent->getTextBody());
        $this->assertStringContainsString('<h1>', $sent->getHtmlBody());

        // the one-click button posts to the same address and unsubscribes the person
        $this->post("/api/unsubscribe/$token")->assertOk();
        $this->assertFalse((bool) \App\Models\Contact::withoutGlobalScopes()->first()->is_subscribed);
    }

    public function test_plain_text_is_readable(): void
    {
        $t = MessageRenderer::plainText('<style>p{color:red}</style><h2>Sale</h2><p>Save <b>20%</b> &amp; more.<br>Today only</p><ul><li>One</li><li>Two</li></ul><a href="https://x.com">https://x.com</a><img src="a.png">');
        $this->assertSame("Sale\n\nSave 20% & more.\nToday only\n\n- One\n- Two\n\nhttps://x.com", $t);
    }

    public function test_messages_are_spread_out_at_the_companys_sending_speed(): void
    {
        $h = $this->owner();
        foreach (['a', 'b', 'c'] as $n) {
            $this->postJson('/api/contacts', ['email' => "$n@x.com"], $h)->assertCreated();
        }
        $this->putJson('/api/config/email', ['active' => 'default', 'per_minute' => 30], $h)->assertOk()->assertJsonPath('per_minute', 30);
        $id = $this->campaign($h);

        Queue::fake();
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();
        (new \App\Jobs\SendCampaignJob($id))->handle();

        $delays = collect(Queue::pushed(SendMessageJob::class))->map(fn ($j) => (int) now()->diffInSeconds($j->delay, false))->sort()->values()->all();
        $this->assertCount(3, $delays);
        $this->assertEqualsWithDelta([0, 2, 4], $delays, 1); // 30 a minute = one every 2 seconds

        $this->putJson('/api/config/email', ['active' => 'default', 'per_minute' => 0], $h)->assertOk()->assertJsonPath('per_minute', 0);
    }

    public function test_a_sending_campaign_can_be_stopped_and_what_is_waiting_is_skipped(): void
    {
        $h = $this->owner();
        $this->postJson('/api/contacts', ['email' => 'a@x.com'], $h)->assertCreated();
        $id = $this->campaign($h);
        $co = (int) Company::value('id');

        $this->postJson("/api/campaigns/$id/cancel", [], $h)->assertStatus(422); // not sending yet

        Campaign::withoutGlobalScopes()->whereKey($id)->update(['status' => 'sending']);
        EmailLog::withoutGlobalScopes()->create(['company_id' => $co, 'campaign_id' => $id, 'recipient' => 'q@x.com', 'status' => 'queued', 'source' => 'campaign']);
        EmailLog::withoutGlobalScopes()->create(['company_id' => $co, 'campaign_id' => $id, 'recipient' => 's@x.com', 'status' => 'sent', 'source' => 'campaign']);

        $this->postJson("/api/campaigns/$id/cancel", [], $h)->assertOk()->assertJsonPath('status', 'cancelled');
        $this->assertSame(['sent', 'skipped'], EmailLog::withoutGlobalScopes()->orderBy('id')->pluck('status')->sort()->values()->all());
        $this->assertStringContainsString('stopped', EmailLog::withoutGlobalScopes()->where('recipient', 'q@x.com')->value('error'));

        // a late queue worker finds nothing left to send
        SendMessageJob::dispatchSync(EmailLog::withoutGlobalScopes()->where('recipient', 'q@x.com')->value('id'), 'email');
        $this->assertSame('skipped', EmailLog::withoutGlobalScopes()->where('recipient', 'q@x.com')->value('status'));
    }

    public function test_campaigns_and_templates_can_be_duplicated(): void
    {
        $h = $this->owner();
        $id = $this->campaign($h);
        Campaign::withoutGlobalScopes()->whereKey($id)->update(['status' => 'sent']);

        $copy = $this->postJson("/api/campaigns/$id/duplicate", [], $h)->assertCreated()->json();
        $this->assertSame(['Copy of Spring', 'draft', 'Hello'], [$copy['name'], $copy['status'], $copy['subject']]);
        $this->assertNotSame($id, $copy['id']);

        $t = $this->postJson('/api/templates', ['name' => 'Promo', 'type' => 'email', 'subject' => 'S', 'body' => '<p>x</p>'], $h)->assertCreated()->json('id');
        $this->postJson("/api/templates/$t/duplicate", [], $h)->assertCreated()->assertJsonPath('name', 'Copy of Promo');
        $this->assertSame(2, Template::count());
    }

    public function test_the_pre_send_check_counts_who_will_receive_it_and_warns(): void
    {
        $h = $this->owner();
        $this->postJson('/api/contacts', ['email' => 'a@x.com'], $h)->assertCreated();
        $this->postJson('/api/contacts', ['email' => 'b@x.com', 'is_subscribed' => false], $h)->assertCreated();
        $this->postJson('/api/contacts', ['phone' => '+61400000000'], $h)->assertCreated();
        $id = $this->campaign($h, ['subject' => str_repeat('Big sale ', 12), 'body' => '<a href="http://localhost/x">x</a>']);

        $r = $this->getJson("/api/campaigns/$id/check", $h)->assertOk();
        $this->assertSame([3, 1, 2], [$r->json('audience'), $r->json('reach'), $r->json('skipped')]);
        $this->assertCount(3, $r->json('warnings')); // a long subject, a localhost link, no sender address

        $sms = $this->campaign($h, ['type' => 'sms', 'subject' => null, 'body' => str_repeat('a', 200)]);
        $this->assertStringContainsString('2 parts', $this->getJson("/api/campaigns/$sms/check", $h)->json('warnings.0'));
    }

    public function test_contacts_export_as_csv_with_the_list_filters_and_without_spreadsheet_formulas(): void
    {
        $h = $this->owner();
        $this->postJson('/api/contacts', ['first_name' => '=HYPERLINK("http://evil")', 'email' => 'a@x.com'], $h)->assertCreated();
        $this->postJson('/api/contacts', ['first_name' => 'Bob', 'email' => 'b@x.com', 'is_subscribed' => false], $h)->assertCreated();

        $all = $this->get('/api/contacts/export', $h)->assertOk()->streamedContent();
        $this->assertStringContainsString('"First name","Last name",Email,Phone,Subscribed', $all);
        $this->assertStringContainsString("'=HYPERLINK", $all);
        $this->assertStringNotContainsString(',=HYPERLINK', $all);
        $this->assertStringContainsString('a@x.com', $all);

        $unsub = $this->get('/api/contacts/export?status=unsubscribed', $h)->streamedContent();
        $this->assertStringContainsString('b@x.com', $unsub);
        $this->assertStringNotContainsString('a@x.com', $unsub);
    }

    public function test_only_temporary_problems_are_retried(): void
    {
        $is = new ReflectionMethod(SendMessageJob::class, 'isTransient');
        $job = new SendMessageJob(1);
        foreach (['Connection timed out', 'Expected response code "250" but got code "421", with message "421 Try again later"', 'HTTP 429 too many requests', 'Service Unavailable 503'] as $m) {
            $this->assertTrue($is->invoke($job, new \RuntimeException($m)), $m);
        }
        foreach (['Authentication failed: 535 Username and Password not accepted', 'Invalid phone number', 'Mailbox 5001 not found'] as $m) {
            $this->assertFalse($is->invoke($job, new \RuntimeException($m)), $m);
        }
    }
}
