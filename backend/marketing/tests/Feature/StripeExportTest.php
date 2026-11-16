<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Integration;
use App\Models\IntegrationSync;
use App\Models\Person;
use App\Models\PersonSource;
use App\Models\PersonSyncEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Stripe in both directions, like Mailchimp: People are sent to Stripe as customers, and customers are imported as People. */
class StripeExportTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected int $company;

    /** @var array<int, array{0: string, 1: string, 2: array, 3: Request}> method, path and form of every write to Stripe's customers */
    protected array $writes = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.outbound.allow_private' => true]);
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
        $this->company = Company::first()->id;
        Http::swap(new Factory);
    }

    protected function person(string $email, array $more = []): Person
    {
        return tap((new Person)->forceFill(array_merge(['company_id' => $this->company, 'email' => $email, 'is_subscribed' => true, 'source' => 'manual'], $more)))->save();
    }

    /**
     * @param  array<string, array>  $existing  customers already in Stripe, by email
     * @param  int  $writeStatus  Stripe's answer to a create or update
     */
    protected function fakeStripe(array $existing = [], int $writeStatus = 200): void
    {
        $this->writes = [];
        Http::swap(new Factory); // a fresh client: a second fake in one test would not replace the first
        Http::fake(function (Request $r) use ($existing, $writeStatus) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            if ($r->method() === 'POST') {
                $this->writes[] = [$r->method(), $path, $r->data(), $r];
                if ($writeStatus !== 200) {
                    return Http::response(['error' => ['message' => 'Stripe says no.']], $writeStatus);
                }

                return Http::response(['id' => $path === '/v1/customers' ? 'cus_new' : basename($path)]);
            }
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);

            return match (true) {
                $path === '/v1/customers' && isset($q['email']) => Http::response(['data' => isset($existing[$q['email']]) ? [$existing[$q['email']]] : [], 'has_more' => false]),
                $path === '/v1/customers' => Http::response(['data' => [], 'has_more' => false]),
                $path === '/v1/account' => Http::response(['business_profile' => ['name' => 'Acme Pty']]),
                default => Http::response([], 404),
            };
        });
    }

    protected function connect(string $direction = 'export'): void
    {
        $this->postJson('/api/integrations', ['provider' => 'stripe', 'secret_key' => 'rk_test_abcdef123456', 'direction' => $direction], $this->h)->assertStatus(201);
    }

    public function test_stripe_can_send_people_and_needs_no_audience_to_do_it(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key');
        $this->assertTrue($list['stripe']['supports_export']);
        $this->assertStringContainsString('marketing_subscribed', $list['stripe']['export_note']);
        $this->assertStringContainsString('unsubscribed there', $list['mailchimp']['export_note']); // each platform words its own rules

        $this->fakeStripe();
        $this->connect('export'); // no audience chosen: Stripe has none, so nothing is asked for (Mailchimp still insists)
        $this->postJson('/api/integrations', ['provider' => 'mailchimp', 'api_key' => 'abcdef123456-us21', 'direction' => 'export'], $this->h)->assertStatus(422);
    }

    public function test_a_new_person_becomes_a_customer_and_keeps_the_id_stripe_gave_them(): void
    {
        $person = $this->person('Sue@Example.com', ['first_name' => 'Sue', 'last_name' => 'Sub', 'phone' => '+61400']);
        $this->fakeStripe();
        $this->connect();

        $this->assertCount(1, $this->writes);
        [, $path, $form, $request] = $this->writes[0];
        $this->assertSame('/v1/customers', $path);
        $this->assertSame(['email' => 'sue@example.com', 'name' => 'Sue Sub', 'phone' => '+61400', 'metadata' => ['marketing_subscribed' => 'yes']], $form);
        $this->assertStringStartsWith('application/x-www-form-urlencoded', $request->header('Content-Type')[0]); // Stripe takes forms, not JSON
        $this->assertNotEmpty($request->header('Idempotency-Key')); // a retry cannot create a second customer

        $source = PersonSource::withoutGlobalScope('company')->where('person_id', $person->id)->where('platform', 'stripe')->sole();
        $this->assertSame('cus_new', $source->external_id);
        $event = PersonSyncEvent::withoutGlobalScope('company')->where('person_id', $person->id)->where('platform', 'stripe')->sole();
        $this->assertSame(['sent', 'out', 'Created as a customer.'], [$event->action, $event->direction, $event->message]);
        $this->assertSame('success', IntegrationSync::withoutGlobalScope('company')->sole()->status);
    }

    public function test_switching_to_sending_later_sends_everyone_once_and_then_only_what_changed(): void
    {
        $a = $this->person('a@example.com');
        $this->person('b@example.com');
        $this->fakeStripe();
        $this->connect('import'); // connected for import: the first sync sends nothing
        $id = Integration::withoutGlobalScopes()->sole()->id;
        $sent = fn () => IntegrationSync::withoutGlobalScope('company')->latest('id')->first()->exported;

        $this->travel(10)->minutes(); // the people are now older than the few minutes of overlap a sync allows: none of them counts as changed
        $this->putJson("/api/integrations/$id", ['direction' => 'export'], $this->h)->assertOk()->assertJsonPath('settings.export_all', true);
        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202); // an ordinary "Sync now", not a full one
        $this->assertSame(2, $sent()); // people who were already here are sent although none of them changed
        $this->assertArrayNotHasKey('export_all', Integration::withoutGlobalScopes()->sole()->settings); // once

        $this->travel(10)->minutes(); // past the few minutes of overlap every sync allows, so nothing falls between two syncs
        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202);
        $this->assertSame(0, $sent()); // nothing changed since: nothing is sent again

        $this->travel(10)->minutes();
        $a->forceFill(['first_name' => 'Changed'])->save();
        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202);
        $this->assertSame(1, $sent()); // only the one who changed
    }
    public function test_stripe_permissions_are_checked_with_a_request_that_can_never_create_a_customer(): void
    {
        $this->fakeStripe();
        $this->connect('import');
        $id = Integration::withoutGlobalScopes()->sole()->id;
        $check = fn () => $this->postJson("/api/integrations/$id/permissions", [], $this->h);

        $this->fakeStripe([], 400); // Stripe turns the invalid email down: writing is allowed
        $check()->assertOk();
        $this->assertSame(['read_write'], Integration::withoutGlobalScopes()->sole()->settings['granted_scopes']);
        $this->assertSame(['email' => 'not-an-email'], $this->writes[0][2]); // nothing a customer could be made from

        $this->fakeStripe([], 403); // a key that may only read
        $check()->assertOk();
        $this->assertSame([], Integration::withoutGlobalScopes()->sole()->settings['granted_scopes']);
    }
    public function test_the_connection_counts_each_person_sent_once(): void
    {
        $this->person('a@example.com');
        $this->person('b@example.com');
        $this->fakeStripe();
        $this->connect();
        $id = Integration::withoutGlobalScopes()->sole()->id;

        $count = fn () => collect($this->getJson('/api/integrations', $this->h)->json())->firstWhere('id', $id)['exported'];
        $this->assertSame(2, $count());

        $this->postJson("/api/integrations/$id/sync", ['full' => true], $this->h)->assertStatus(202); // everyone is sent again
        $this->assertSame(4, IntegrationSync::withoutGlobalScope('company')->sum('exported')); // four sends in all...
        $this->assertSame(2, $count()); // ...to two people
    }
    public function test_an_existing_customer_is_only_completed_never_overwritten(): void
    {
        $this->person('old@example.com', ['first_name' => 'Ola', 'last_name' => 'New', 'phone' => '+61999', 'is_subscribed' => false]);
        $this->fakeStripe(['old@example.com' => ['id' => 'cus_old', 'name' => 'Ola Stripe-Name', 'phone' => null]]);
        $this->connect();

        $this->assertCount(1, $this->writes);
        [, $path, $form] = $this->writes[0];
        $this->assertSame('/v1/customers/cus_old', $path); // updated, not created again
        $this->assertSame(['phone' => '+61999', 'metadata' => ['marketing_subscribed' => 'no']], $form); // the name Stripe has stays; the blank phone is filled
    }

    public function test_people_without_an_email_are_skipped_and_a_refusal_is_recorded_not_fatal(): void
    {
        $this->person('nomail-phone@example.com', ['email' => null, 'phone' => '+61400']);
        $bad = $this->person('bad@example.com');
        $this->fakeStripe([], 400);
        $this->connect();

        $sync = IntegrationSync::withoutGlobalScope('company')->sole();
        $this->assertSame(['success', 0, 1], [$sync->status, $sync->exported, $sync->export_failed]);
        $event = PersonSyncEvent::withoutGlobalScope('company')->where('person_id', $bad->id)->sole();
        $this->assertSame(['failed', 'Stripe says no.'], [$event->action, $event->message]);
        $this->assertSame(0, PersonSource::withoutGlobalScope('company')->where('platform', 'stripe')->count()); // nothing to remember about a person Stripe refused
    }

    public function test_a_key_that_cannot_write_stops_the_sync_with_a_clear_message(): void
    {
        $this->person('a@example.com');
        $this->fakeStripe([], 403);
        $this->connect();

        $sync = IntegrationSync::withoutGlobalScope('company')->sole();
        $this->assertSame('failed', $sync->status);
        $this->assertStringContainsString('Write access to Customers', $sync->error);
    }

    public function test_both_ways_imports_the_customers_then_sends_the_people(): void
    {
        $this->person('local@example.com');
        Http::fake(function (Request $r) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            $customer = ['id' => 'cus_in', 'email' => 'from-stripe@example.com', 'name' => 'Ann Lee', 'phone' => null];
            if ($r->method() === 'POST') {
                return Http::response(['id' => $path === '/v1/customers' ? 'cus_local' : basename($path)]);
            }
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);

            return match (true) {
                $path === '/v1/customers' && isset($q['email']) => Http::response(['data' => $q['email'] === $customer['email'] ? [$customer] : []]), // Stripe finds the customer that was just imported
                $path === '/v1/customers' => Http::response(['data' => [$customer], 'has_more' => false]),
                $path === '/v1/account' => Http::response(['business_profile' => ['name' => 'Acme Pty']]),
                default => Http::response([], 404),
            };
        });
        $this->connect('both');

        $imported = Person::withoutGlobalScopes()->where('email', 'from-stripe@example.com')->sole();
        $this->assertSame('cus_in', PersonSource::withoutGlobalScope('company')->where('person_id', $imported->id)->where('platform', 'stripe')->sole()->external_id);
        $sync = IntegrationSync::withoutGlobalScope('company')->sole();
        $this->assertSame(2, $sync->exported); // the person who was already here and the one just imported
    }
}
