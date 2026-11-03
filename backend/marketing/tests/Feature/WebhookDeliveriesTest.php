<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookDeliveriesTest extends TestCase
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

    protected function hook(array $h, string $name): int
    {
        return $this->postJson('/api/webhooks', ['name' => $name, 'url' => 'https://8.8.8.8/hook', 'events' => ['*']], $h)->assertCreated()->json('id');
    }

    protected function delivery(int $company, int $hook, string $event, bool $ok, string $response = 'ok', ?string $at = null): void
    {
        WebhookDelivery::withoutGlobalScopes()->create([
            'company_id' => $company, 'webhook_id' => $hook, 'event' => $event, 'status_code' => $ok ? 200 : 500, 'success' => $ok,
            'response' => $response, 'payload' => json_encode(['event' => $event]), 'created_at' => $at ?? now(),
        ]);
    }

    public function test_every_delivery_of_every_webhook_is_listed_with_filters(): void
    {
        $h = $this->owner();
        $co = (int) Company::value('id');
        $crm = $this->hook($h, 'CRM');
        $zap = $this->hook($h, 'Zapier');
        $this->delivery($co, $crm, 'person.created', true, 'OK', '2026-01-10 10:00:00');
        $this->delivery($co, $crm, 'campaign.sent', false, 'Connection refused', '2026-02-10 10:00:00');
        $this->delivery($co, $zap, 'person.created', true, 'OK', '2026-03-10 10:00:00');

        $names = fn (string $q) => collect($this->getJson('/api/webhook-deliveries?'.$q, $h)->assertOk()->json('data'))->map(fn ($d) => $d['webhook_name'].' '.$d['event'])->all();
        $this->assertSame(['Zapier person.created', 'CRM campaign.sent', 'CRM person.created'], $names('')); // newest first
        $this->assertSame(['CRM campaign.sent', 'CRM person.created'], $names("webhook_id=$crm"));
        $this->assertSame(['Zapier person.created', 'CRM person.created'], $names('event=person.created'));
        $this->assertSame(['CRM campaign.sent'], $names('result=failed'));
        $this->assertSame(['Zapier person.created', 'CRM person.created'], $names('result=ok'));
        $this->assertSame(['CRM campaign.sent'], $names('search=refused'));
        $this->assertSame(['CRM campaign.sent'], $names('from=2026-02-01&to=2026-02-28'));

        $first = $this->getJson('/api/webhook-deliveries', $h)->json('data.0');
        $this->assertSame('{"event":"person.created"}', $first['payload']); // what was sent, for the "view request" detail
    }

    public function test_a_deleted_webhooks_deliveries_stay_in_the_log_with_its_name(): void
    {
        $h = $this->owner();
        $co = (int) Company::value('id');
        $hook = $this->hook($h, 'Old CRM');
        $this->delivery($co, $hook, 'person.created', true);
        $this->deleteJson("/api/webhooks/$hook", [], $h)->assertNoContent();

        $this->getJson('/api/webhook-deliveries', $h)->assertOk()->assertJsonPath('data.0.webhook_name', 'Old CRM');
    }

    public function test_another_company_sees_none_of_it(): void
    {
        $h = $this->owner();
        $co = (int) Company::value('id');
        $this->delivery($co, $this->hook($h, 'CRM'), 'person.created', true);

        $other = $this->owner('b@x.com', 'Other');
        $this->getJson('/api/webhook-deliveries', $other)->assertOk()->assertJsonPath('total', 0);
    }
}
