<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\AutomationHistory;
use App\Models\Contact;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AudienceAutomationTest extends TestCase
{
    use RefreshDatabase;

    /** Same-process requests share the auth guard, so drop its cached user before acting as someone else. */
    protected function call_as(string $token, string $method, string $uri, array $data = [])
    {
        $this->app['auth']->forgetGuards();

        return $this->json($method, $uri, $data, ['Authorization' => 'Bearer '.$token]);
    }

    protected function owner(string $email = 'a@x.com', string $biz = 'Biz A'): string
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/register', [
            'company_name' => $biz, 'name' => 'Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
    }

    protected function login(string $email): string
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/login', ['email' => $email, 'password' => 'password123'])->assertOk()->json('token');
    }

    // -------------------------------------------------------------------- audience

    public function test_tags_segments_and_campaign_to_a_segment(): void
    {
        $t = $this->owner();
        $vip = $this->call_as($t, 'POST', '/api/tags', ['name' => 'VIP', 'color' => '#ff0000'])->assertCreated()->json('id');
        $this->call_as($t, 'POST', '/api/tags', ['name' => 'VIP'])->assertStatus(422); // unique per company

        $a = $this->call_as($t, 'POST', '/api/contacts', ['first_name' => 'Ann', 'email' => 'ann@gmail.com', 'tag_ids' => [$vip]])->assertCreated()->json('id');
        $this->call_as($t, 'POST', '/api/contacts', ['first_name' => 'Bob', 'email' => 'bob@yahoo.com']);
        $this->call_as($t, 'POST', '/api/contacts', ['first_name' => 'Cat', 'email' => 'cat@gmail.com', 'tag_ids' => [$vip]]);

        $this->call_as($t, 'GET', "/api/contacts?tag_id=$vip")->assertJsonPath('total', 2);

        // AND: tag VIP + domain gmail.com + not Cat
        $rules = [['field' => 'tag', 'op' => 'in', 'value' => $vip], ['field' => 'first_name', 'op' => 'not_contains', 'value' => 'Cat']];
        $this->call_as($t, 'POST', '/api/segments/preview', ['match' => 'all', 'rules' => $rules])->assertJsonPath('count', 1)->assertJsonPath('sample.0.email', 'ann@gmail.com');
        // OR widens it
        $this->call_as($t, 'POST', '/api/segments/preview', ['match' => 'any', 'rules' => [['field' => 'email_domain', 'op' => 'equals', 'value' => 'yahoo.com'], ['field' => 'tag', 'op' => 'in', 'value' => $vip]]])->assertJsonPath('count', 3);
        $this->call_as($t, 'POST', '/api/segments/preview', ['match' => 'all', 'rules' => [['field' => 'nonsense', 'op' => 'x']]])->assertStatus(422);

        $seg = $this->call_as($t, 'POST', '/api/segments', ['name' => 'Gmail VIPs', 'match' => 'all', 'rules' => [['field' => 'tag', 'op' => 'in', 'value' => $vip], ['field' => 'email_domain', 'op' => 'equals', 'value' => 'gmail.com']]])->assertCreated()->json('id');
        $this->call_as($t, 'GET', '/api/segments')->assertJsonPath('0.contacts_count', 2);

        $c = $this->call_as($t, 'POST', '/api/campaigns', ['name' => 'To segment', 'type' => 'email', 'segment_id' => $seg, 'subject' => 'Hi', 'body' => '<p>x {{first_name}}</p>'])->assertCreated()->json('id');
        $this->call_as($t, 'POST', "/api/campaigns/$c/send")->assertOk();
        $this->assertSame(['ann@gmail.com', 'cat@gmail.com'], EmailLog::withoutGlobalScopes()->where('campaign_id', $c)->orderBy('recipient')->pluck('recipient')->all());
        $this->assertNotNull($a);
    }

    public function test_subscription_management(): void
    {
        $t = $this->owner();
        $id = $this->call_as($t, 'POST', '/api/contacts', ['email' => 'ann@x.com'])->json('id');

        $this->call_as($t, 'POST', '/api/subscriptions/bulk', ['ids' => [$id], 'subscribed' => false])->assertJsonPath('changed', 1);
        $this->call_as($t, 'POST', '/api/subscriptions/bulk', ['ids' => [$id], 'subscribed' => false])->assertJsonPath('changed', 0);
        $this->call_as($t, 'GET', '/api/subscriptions?status=unsubscribed')
            ->assertJsonPath('summary.unsubscribed', 1)->assertJsonPath('contacts.total', 1);
        $this->assertFalse((bool) Contact::withoutGlobalScopes()->find($id)->is_subscribed);
        $this->assertNull(Contact::withoutGlobalScopes()->find($id)->subscribed_at);

        $this->call_as($t, 'GET', '/api/contacts?status=unsubscribed')->assertJsonPath('total', 1);
        $this->call_as($t, 'GET', '/api/contacts?status=subscribed')->assertJsonPath('total', 0);

        // an unsubscribed contact is skipped by campaigns
        $c = $this->call_as($t, 'POST', '/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 's', 'body' => '<p>x</p>'])->json('id');
        $this->call_as($t, 'POST', "/api/campaigns/$c/send");
        $this->assertSame(0, EmailLog::withoutGlobalScopes()->count()); // nothing is written to the message log for them
        $this->assertSame(0, Campaign::withoutGlobalScopes()->find($c)->audience_skipped); // opted out is not "skipped"

        $this->call_as($t, 'POST', '/api/subscriptions/bulk', ['ids' => [$id], 'subscribed' => true])->assertJsonPath('changed', 1);
        $this->assertTrue((bool) Contact::withoutGlobalScopes()->find($id)->is_subscribed);
        $this->assertNotNull(Contact::withoutGlobalScopes()->find($id)->subscribed_at);
    }

    // -------------------------------------------------------------------- reports

    public function test_campaign_report_with_clicks_and_export(): void
    {
        $t = $this->owner();
        $this->call_as($t, 'POST', '/api/contacts', ['email' => 'ann@x.com']);
        $c = $this->call_as($t, 'POST', '/api/campaigns', ['name' => 'Rep', 'type' => 'email', 'subject' => 's', 'body' => '<a href="https://example.com/a">a</a>'])->json('id');
        $this->call_as($t, 'POST', "/api/campaigns/$c/send");

        $log = EmailLog::withoutGlobalScopes()->first();
        $this->get("/api/t/c/{$log->track_token}?".http_build_query(['u' => 'https://example.com/a', 's' => \App\Services\MessageRenderer::sign('https://example.com/a')]))->assertRedirect('https://example.com/a');

        $r = $this->call_as($t, 'GET', "/api/reports/campaigns/$c")->assertOk();
        $r->assertJsonPath('stats.sent', 1)->assertJsonPath('stats.opened', 1)->assertJsonPath('stats.open_rate', 100)
            ->assertJsonPath('stats.clicks', 1)->assertJsonPath('top_links.0.url', 'https://example.com/a');
        $this->call_as($t, 'GET', '/api/reports/campaigns')->assertJsonPath('data.0.click_rate', 100);

        $csv = $this->call_as($t, 'GET', "/api/reports/campaigns/$c/export")->assertOk()->streamedContent();
        $this->assertStringContainsString('ann@x.com', $csv);

        $this->call_as($t, 'GET', '/api/analytics/campaigns?days=7')->assertJsonPath('totals.emails', 1)->assertJsonCount(7, 'daily.sent');
        $this->call_as($t, 'GET', '/api/analytics/contacts')->assertJsonPath('total', 1);
        $this->call_as($t, 'GET', '/api/analytics/conversions')->assertOk();
    }

    public function test_scheduled_campaigns_can_be_cancelled(): void
    {
        $t = $this->owner();
        $c = $this->call_as($t, 'POST', '/api/campaigns', ['name' => 'Later', 'type' => 'sms', 'body' => 'hi', 'scheduled_at' => now()->addDay()->toIso8601String()])->json('id');
        $this->call_as($t, 'POST', "/api/campaigns/$c/send")->assertJsonPath('status', 'scheduled');
        $this->call_as($t, 'GET', '/api/campaigns?status=scheduled')->assertJsonPath('total', 1);
        $this->call_as($t, 'POST', "/api/campaigns/$c/unschedule")->assertJsonPath('status', 'draft');
        $this->call_as($t, 'GET', '/api/campaigns?type=email')->assertJsonPath('total', 0);
    }

    // -------------------------------------------------------------------- forms + landing pages

    public function test_hosted_form_signup_creates_contact_with_list_and_tag(): void
    {
        $t = $this->owner();
        $list = $this->call_as($t, 'POST', '/api/lists', ['name' => 'Web'])->json('id');
        $tag = $this->call_as($t, 'POST', '/api/tags', ['name' => 'web-signup'])->json('id');
        $form = $this->call_as($t, 'POST', '/api/forms', ['name' => 'Footer', 'list_ids' => [$list], 'tag_ids' => [$tag], 'success_message' => 'Welcome!'])->assertCreated();
        $slug = $form->json('slug');

        $page = $this->get("/f/$slug")->assertOk();
        $this->assertStringContainsString('script-src \'nonce-', $page->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('name="email"', $page->getContent());

        $this->postJson("/api/public/forms/$slug", ['email' => 'New@Site.com', 'first_name' => 'Nia'])->assertOk()->assertJsonPath('message', 'Welcome!');
        $c = Contact::withoutGlobalScopes()->where('email', 'new@site.com')->firstOrFail();
        $this->assertSame('form', $c->source);
        $this->assertSame([$list], $c->lists()->pluck('contact_lists.id')->all());
        $this->assertSame([$tag], $c->tags()->pluck('tags.id')->all());

        // same person again: no duplicate; an opt-out is never reversed by a form
        $c->forceFill(['is_subscribed' => false])->save();
        $this->postJson("/api/public/forms/$slug", ['email' => 'new@site.com'])->assertOk();
        $this->assertSame(1, Contact::withoutGlobalScopes()->count());
        $this->assertFalse((bool) $c->fresh()->is_subscribed);

        $this->postJson("/api/public/forms/$slug", ['email' => 'not-an-email'])->assertStatus(422);
        $this->postJson("/api/public/forms/$slug", ['email' => 'bot@x.com', 'website' => 'spam'])->assertOk(); // honeypot
        $this->assertNull(Contact::withoutGlobalScopes()->where('email', 'bot@x.com')->first());

        $this->call_as($t, 'GET', '/api/analytics/conversions')->assertJsonPath('forms.0.submissions', 2)->assertJsonPath('forms.0.views', 1);
    }

    public function test_landing_page_is_public_only_when_published_and_embeds_the_form(): void
    {
        $t = $this->owner();
        $form = $this->call_as($t, 'POST', '/api/forms', ['name' => 'F'])->json('id');
        $p = $this->call_as($t, 'POST', '/api/landing-pages', [
            'name' => 'Promo', 'html' => '<html><body><h1>Hi from {{company_name}}</h1><script>alert(1)</script></body></html>', 'form_id' => $form,
        ])->assertCreated();

        $this->get('/p/'.$p->json('slug'))->assertNotFound(); // not published yet
        $this->call_as($t, 'PUT', '/api/landing-pages/'.$p->json('id'), ['name' => 'Promo', 'html' => $p->json('html'), 'form_id' => $form, 'published' => true])->assertOk();

        $res = $this->get('/p/'.$p->json('slug'))->assertOk();
        $this->assertStringContainsString('Hi from Biz A', $res->getContent());
        $this->assertStringContainsString('fc-form', $res->getContent());
        // injected scripts have no nonce, so the browser's CSP refuses to run them
        $this->assertStringNotContainsString("'unsafe-inline'; font-src *; script-src 'unsafe-inline'", $res->headers->get('Content-Security-Policy'));
        $this->call_as($t, 'GET', '/api/analytics/conversions')->assertJsonPath('pages.0.views', 1)->assertJsonPath('pages.0.form', 'F');
        $this->call_as($t, 'GET', '/api/landing-pages')->assertJsonPath('0.form.name', 'F');
    }

    // -------------------------------------------------------------------- automation

    public function test_workflow_sends_waits_and_resumes(): void
    {
        $t = $this->owner();
        $tpl = $this->call_as($t, 'POST', '/api/templates', ['name' => 'Welcome', 'type' => 'email', 'subject' => 'Hi {{first_name}}', 'body' => '<p>Welcome {{first_name}}</p>'])->json('id');
        $tag = $this->call_as($t, 'POST', '/api/tags', ['name' => 'welcomed'])->json('id');

        $wf = $this->call_as($t, 'POST', '/api/workflows', ['name' => 'Welcome flow', 'trigger_type' => 'contact_created', 'steps' => [
            ['type' => 'send_email', 'template_id' => $tpl],
            ['type' => 'wait', 'amount' => 2, 'unit' => 'days'],
            ['type' => 'add_tag', 'tag_id' => $tag],
        ]])->assertCreated()->json('id');

        // draft workflows do nothing
        $this->call_as($t, 'POST', '/api/contacts', ['first_name' => 'Early', 'email' => 'early@x.com']);
        $this->assertSame(0, EmailLog::withoutGlobalScopes()->count());

        $this->call_as($t, 'POST', "/api/workflows/$wf/toggle")->assertJsonPath('status', 'active');
        $id = $this->call_as($t, 'POST', '/api/contacts', ['first_name' => 'Ann', 'email' => 'ann@x.com'])->json('id');

        // email went out immediately, using the contact's name
        $log = EmailLog::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('sent', $log->status);
        $this->assertSame('Hi Ann', $log->subject);
        $this->assertNotNull($log->workflow_id);

        // waiting: tag not applied yet
        $this->assertSame(0, Contact::withoutGlobalScopes()->find($id)->tags()->count());
        $this->call_as($t, 'GET', '/api/automation/runs?status=running')->assertJsonPath('total', 1);

        Artisan::call('automation:process'); // not due yet
        $this->assertSame(0, Contact::withoutGlobalScopes()->find($id)->tags()->count());

        $this->travel(3)->days();
        Artisan::call('automation:process');
        $this->assertSame([$tag], Contact::withoutGlobalScopes()->find($id)->tags()->pluck('tags.id')->all());
        $this->call_as($t, 'GET', '/api/automation/runs?status=completed')->assertJsonPath('total', 1);

        $steps = AutomationHistory::withoutGlobalScopes()->orderBy('id')->pluck('step_type')->all();
        $this->assertContains('wait', $steps);
        $this->assertContains('add_tag', $steps);
        $this->assertContains('end', $steps);
        $this->call_as($t, 'GET', '/api/automation/history')->assertOk()->assertJsonPath('data.0.step_type', 'end');
        $this->call_as($t, 'GET', '/api/automation/triggers')->assertJsonPath('0.workflows.0.name', 'Welcome flow');
    }

    public function test_workflow_validation_and_trigger_filters(): void
    {
        $t = $this->owner();
        $this->call_as($t, 'POST', '/api/workflows', ['name' => 'Bad', 'trigger_type' => 'contact_created', 'steps' => [['type' => 'send_email']]])->assertStatus(422);
        $this->call_as($t, 'POST', '/api/workflows', ['name' => 'Bad', 'trigger_type' => 'nope', 'steps' => []])->assertStatus(422);

        $list = $this->call_as($t, 'POST', '/api/lists', ['name' => 'VIP'])->json('id');
        $other = $this->call_as($t, 'POST', '/api/lists', ['name' => 'Other'])->json('id');
        $tag = $this->call_as($t, 'POST', '/api/tags', ['name' => 'joined-vip'])->json('id');
        $wf = $this->call_as($t, 'POST', '/api/workflows', ['name' => 'VIP', 'trigger_type' => 'list_joined', 'trigger_config' => ['list_id' => $list], 'steps' => [['type' => 'add_tag', 'tag_id' => $tag]]])->json('id');
        $this->call_as($t, 'POST', "/api/workflows/$wf/toggle");

        $a = $this->call_as($t, 'POST', '/api/contacts', ['email' => 'a@x.com', 'list_ids' => [$other]])->json('id');
        $b = $this->call_as($t, 'POST', '/api/contacts', ['email' => 'b@x.com', 'list_ids' => [$list]])->json('id');
        $this->assertSame(0, Contact::withoutGlobalScopes()->find($a)->tags()->count()); // wrong list: not triggered
        $this->assertSame(1, Contact::withoutGlobalScopes()->find($b)->tags()->count());
    }

    // -------------------------------------------------------------------- system

    public function test_webhooks_are_signed_and_ssrf_protected(): void
    {
        $t = $this->owner();
        $this->call_as($t, 'POST', '/api/webhooks', ['name' => 'Local', 'url' => 'http://127.0.0.1/hook', 'events' => ['*']])->assertStatus(422);
        $this->call_as($t, 'POST', '/api/webhooks', ['name' => 'Meta', 'url' => 'http://169.254.169.254/latest', 'events' => ['*']])->assertStatus(422);
        $this->call_as($t, 'POST', '/api/webhooks', ['name' => 'Bad', 'url' => 'ftp://93.184.216.34/x', 'events' => ['*']])->assertStatus(422);

        Http::fake(['93.184.216.34/*' => Http::response('ok', 200)]);
        $hook = $this->call_as($t, 'POST', '/api/webhooks', ['name' => 'Zap', 'url' => 'https://93.184.216.34/hook', 'events' => ['contact.created']])->assertCreated();
        $secret = $hook->json('secret');
        $this->assertStringStartsWith('whsec_', $secret);
        $this->call_as($t, 'GET', '/api/webhooks')->assertJsonMissingPath('0.secret'); // shown once only

        $this->call_as($t, 'POST', '/api/contacts', ['email' => 'ann@x.com'])->assertCreated();

        Http::assertSent(function ($req) use ($secret) {
            return $req->url() === 'https://93.184.216.34/hook'
                && $req->header('X-Webhook-Event')[0] === 'contact.created'
                && $req->header('X-Webhook-Signature')[0] === 'sha256='.hash_hmac('sha256', $req->body(), $secret)
                && json_decode($req->body(), true)['data']['email'] === 'ann@x.com';
        });
        $this->call_as($t, 'GET', '/api/webhooks/'.$hook->json('id').'/deliveries')->assertJsonPath('data.0.success', true);
        $this->call_as($t, 'POST', '/api/webhooks/'.$hook->json('id').'/test')->assertJsonPath('event', 'ping');
    }

    public function test_api_keys_work_on_v1_only(): void
    {
        $t = $this->owner();
        $key = $this->call_as($t, 'POST', '/api/api-keys', ['name' => 'Zapier'])->assertCreated()->json('key');
        $this->call_as($t, 'GET', '/api/api-keys')->assertJsonPath('0.name', 'Zapier')->assertJsonMissingPath('0.key');

        $this->call_as($key, 'POST', '/api/v1/contacts', ['email' => 'Api@X.com', 'first_name' => 'Api', 'tags' => ['from-api'], 'lists' => ['API list']])
            ->assertCreated()->assertJsonPath('email', 'api@x.com')->assertJsonPath('tags.0.name', 'from-api');
        $this->call_as($key, 'POST', '/api/v1/contacts', ['email' => 'api@x.com', 'last_name' => 'Two'])->assertOk(); // update, not duplicate
        $this->call_as($key, 'GET', '/api/v1/contacts?email=api@x.com')->assertJsonPath('last_name', 'Two');
        $this->assertSame('api', Contact::withoutGlobalScopes()->first()->source);

        // a key must not open the dashboard API
        $this->call_as($key, 'GET', '/api/contacts')->assertForbidden();
        $this->call_as($key, 'GET', '/api/api-keys')->assertForbidden();

        $id = $this->call_as($t, 'GET', '/api/api-keys')->json('0.id');
        $this->call_as($t, 'DELETE', "/api/api-keys/$id")->assertNoContent();
        $this->call_as($key, 'GET', '/api/v1/contacts?email=api@x.com')->assertUnauthorized();
    }

    public function test_users_belong_to_their_company_and_share_its_data(): void
    {
        $a = $this->owner('a@x.com', 'Company A');
        $this->call_as($a, 'POST', '/api/contacts', ['email' => 'lead@a.com'])->assertCreated();

        // a user created inside the platform joins the creator's company; the request can't choose another one
        $other = $this->owner('b@y.com', 'Company B');
        $companyB = User::where('email', 'b@y.com')->value('company_id');
        $this->call_as($a, 'POST', '/api/users', ['name' => 'Sam', 'email' => 'sam@a.com', 'password' => 'password123', 'company_id' => $companyB])->assertCreated();
        $sam = User::where('email', 'sam@a.com')->firstOrFail();
        $this->assertSame(User::where('email', 'a@x.com')->value('company_id'), $sam->company_id);
        $this->assertNotSame($companyB, $sam->company_id);

        // Sam sees Company A's data, and can do everything (no roles): contacts, settings, webhooks, users
        $s = $this->login('sam@a.com');
        $this->call_as($s, 'GET', '/api/contacts')->assertJsonPath('total', 1)->assertJsonPath('data.0.email', 'lead@a.com');
        $this->call_as($s, 'POST', '/api/contacts', ['email' => 'by-sam@a.com'])->assertCreated();
        $this->call_as($s, 'GET', '/api/config/email')->assertOk();
        $this->call_as($s, 'GET', '/api/webhooks')->assertOk();
        $this->call_as($s, 'GET', '/api/users')->assertOk()->assertJsonCount(2);
        $this->call_as($s, 'GET', '/api/me')->assertJsonMissingPath('user.role')->assertJsonPath('company.name', 'Company A');

        // ...and Company A's owner sees what Sam added
        $this->call_as($a, 'GET', '/api/contacts')->assertJsonPath('total', 2);

        // Company B sees none of it, and can't see or touch Company A's people
        $this->call_as($other, 'GET', '/api/contacts')->assertJsonPath('total', 0);
        $this->call_as($other, 'GET', '/api/users')->assertJsonCount(1);
        $this->call_as($other, 'PUT', "/api/users/{$sam->id}", ['name' => 'x', 'email' => 'sam@a.com'])->assertNotFound();
        $this->call_as($other, 'DELETE', "/api/users/{$sam->id}")->assertNotFound();

        // you can't delete yourself; anyone else in your own company can be removed and loses access
        $this->call_as($s, 'DELETE', "/api/users/{$sam->id}")->assertStatus(422);
        $aId = User::where('email', 'a@x.com')->value('id');
        $this->call_as($s, 'DELETE', "/api/users/$aId")->assertNoContent();
        $this->call_as($a, 'GET', '/api/contacts')->assertUnauthorized();
    }

    public function test_activity_log_records_changes_but_not_reads_or_bodies(): void
    {
        $t = $this->owner();
        $this->call_as($t, 'POST', '/api/contacts', ['email' => 'secret-body@x.com', 'first_name' => 'Zed']);
        $this->call_as($t, 'POST', '/api/tags', ['name' => 'Hot']);
        $this->call_as($t, 'GET', '/api/contacts');

        $logs = $this->call_as($t, 'GET', '/api/activity-logs')->assertOk()->json('data');
        $actions = array_column($logs, 'action');
        $this->assertContains('contact.created', $actions);
        $this->assertContains('tag.created', $actions);
        $this->assertCount(2, array_filter($actions, fn ($a) => $a !== 'login')); // the GET was not logged
        $this->assertStringNotContainsString('secret-body@x.com', json_encode($logs));
        $this->call_as($t, 'GET', '/api/activity-logs?action=tag')->assertJsonPath('total', 1);
    }

    public function test_email_configuration(): void
    {
        $t = $this->owner();
        $this->call_as($t, 'PUT', '/api/config/email/connections/custom', ['host' => '127.0.0.1', 'port' => 25])->assertStatus(422); // private target blocked
        $this->call_as($t, 'PUT', '/api/config/email/connections/custom', [])->assertStatus(422); // host required
        $this->call_as($t, 'PUT', '/api/config/email/connections/nonsense', ['host' => '93.184.216.34'])->assertNotFound();

        $this->call_as($t, 'PUT', '/api/config/email/connections/custom', ['host' => '93.184.216.34', 'port' => 587, 'username' => 'u', 'password' => 's3cret'])
            ->assertOk()->assertJsonPath('connections.custom.password_set', true)->assertJsonMissingPath('connections.custom.password');
        // blank password keeps the stored one
        $this->call_as($t, 'PUT', '/api/config/email/connections/custom', ['host' => '93.184.216.34', 'username' => 'u', 'password' => ''])->assertJsonPath('connections.custom.password_set', true);

        $this->call_as($t, 'PUT', '/api/config/email', ['active' => 'custom', 'from_email' => 'hi@biz.com', 'from_name' => 'Biz'])->assertOk()->assertJsonPath('active', 'custom');
        $this->call_as($t, 'PUT', '/api/config/email', ['active' => 'default'])->assertOk()->assertJsonPath('active', 'default');
        $this->call_as($t, 'POST', '/api/config/email/test', ['to' => 'me@x.com'])->assertOk(); // default mailer = array in tests
        $this->call_as($t, 'POST', '/api/config/sms/test', ['to' => '+61400000000'])->assertOk(); // log driver
    }
}
