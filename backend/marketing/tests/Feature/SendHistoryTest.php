<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmailLog;
use App\Services\SendLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SendHistoryTest extends TestCase
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

    protected function history(array $h, string $query = ''): array
    {
        return $this->getJson('/api/logs'.$query, $h)->assertOk()->json('data');
    }

    public function test_a_test_sms_in_test_mode_is_recorded_in_the_send_history(): void
    {
        $h = $this->owner();
        $this->postJson('/api/config/sms/test', ['to' => '+977 9806064921'], $h)->assertOk();

        $rows = $this->history($h);
        $this->assertCount(1, $rows);
        $this->assertSame(['sms', '+977 9806064921', 'test', 'log', 'sent'], [$rows[0]['channel'], $rows[0]['recipient'], $rows[0]['source'], $rows[0]['service'], $rows[0]['status']]);
        $this->assertStringContainsString('Test SMS from Acme', $this->getJson('/api/logs/sms/'.$rows[0]['id'], $h)->assertOk()->json('body'));
    }

    public function test_test_emails_are_recorded_with_the_service_that_carried_them(): void
    {
        $h = $this->owner();
        $this->postJson('/api/config/email/test', ['to' => 'me@x.com'], $h)->assertOk();

        $rows = $this->history($h, '?channel=email');
        $this->assertSame(['me@x.com', 'test', 'default', 'sent'], [$rows[0]['recipient'], $rows[0]['source'], $rows[0]['service'], $rows[0]['status']]);
        $this->assertSame('Test email from Acme', $rows[0]['subject']);
    }

    public function test_a_failed_send_is_recorded_with_its_error_and_still_thrown(): void
    {
        $h = $this->owner();
        $company = Company::first();

        try {
            SendLog::run($company, 'sms', '+61400000000', null, 'Hi', 'test', fn () => throw new RuntimeException('Bad credentials'));
            $this->fail('the error should be thrown again');
        } catch (RuntimeException $e) {
            $this->assertSame('Bad credentials', $e->getMessage());
        }
        $row = $this->history($h, '?status=failed')[0];
        $this->assertSame(['test', 'Bad credentials'], [$row['source'], $row['error']]);
    }

    public function test_campaign_sends_and_campaign_test_sends_are_both_listed_but_only_real_ones_are_counted(): void
    {
        $h = $this->owner();
        $this->postJson('/api/contacts', ['email' => 'ann@x.com'], $h)->assertCreated();
        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'Hello', 'body' => '<p>Hi</p>'], $h)->json('id');
        $this->postJson("/api/campaigns/$id/test", ['to' => 'test@x.com'], $h)->assertOk();
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();

        $all = $this->history($h);
        $this->assertEqualsCanonicalizing(['campaign', 'test'], array_column($all, 'source'));
        $campaign = collect($all)->firstWhere('source', 'campaign');
        $this->assertSame(['ann@x.com', 'default', 'sent'], [$campaign['recipient'], $campaign['service'], $campaign['status']]);

        // the test message is in the history but not in the campaign's report or the dashboard numbers
        $this->assertSame(1, $this->getJson("/api/reports/campaigns/$id", $h)->json('stats.sent'));
        $this->assertSame(1, $this->getJson('/api/dashboard', $h)->json('email.sent'));
        $this->assertSame(1, EmailLog::count());                                  // the statistics view
        $this->assertSame(2, EmailLog::withoutGlobalScope('real')->count());     // everything
    }

    public function test_staff_notifications_for_form_submissions_are_listed(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'Contact us', 'notify_emails' => ['boss@x.com']], $h)->assertCreated()->json();
        $this->postJson('/api/public/forms/'.$form['slug'], ['email' => 'p@x.com'])->assertOk();

        $row = collect($this->history($h))->firstWhere('source', 'notification');
        $this->assertSame(['boss@x.com', 'New submission: Contact us', 'sent'], [$row['recipient'], $row['subject'], $row['status']]);
    }

    public function test_the_history_can_be_filtered_by_source_service_text_and_date(): void
    {
        $h = $this->owner();
        $this->postJson('/api/config/sms/test', ['to' => '+61400000001'], $h)->assertOk();
        $this->postJson('/api/config/email/test', ['to' => 'me@x.com'], $h)->assertOk();

        $this->assertCount(2, $this->history($h));
        $this->assertCount(2, $this->history($h, '?source=test'));
        $this->assertCount(0, $this->history($h, '?source=campaign'));
        $this->assertCount(1, $this->history($h, '?service=log'));
        $this->assertCount(1, $this->history($h, '?service=default&channel=email'));
        $this->assertCount(1, $this->history($h, '?search=Test%20email'));      // searches the subject too
        $this->assertCount(1, $this->history($h, '?search=61400000001'));
        $this->assertCount(0, $this->history($h, '?from=2999-01-01'));
        $this->assertCount(1, $this->history($h, '?per_page=5&channel=sms'));
    }

    public function test_each_company_only_sees_its_own_history(): void
    {
        $h = $this->owner();
        $this->postJson('/api/config/sms/test', ['to' => '+61400000001'], $h)->assertOk();
        $id = $this->history($h)[0]['id'];

        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', ['company_name' => 'Other', 'name' => 'P', 'email' => 'p@y.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->json('token');
        $h2 = ['Authorization' => 'Bearer '.$token];

        $this->assertSame([], $this->history($h2));
        $this->getJson("/api/logs/sms/$id", $h2)->assertNotFound();
    }
}
