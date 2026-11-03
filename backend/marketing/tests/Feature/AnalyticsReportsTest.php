<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\EmailLog;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsReportsTest extends TestCase
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

    protected function log(string $model, int $company, array $a): void
    {
        $model::withoutGlobalScopes()->create($a + ['company_id' => $company, 'source' => 'campaign', 'service' => 'default', 'recipient' => 'x@x.com', 'body' => 'b', 'status' => 'sent', 'sent_at' => now()]);
    }

    public function test_every_report_answers_with_the_right_numbers_and_only_for_the_own_company(): void
    {
        $h = $this->owner();
        $co = (int) Company::value('id');
        $campaign = (new Campaign)->forceFill(['company_id' => $co, 'name' => 'Spring', 'type' => 'email', 'subject' => 's', 'body' => 'b', 'status' => 'sent']);
        $campaign->save();

        $this->log(EmailLog::class, $co, ['campaign_id' => $campaign->id, 'opened_at' => now(), 'clicks' => 2]);
        $this->log(EmailLog::class, $co, ['campaign_id' => $campaign->id]);
        $this->log(EmailLog::class, $co, ['status' => 'failed', 'error' => 'Connection refused for a@x.com', 'sent_at' => null, 'service' => 'gmail']);
        $this->log(EmailLog::class, $co, ['status' => 'failed', 'error' => 'Connection refused for b@x.com', 'sent_at' => null, 'service' => 'gmail']);
        $this->log(SmsLog::class, $co, ['service' => 'clicksend']);
        $this->log(SmsLog::class, $co, ['status' => 'skipped', 'error' => 'Person unsubscribed', 'sent_at' => null]);

        $email = $this->getJson('/api/analytics/email?days=7', $h)->assertOk();
        $this->assertSame([2, 1, 50.0, 2], [$email->json('totals.sent'), $email->json('totals.opened'), (float) $email->json('totals.open_rate'), $email->json('totals.failed')]);
        $email->assertJsonCount(7, 'daily.sent')->assertJsonPath('campaigns.0.name', 'Spring')->assertJsonPath('campaigns.0.sent', 2);

        $sms = $this->getJson('/api/analytics/sms', $h)->assertOk();
        $this->assertSame([1, 0, 1], [$sms->json('totals.sent'), $sms->json('totals.failed'), $sms->json('totals.skipped')]);
        $sms->assertJsonPath('by_service.0.label', 'clicksend');

        $d = $this->getJson('/api/analytics/delivery', $h)->assertOk();
        $this->assertSame([3, 2, 1], [$d->json('totals.sent'), $d->json('totals.failed'), $d->json('totals.skipped')]);
        // the same error for different addresses is one problem
        $d->assertJsonPath('failed_reasons.0.count', 2)->assertJsonPath('skipped_reasons.0.reason', 'Person unsubscribed');
        $gmail = collect($d->json('by_service'))->firstWhere('service', 'gmail');
        $this->assertSame([2, 0], [$gmail['failed'], $gmail['sent']]);

        $o = $this->getJson('/api/analytics/overview?days=14', $h)->assertOk();
        $this->assertSame(3, $o->json('metrics.messages.value'));
        $o->assertJsonCount(14, 'daily.messages')->assertJsonStructure(['metrics' => ['new_people' => ['value', 'prev', 'change']]]);

        $this->getJson('/api/analytics/automation', $h)->assertOk()->assertJsonPath('totals.started', 0)->assertJsonStructure(['workflows', 'steps', 'step_results', 'daily']);

        // another company sees none of it
        $other = $this->owner('b@x.com', 'Other');
        $this->getJson('/api/analytics/email', $other)->assertJsonPath('totals.sent', 0);
        $this->getJson('/api/analytics/delivery', $other)->assertJsonPath('totals.failed', 0);
    }
}
