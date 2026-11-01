<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeparateLogTablesTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_emails_and_texts_have_their_own_tables_and_the_old_shared_one_is_gone(): void
    {
        $this->assertTrue(Schema::hasTable('email_logs'));
        $this->assertTrue(Schema::hasTable('sms_logs'));
        $this->assertFalse(Schema::hasTable('message_logs'));
        $this->assertTrue(Schema::hasColumns('email_logs', ['subject', 'track_token', 'opened_at', 'clicks']));
        $this->assertFalse(Schema::hasColumn('sms_logs', 'subject'));       // texts have no subject, tracking or opens
        $this->assertFalse(Schema::hasColumn('sms_logs', 'track_token'));
        $this->assertTrue(Schema::hasColumn('click_events', 'email_log_id'));
    }

    public function test_each_campaign_writes_to_the_table_of_its_own_channel(): void
    {
        $h = $this->owner();
        $this->postJson('/api/contacts', ['email' => 'ann@x.com', 'phone' => '+61400000001'], $h)->assertCreated();
        $email = $this->postJson('/api/campaigns', ['name' => 'Mail', 'type' => 'email', 'subject' => 'Hi', 'body' => '<p>x</p>'], $h)->json('id');
        $sms = $this->postJson('/api/campaigns', ['name' => 'Text', 'type' => 'sms', 'body' => 'Hello'], $h)->json('id');
        $this->postJson("/api/campaigns/$email/send", [], $h)->assertOk();
        $this->postJson("/api/campaigns/$sms/send", [], $h)->assertOk();

        $this->assertSame(['ann@x.com'], EmailLog::pluck('recipient')->all());
        $this->assertSame(['+61400000001'], SmsLog::pluck('recipient')->all());
        $this->assertSame('log', SmsLog::first()->service);   // SMS test mode
        $this->assertSame('default', EmailLog::first()->service);

        // each campaign's own counts and report come from its own table
        $rows = collect($this->getJson('/api/campaigns', $h)->json('data'))->keyBy('name');
        $this->assertSame([1, 1, 0, 1], [$rows['Mail']['logs_count'], $rows['Mail']['sent_count'], $rows['Mail']['failed_count'], $rows['Mail']['opened_count'] + 1]);
        $this->assertSame([1, 1, 0], [$rows['Text']['logs_count'], $rows['Text']['sent_count'], $rows['Text']['opened_count']]);
        $this->getJson("/api/campaigns/$sms", $h)->assertJsonPath('sent_count', 1)->assertJsonPath('logs_count', 1);
        $this->getJson("/api/reports/campaigns/$sms", $h)->assertOk()->assertJsonPath('stats.sent', 1)->assertJsonPath('stats.recipients', 1)->assertJsonPath('stats.opened', 0);
        $reports = collect($this->getJson('/api/reports/campaigns', $h)->json('data'))->keyBy('name');
        $this->assertSame(1, $reports['Text']['sent_count']);
        $this->assertSame(1, $reports['Mail']['sent_count']);
        $this->get("/api/reports/campaigns/$sms/export", $h)->assertOk();
    }

    public function test_the_history_reads_both_tables_and_each_message_opens_by_channel_and_id(): void
    {
        $h = $this->owner();
        $this->postJson('/api/config/email/test', ['to' => 'me@x.com'], $h)->assertOk();
        $this->postJson('/api/config/sms/test', ['to' => '+977 9806064921'], $h)->assertOk();

        $rows = collect($this->getJson('/api/logs', $h)->assertOk()->json('data'));
        $this->assertEqualsCanonicalizing(['email', 'sms'], $rows->pluck('channel')->all());
        $this->assertSame(['me@x.com'], collect($this->getJson('/api/logs?channel=email', $h)->json('data'))->pluck('recipient')->all());
        $this->assertSame(['+977 9806064921'], collect($this->getJson('/api/logs?channel=sms', $h)->json('data'))->pluck('recipient')->all());

        // ids repeat across the two tables, so the channel is part of the address
        $emailId = $rows->firstWhere('channel', 'email')['id'];
        $smsId = $rows->firstWhere('channel', 'sms')['id'];
        $this->getJson("/api/logs/email/$emailId", $h)->assertOk()->assertJsonPath('recipient', 'me@x.com')->assertJsonPath('channel', 'email');
        $this->getJson("/api/logs/sms/$smsId", $h)->assertOk()->assertJsonPath('recipient', '+977 9806064921')->assertJsonPath('channel', 'sms');
        $this->getJson('/api/logs/fax/1', $h)->assertNotFound();
        $this->assertStringStartsWith(now()->year.'-', $rows->first()['created_at']); // ISO dates, like the models
    }

    public function test_dashboard_and_clicks_still_work_on_the_split_tables(): void
    {
        $h = $this->owner();
        $this->postJson('/api/contacts', ['email' => 'ann@x.com', 'phone' => '+61400000001'], $h)->assertCreated();
        foreach ([['email', ['subject' => 's', 'body' => '<a href="https://example.com/a">a</a>']], ['sms', ['body' => 'hi']]] as [$type, $extra]) {
            $id = $this->postJson('/api/campaigns', ['name' => $type, 'type' => $type] + $extra, $h)->json('id');
            $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();
        }

        $d = $this->getJson('/api/dashboard', $h)->assertOk();
        $d->assertJsonPath('email.sent', 1)->assertJsonPath('sms.sent', 1);
        $this->assertEqualsCanonicalizing(['email', 'sms'], collect($d->json('recent_logs'))->pluck('channel')->all());
        $this->assertSame(2, array_sum($d->json('daily')));

        $log = EmailLog::first();
        $this->get("/api/t/c/{$log->track_token}?".http_build_query(['u' => 'https://example.com/a', 's' => \App\Services\MessageRenderer::sign('https://example.com/a')]))->assertRedirect('https://example.com/a');
        $this->assertSame(1, $log->fresh()->clicks);
        $this->assertSame($log->id, \App\Models\ClickEvent::first()->email_log_id);
        $analytics = $this->getJson('/api/analytics/campaigns', $h)->assertOk();
        $this->assertSame([2, 1, 1], [$analytics->json('totals.sent'), $analytics->json('totals.emails'), $analytics->json('totals.sms')]);
    }
}
