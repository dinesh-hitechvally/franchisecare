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
        $this->assertSame(['sms', '+977 9806064921', 'test', 'log', 'logged'], [$rows[0]['channel'], $rows[0]['recipient'], $rows[0]['source'], $rows[0]['service'], $rows[0]['status']]);
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
        $this->postJson('/api/people', ['email' => 'ann@x.com'], $h)->assertCreated();
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
    public function test_only_log_only_services_are_marked_logged_and_the_default_server_sends_or_fails(): void
    {
        $h = $this->owner();

        // SMS in test mode: logged, never "sent"
        $this->postJson('/api/config/sms/test', ['to' => '+977 9806064921'], $h)->assertOk();
        $this->assertSame('logged', $this->history($h, '?channel=sms')[0]['status']);

        // Default = the server's own mail program. If it cannot be found the send fails with the reason; it never pretends
        config(['mail.default' => 'log', 'mail.server_command' => '/nonexistent/sendmail']);
        $this->getJson('/api/config/email', $h)->assertJsonPath('default_ready', false)->assertJsonPath('active', 'default');
        $res = $this->postJson('/api/config/email/test', ['to' => 'me@x.com'], $h)->assertStatus(422);
        $this->assertStringContainsString("mail program was not found", $res->json('message'));
        $row = $this->history($h, '?channel=email')[0];
        $this->assertSame(['failed', 'default'], [$row['status'], $row['service']]);
        $this->assertStringContainsString('/nonexistent/sendmail', $row['error']);

        // ...and when the program is there, the message is handed to it and counts as sent (not "logged")
        $out = storage_path('framework/testing/fake_sendmail_out.txt');
        @unlink($out);
        config(['mail.server_command' => '"'.PHP_BINARY.'" "'.base_path('tests/Support/fake_sendmail.php').'"']);
        $this->getJson('/api/config/email', $h)->assertJsonPath('default_ready', true)->assertJsonPath('default_via', 'php.exe');
        $this->postJson('/api/config/email/test', ['to' => 'server@x.com'], $h)->assertOk()->assertJsonPath('logged', false);
        $this->assertSame('sent', $this->history($h, '?channel=email&search=server@')[0]['status']);
        $this->assertFileExists($out);
        $this->assertStringContainsString('Test email from Acme', file_get_contents($out));
        $this->assertStringContainsString('server@x.com', file_get_contents($out));

        // "Log only" is its own service: written to the log, marked logged, needs no connection
        $this->putJson('/api/config/email', ['active' => 'log'], $h)->assertOk()->assertJsonPath('active', 'log');
        $this->postJson('/api/config/email/test', ['to' => 'me@x.com'], $h)->assertOk()->assertJsonPath('logged', true);
        $row = $this->history($h, '?channel=email&status=logged')[0];
        $this->assertSame(['logged', 'log'], [$row['status'], $row['service']]);

        // Default on a platform with a real mailer: sent
        config(['mail.default' => 'array']);
        $this->putJson('/api/config/email', ['active' => 'default'], $h)->assertOk();
        $this->getJson('/api/config/email', $h)->assertJsonPath('default_ready', true);
        $this->postJson('/api/config/email/test', ['to' => 'me2@x.com'], $h)->assertOk()->assertJsonPath('logged', false);
        $this->assertSame('sent', $this->history($h, '?channel=email&search=me2')[0]['status']);

        // a connected SMS service: sent
        \Illuminate\Support\Facades\Http::fake(['rest.nexmo.com/*' => \Illuminate\Support\Facades\Http::response(['messages' => [['status' => '0', 'message-id' => 'V1']]])]);
        $this->putJson('/api/config/sms/connections/vonage', ['api_key' => 'k', 'api_secret' => 's', 'from' => 'Acme', 'activate' => true], $h)->assertOk();
        $this->postJson('/api/config/sms/test', ['to' => '+61400000001'], $h)->assertOk();
        $this->assertSame('sent', $this->history($h, '?search=61400000001')[0]['status']);

        // campaign counts and reports keep "logged" apart from "sent"
        $this->putJson('/api/config/sms', ['active' => 'log'], $h)->assertOk();
        $this->postJson('/api/people', ['email' => 'ann@x.com', 'phone' => '+61400000009'], $h)->assertCreated();
        $id = $this->postJson('/api/campaigns', ['name' => 'T', 'type' => 'sms', 'body' => 'Hi'], $h)->json('id');
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();
        $this->getJson("/api/campaigns/$id", $h)->assertJsonPath('sent_count', 0)->assertJsonPath('logged_count', 1)->assertJsonPath('status', 'sent');
        $this->getJson("/api/reports/campaigns/$id", $h)->assertJsonPath('stats.sent', 0)->assertJsonPath('stats.logged', 1);
        $this->getJson('/api/dashboard', $h)->assertJsonPath('sms.sent', 0); // test messages and logged-only texts never count as sent
    }

    public function test_a_campaign_sent_through_a_default_server_without_a_mail_program_fails_instead_of_logging(): void
    {
        $h = $this->owner();
        $this->postJson('/api/people', ['email' => 'ann@x.com'], $h)->assertCreated();
        config(['mail.default' => 'log', 'mail.server_command' => '/nonexistent/sendmail']);
        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'S', 'body' => '<p>x</p>'], $h)->json('id');
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();

        $log = \App\Models\EmailLog::first();
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('mail program was not found', $log->error);
        $this->getJson("/api/campaigns/$id", $h)->assertJsonPath('status', 'failed');
    }
    public function test_errors_are_in_the_history_with_advice_on_what_to_do(): void
    {
        $h = $this->owner();
        $company = Company::first();
        $gmail = 'Failed to authenticate on SMTP server with username "x@gmail.com". Expected response code "235" but got code "535", with message "535-5.7.8 Username and Password not accepted."';

        try {
            SendLog::run($company, 'email', 'a@x.com', 'Hi', '<p>x</p>', 'campaign', fn () => throw new RuntimeException($gmail));
        } catch (RuntimeException) {
        }
        try {
            SendLog::run($company, 'sms', '+977 12', null, 'Hi', 'campaign', fn () => throw new RuntimeException('The To number +977 12 is not a valid phone number. (21211)'));
        } catch (RuntimeException) {
        }

        // the list carries the error itself
        $rows = collect($this->history($h, '?status=failed'))->keyBy('channel');
        $this->assertStringContainsString('535-5.7.8', $rows['email']['error']);
        $this->assertStringContainsString('21211', $rows['sms']['error']);

        // the message opens with the full error and what to do about it
        $email = $this->getJson('/api/logs/email/'.$rows['email']['id'], $h)->assertOk()->assertJsonPath('status', 'failed');
        $this->assertStringContainsString('App password', $email->json('hint'));
        $sms = $this->getJson('/api/logs/sms/'.$rows['sms']['id'], $h)->assertOk();
        $this->assertStringContainsString('international format', $sms->json('hint'));

        // skipped messages say why too; a successful one has no hint
        EmailLog::create(['company_id' => $company->id, 'recipient' => 'u@x.com', 'status' => 'skipped', 'error' => 'Person unsubscribed']);
        $skipped = collect($this->history($h, '?status=skipped'))->first();
        $this->assertSame('Person unsubscribed', $skipped['error']);
        $this->assertStringContainsString('skipped on purpose', $this->getJson('/api/logs/email/'.$skipped['id'], $h)->json('hint'));
        $this->postJson('/api/config/sms/test', ['to' => '+977 9806064921'], $h)->assertOk();
        $ok = collect($this->history($h, '?channel=sms&status=logged'))->first();
        $this->assertNull($this->getJson('/api/logs/sms/'.$ok['id'], $h)->json('hint'));
    }
}
