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

/** Klaviyo in both directions, like Mailchimp: import (Klaviyo → People), send (People → a Klaviyo list) or both. */
class KlaviyoExportTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected int $company;

    /** @var array<int, Request> every request made to Klaviyo, in order */
    protected array $calls = [];

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
     * @param  array<string, string>  $known  profiles Klaviyo already has, as email => id
     * @param  array<string, int>  $status  the status to answer a write with, by path (for example '/api/profile-import/' => 400)
     */
    protected function fakeKlaviyo(array $known = [], array $status = []): void
    {
        $this->calls = [];
        $n = 0;
        Http::swap(new Factory); // a fresh client: a second fake in one test would not replace the first
        Http::fake(function (Request $r) use ($known, $status, &$n) {
            $this->calls[] = $r;
            $path = parse_url($r->url(), PHP_URL_PATH);
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);
            if (isset($status[$path])) {
                return Http::response(['errors' => [['detail' => 'Klaviyo says no.']]], $status[$path]);
            }

            return match (true) {
                $path === '/api/profile-import/' => Http::response(['data' => ['type' => 'profile', 'id' => 'PROF'.(++$n)]], 201),
                str_starts_with($path, '/api/profile-subscription-bulk-') => Http::response('', 202),
                $path === '/api/profiles/' && isset($q['filter']) => (function () use ($q, $known) {
                    preg_match('/equals\(email,"(.*)"\)/', $q['filter'], $m);

                    return Http::response(['data' => isset($known[$m[1]]) ? [['type' => 'profile', 'id' => $known[$m[1]]]] : []]);
                })(),
                $path === '/api/profiles/' => Http::response(['data' => [], 'links' => ['next' => null]]),
                $path === '/api/accounts/' => Http::response(['data' => [['attributes' => ['contact_information' => ['organization_name' => 'Acme Klaviyo']]]]]),
                $path === '/api/lists/' && ! isset($q['page']['cursor']) => Http::response(['data' => [['id' => 'L1', 'attributes' => ['name' => 'Newsletter']]], 'links' => ['next' => 'https://a.klaviyo.com/api/lists/?page%5Bcursor%5D=c2']]),
                $path === '/api/lists/' => Http::response(['data' => [['id' => 'L2', 'attributes' => ['name' => 'VIPs']]], 'links' => ['next' => null]]),
                $path === '/api/lists/LIST1/' => Http::response(['data' => ['id' => 'LIST1']]),
                $path === '/api/lists/LIST1/profiles/' => Http::response(['data' => [['id' => 'KL-9', 'attributes' => ['email' => 'in-list@example.com', 'first_name' => 'Ina', 'subscriptions' => ['email' => ['marketing' => ['consent' => 'SUBSCRIBED']]]]]], 'links' => ['next' => null]]),
                default => Http::response([], 404),
            };
        });
    }

    protected function connect(string $direction = 'export', array $extra = [], int $status = 201)
    {
        return $this->postJson('/api/integrations', array_merge(['provider' => 'klaviyo', 'private_key' => 'pk_test_abcdef123456', 'direction' => $direction], $extra), $this->h)->assertStatus($status);
    }

    /** @return array<int, Request> */
    protected function to(string $path, string $method = 'POST'): array
    {
        return array_values(array_filter($this->calls, fn ($r) => $r->method() === $method && parse_url($r->url(), PHP_URL_PATH) === $path));
    }

    public function test_klaviyo_can_send_people_and_asks_for_the_list_to_send_them_to(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key');
        $this->assertSame([true, true, 'list'], [$list['klaviyo']['supports_export'], $list['klaviyo']['has_audiences'], $list['klaviyo']['audience_noun']]);
        $this->assertTrue(collect($list['klaviyo']['fields'])->firstWhere('key', 'export_audience_id')['export_required']);

        $this->fakeKlaviyo();
        $r = $this->connect('export', [], 422); // sending needs a list, like Mailchimp needs an audience
        $this->assertStringContainsString('choose the list to send them to (List ID)', $r->json('message'));
        $this->connect('export', ['export_audience_id' => 'LIST1']);
        $this->connect('import', ['private_key' => 'pk_test_another999999'], 201); // importing needs no list
    }

    public function test_a_subscribed_person_becomes_a_profile_and_is_subscribed_to_the_list(): void
    {
        $person = $this->person('Sue@Example.com', ['first_name' => 'Sue', 'last_name' => 'Sub', 'phone' => '+61 400 111 222']);
        $this->fakeKlaviyo();
        $this->connect('export', ['export_audience_id' => 'LIST1']);

        $import = $this->to('/api/profile-import/');
        $this->assertCount(1, $import);
        $this->assertSame(['email' => 'sue@example.com', 'first_name' => 'Sue', 'last_name' => 'Sub', 'phone_number' => '+61400111222'], $import[0]->data()['data']['attributes']);
        $this->assertSame('Klaviyo-API-Key pk_test_abcdef123456', $import[0]->header('Authorization')[0]);
        $this->assertSame('application/vnd.api+json', $import[0]->header('Content-Type')[0]);

        $job = $this->to('/api/profile-subscription-bulk-create-jobs/')[0]->data()['data'];
        $this->assertSame('LIST1', $job['relationships']['list']['data']['id']);
        $this->assertSame([['type' => 'profile', 'attributes' => ['email' => 'sue@example.com', 'subscriptions' => ['email' => ['marketing' => ['consent' => 'SUBSCRIBED']]]]]], $job['attributes']['profiles']['data']);

        $source = PersonSource::withoutGlobalScope('company')->where('person_id', $person->id)->where('platform', 'klaviyo')->sole();
        $this->assertSame('PROF1', $source->external_id); // the id Klaviyo gave them
        $this->assertSame('sent', PersonSyncEvent::withoutGlobalScope('company')->where('person_id', $person->id)->sole()->action);
        $this->assertSame(['success', 1], [IntegrationSync::withoutGlobalScope('company')->sole()->status, IntegrationSync::withoutGlobalScope('company')->sole()->exported]);
    }

    public function test_an_unsubscribed_person_is_only_unsubscribed_if_klaviyo_knows_them(): void
    {
        $known = $this->person('known@example.com', ['is_subscribed' => false]);
        $unknown = $this->person('stranger@example.com', ['is_subscribed' => false]);
        $this->fakeKlaviyo(['known@example.com' => 'PROF-KNOWN']);
        $this->connect('export', ['export_audience_id' => 'LIST1']);

        $this->assertSame([], $this->to('/api/profile-import/')); // nobody is created for an unsubscribed person
        $this->assertSame([], $this->to('/api/profile-subscription-bulk-create-jobs/'));
        $job = $this->to('/api/profile-subscription-bulk-delete-jobs/')[0]->data()['data'];
        $this->assertSame([['type' => 'profile', 'attributes' => ['email' => 'known@example.com']]], $job['attributes']['profiles']['data']);

        $this->assertSame('Marked unsubscribed.', PersonSyncEvent::withoutGlobalScope('company')->where('person_id', $known->id)->sole()->message);
        $skipped = PersonSyncEvent::withoutGlobalScope('company')->where('person_id', $unknown->id)->sole();
        $this->assertSame(['skipped', 'Unsubscribed here and not in Klaviyo.'], [$skipped->action, $skipped->message]);
    }

    public function test_a_phone_klaviyo_would_refuse_is_left_out_and_a_refused_person_is_recorded_not_fatal(): void
    {
        $this->person('a@example.com', ['phone' => '0400 111 222']); // not in international format
        $this->fakeKlaviyo();
        $this->connect('export', ['export_audience_id' => 'LIST1']);
        $this->assertArrayNotHasKey('phone_number', $this->to('/api/profile-import/')[0]->data()['data']['attributes']);

        $this->fakeKlaviyo([], ['/api/profile-import/' => 400]);
        $bad = $this->person('b@example.com');
        $this->postJson('/api/integrations', ['provider' => 'klaviyo', 'private_key' => 'pk_test_second1234567', 'direction' => 'export', 'export_audience_id' => 'LIST1'], $this->h)->assertStatus(201);
        $event = PersonSyncEvent::withoutGlobalScope('company')->where('person_id', $bad->id)->where('action', 'FAILED')->first();
        $this->assertSame('Klaviyo says no.', $event->message);
        $this->assertSame([], $this->to('/api/profile-subscription-bulk-create-jobs/')); // nobody left to subscribe
    }

    public function test_a_key_without_write_access_stops_the_sync_with_a_clear_message(): void
    {
        $this->person('a@example.com');
        $this->fakeKlaviyo([], ['/api/profile-import/' => 403]);
        $this->connect('export', ['export_audience_id' => 'LIST1']);

        $sync = IntegrationSync::withoutGlobalScope('company')->sole();
        $this->assertSame('failed', $sync->status);
        $this->assertStringContainsString('Write access to Profiles, Subscriptions and Lists', $sync->error);
    }

    public function test_the_lists_of_the_account_can_be_chosen_from_across_all_its_pages(): void
    {
        $this->fakeKlaviyo();
        $this->connect('import');
        $id = Integration::withoutGlobalScopes()->sole()->id;

        $choices = $this->getJson("/api/integrations/$id/audiences", $this->h)->assertOk()->json();
        $this->assertSame(['L1' => 'Newsletter', 'L2' => 'VIPs'], collect($choices)->pluck('name', 'id')->all());
    }

    public function test_a_missing_permission_is_named_instead_of_a_vague_refusal(): void
    {
        $this->fakeKlaviyo();
        $this->connect('import');
        $id = Integration::withoutGlobalScopes()->sole()->id;

        // the connection was made before Lists was switched on: Klaviyo still reads profiles but refuses the lists, and says why
        Http::swap(new Factory);
        Http::fake(fn (Request $r) => Http::response(['errors' => [['code' => 'permission_denied', 'detail' => 'Your access token is missing required scopes: lists:read']]], 403));

        $message = $this->getJson("/api/integrations/$id/audiences", $this->h)->assertStatus(422)->json('message');
        $this->assertStringContainsString('missing required scopes: lists:read', $message);
        $this->assertStringContainsString('reconnect', $message);
    }
    public function test_the_permissions_a_connection_really_has_can_be_checked_without_changing_anything(): void
    {
        $this->fakeKlaviyo();
        $this->connect('import');
        $id = Integration::withoutGlobalScopes()->sole()->id;

        // a connection that can read, but was not given the permission to read lists or to write lists
        Http::swap(new Factory);
        $this->calls = [];
        Http::fake(function (Request $r) {
            $this->calls[] = $r;
            $path = parse_url($r->url(), PHP_URL_PATH);

            return match (true) {
                $path === '/api/lists/' => Http::response(['errors' => [['detail' => 'Your access token is missing required scopes']]], 403),
                $r->method() === 'POST' && $path === '/api/lists/' => Http::response([], 403),
                $r->method() === 'POST' => Http::response(['errors' => [['detail' => 'incomplete request']]], 400), // allowed, then turned down for being empty
                default => Http::response(['data' => []]),
            };
        });
        Http::fake(['*/api/lists/' => fn (Request $r) => Http::response([], 403)]);

        $r = $this->postJson("/api/integrations/$id/permissions", [], $this->h)->assertOk();
        $this->assertSame(['accounts:read', 'profiles:read', 'profiles:write', 'subscriptions:read', 'subscriptions:write'], $r->json('data.settings.granted_scopes') ?? $r->json('settings.granted_scopes'));
        $this->assertNotEmpty(Integration::withoutGlobalScopes()->sole()->settings['scopes_checked_at']);

        foreach (array_filter($this->calls, fn ($c) => $c->method() === 'POST') as $post) { // the checks that write ask for nothing to be made
            $attributes = json_decode(json_encode($post->data()["data"]["attributes"]), true); // empty, or an empty list of profiles
            $this->assertTrue($attributes === [] || $attributes === ['profiles' => ['data' => []]]);
        }
    }

    public function test_checking_permissions_says_so_when_the_connection_itself_is_refused_and_only_klaviyo_can_be_asked(): void
    {
        $this->fakeKlaviyo();
        $this->connect('import');
        $id = Integration::withoutGlobalScopes()->sole()->id;

        Http::swap(new Factory);
        Http::fake(fn (Request $r) => Http::response(['errors' => [['code' => 'not_authenticated']]], 401));
        $this->postJson("/api/integrations/$id/permissions", [], $this->h)->assertStatus(422)->assertJsonPath('message', 'Klaviyo no longer accepts this connection. Reconnect it.');

        $list = collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key');
        $this->assertTrue($list['klaviyo']['auto_connect']['can_check_scopes']);
        $this->assertFalse($list['mailchimp']['auto_connect']['can_check_scopes']);
    }
    public function test_import_can_read_one_list_only(): void
    {
        $this->fakeKlaviyo();
        $this->connect('import', ['audience_id' => 'LIST1']);

        $this->assertNotEmpty($this->to('/api/lists/LIST1/profiles/', 'GET')); // the members of the list, not every profile
        $this->assertSame([], $this->to('/api/profiles/', 'GET')[1] ?? []);
        $ina = Person::withoutGlobalScopes()->where('email', 'in-list@example.com')->sole();
        $this->assertSame(['Ina', 'klaviyo'], [$ina->first_name, $ina->source]);
    }

    public function test_both_ways_imports_first_and_then_sends_everyone(): void
    {
        $this->person('local@example.com');
        $this->fakeKlaviyo();
        $this->connect('both', ['export_audience_id' => 'LIST1', 'audience_id' => 'LIST1']);

        $this->assertSame(1, Person::withoutGlobalScopes()->where('email', 'in-list@example.com')->count()); // imported
        $emails = collect($this->to('/api/profile-import/'))->map(fn ($r) => $r->data()['data']['attributes']['email'])->sort()->values()->all();
        $this->assertSame(['in-list@example.com', 'local@example.com'], $emails); // and both are sent
        $this->assertSame(2, IntegrationSync::withoutGlobalScope('company')->sole()->exported);
    }
}
