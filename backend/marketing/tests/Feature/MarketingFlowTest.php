<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\EmailLog;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MarketingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function register(string $email = 'a@x.com', string $biz = 'Biz A'): string
    {
        return $this->postJson('/api/register', [
            'company_name' => $biz, 'name' => 'Owner', 'email' => $email,
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
    }

    public function test_email_and_sms_campaigns_send_and_log(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->register()];

        $list = $this->postJson('/api/lists', ['name' => 'VIP'], $h)->assertCreated()->json('id');
        $this->postJson('/api/contacts', ['first_name' => 'Ann', 'email' => 'ann@x.com', 'phone' => '+61400000001', 'list_ids' => [$list]], $h)->assertCreated();
        $this->postJson('/api/contacts', ['first_name' => 'Bob', 'email' => 'bob@x.com', 'list_ids' => [$list]], $h)->assertCreated();

        $email = $this->postJson('/api/campaigns', [
            'name' => 'Hello', 'type' => 'email', 'contact_list_id' => $list, 'subject' => 'Hi {{first_name}}',
            'body' => '<p>Hello {{first_name}} <a href="https://example.com">link</a></p>',
        ], $h)->assertCreated()->json('id');

        $this->postJson("/api/campaigns/$email/send", [], $h)->assertOk();

        $logs = EmailLog::withoutGlobalScopes()->get();
        $this->assertCount(2, $logs);
        $this->assertSame('sent', $logs[0]->status);
        $this->assertStringContainsString('Hi Ann', $logs->firstWhere('recipient', 'ann@x.com')->subject);
        $this->assertStringContainsString('/api/t/c/', $logs[0]->body);
        $this->assertStringContainsString('Unsubscribe', $logs[0]->body);

        // SMS: Bob has no phone -> left out (counted), Ann 'logged' (test mode: written to the log, not delivered)
        $sms = $this->postJson('/api/campaigns', ['name' => 'Txt', 'type' => 'sms', 'contact_list_id' => $list, 'body' => 'Hi {{first_name}}'], $h)->json('id');
        $this->postJson("/api/campaigns/$sms/send", [], $h)->assertOk();
        $this->assertSame(1, SmsLog::withoutGlobalScopes()->where('status', 'logged')->count());
        $this->assertSame(0, SmsLog::withoutGlobalScopes()->where('status', 'sent')->count()); // nothing was really sent
        $this->assertSame(0, SmsLog::withoutGlobalScopes()->where('status', 'skipped')->count()); // nobody without a number is written to the message log
        $this->assertSame(1, Campaign::withoutGlobalScopes()->find($sms)->audience_skipped); // but they are counted
        $this->getJson("/api/campaigns/$sms", $h)->assertJsonPath('status', 'sent');

        $this->getJson('/api/logs?channel=email', $h)->assertJsonPath('total', 2);
    }

    public function test_tracking_and_unsubscribe(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->register()];
        $this->postJson('/api/contacts', ['email' => 'ann@x.com'], $h);
        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 's', 'body' => '<a href="https://example.com">x</a>'], $h)->json('id');
        $this->postJson("/api/campaigns/$id/send", [], $h);

        $log = EmailLog::withoutGlobalScopes()->first();
        $this->get("/api/t/o/{$log->track_token}.gif")->assertOk();
        $this->assertNotNull($log->fresh()->opened_at);

        // unsigned redirect must be rejected (open-redirect guard)
        $this->get("/api/t/c/{$log->track_token}?u=https://evil.com&s=bad")->assertNotFound();

        $contact = \App\Models\Contact::withoutGlobalScopes()->first();
        $this->get("/api/unsubscribe/{$contact->unsubscribe_token}")->assertOk();
        $this->assertFalse($contact->fresh()->is_subscribed);
    }

    public function test_tenants_are_isolated(): void
    {
        $a = ['Authorization' => 'Bearer '.$this->register('a@x.com', 'A')];
        $contactId = $this->postJson('/api/contacts', ['email' => 'ann@x.com'], $a)->json('id');

        $this->app['auth']->forgetGuards();
        $b = ['Authorization' => 'Bearer '.$this->register('b@x.com', 'B')];
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/contacts', $b)->assertJsonPath('total', 0);
        $this->putJson("/api/contacts/$contactId", ['email' => 'hack@x.com'], $b)->assertNotFound();
        $this->deleteJson("/api/contacts/$contactId", [], $b)->assertNotFound();
    }

    public function test_visual_design_is_saved_and_sent(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->register()];
        $design = ['settings' => ['bg' => '#fff'], 'blocks' => [['id' => 'a1', 'type' => 'heading', 'text' => 'Hi']]];
        $html = '<html><body><h1>Hello {{first_name}}</h1><a href="{{unsubscribe_url}}">Unsubscribe</a></body></html>';

        $t = $this->postJson('/api/templates', ['name' => 'T', 'type' => 'email', 'subject' => 'S', 'body' => $html, 'design' => $design], $h)->assertCreated();
        $t->assertJsonPath('design.blocks.0.id', 'a1');
        $this->getJson('/api/templates', $h)->assertJsonPath('0.design.settings.bg', '#fff');

        $this->postJson('/api/contacts', ['first_name' => 'Ann', 'email' => 'ann@x.com'], $h);
        $c = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'S', 'body' => $html, 'design' => $design], $h)->assertCreated()->json('id');
        $this->postJson("/api/campaigns/$c/send", [], $h)->assertOk();

        $log = EmailLog::withoutGlobalScopes()->first();
        $this->assertStringContainsString('Hello Ann', $log->body);
        $this->assertStringNotContainsString('{{unsubscribe_url}}', $log->body);
        $this->getJson("/api/campaigns/$c", $h)->assertJsonPath('design.blocks.0.type', 'heading');
    }

    public function test_layouts_are_saved_per_company(): void
    {
        $a = ['Authorization' => 'Bearer '.$this->register('a@x.com', 'A')];
        $id = $this->postJson('/api/layouts', ['name' => 'Mine', 'design' => ['settings' => [], 'blocks' => []]], $a)->assertCreated()->json('id');
        $this->postJson('/api/layouts', ['name' => '', 'design' => ['blocks' => []]], $a)->assertStatus(422);
        $this->getJson('/api/layouts', $a)->assertJsonCount(1)->assertJsonPath('0.name', 'Mine');

        $this->app['auth']->forgetGuards();
        $b = ['Authorization' => 'Bearer '.$this->register('b@x.com', 'B')];
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/layouts', $b)->assertJsonCount(0);
        $this->deleteJson("/api/layouts/$id", [], $b)->assertNotFound();
        $this->app["auth"]->forgetGuards(); // same process: drop the cached user before acting as A again
        $this->deleteJson("/api/layouts/$id", [], $a)->assertNoContent();
    }

    public function test_image_sources_work_without_keys(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->register()];
        $ids = collect($this->getJson('/api/images/providers', $h)->assertOk()->json())->pluck('id')->all();
        $this->assertSame(['all', 'openverse', 'wikimedia'], $ids); // keyed sources hidden until a server key exists
        $this->getJson('/api/images/search?q=cat&provider=pexels', $h)->assertStatus(422);
    }
}
