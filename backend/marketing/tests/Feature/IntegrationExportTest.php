<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Integration;
use App\Models\IntegrationSync;
use App\Models\PeopleList;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Mailchimp in both directions: import (Mailchimp → People), export (People → Mailchimp) or both. */
class IntegrationExportTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected int $company;

    /** @var array<int, array{0: string, 1: string, 2: array}> method, path, json body of every request to Mailchimp's members */
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
        $person = (new Person)->forceFill(array_merge(['company_id' => $this->company, 'email' => $email, 'is_subscribed' => true, 'source' => 'manual'], $more));
        $person->save();

        return $person;
    }

    /** @param  array<string, int>  $answers  status per path fragment, for example ['/members/'.md5('x@y.com') => 400] */
    protected function fakeMailchimp(array $answers = []): void
    {
        $this->calls = [];
        Http::fake(function (Request $r) use ($answers) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            if (str_contains($path, '/members/')) {
                $this->calls[] = [$r->method(), $path, $r->data()];
                foreach ($answers as $fragment => $reply) {
                    if (str_contains($path, $fragment)) {
                        return Http::response($reply['body'], $reply['status']);
                    }
                }

                return Http::response(['id' => 'x', 'status' => 'subscribed']);
            }

            return match (true) {
                $path === '/3.0/' => Http::response(['account_name' => 'Acme Mailchimp']),
                $path === '/3.0/lists' => Http::response(['lists' => [['id' => 'aud1']]]),
                $path === '/3.0/lists/aud1/members' => Http::response(['members' => [
                    ['id' => 'm1', 'email_address' => 'from-mc@example.com', 'status' => 'subscribed', 'merge_fields' => ['FNAME' => 'Mia']],
                ], 'total_items' => 1]),
                preg_match('~^/3.0/lists/\w+$~', $path) === 1 => Http::response(['id' => 'aud1']),
                default => Http::response([], 404),
            };
        });
    }

    protected function connect(array $extra = [], int $status = 201)
    {
        return $this->postJson('/api/integrations', ['provider' => 'mailchimp', 'api_key' => 'abcdef123456-us21'] + $extra, $this->h)->assertStatus($status);
    }

    protected function memberCalls(string $method): array
    {
        return array_values(array_filter($this->calls, fn ($c) => $c[0] === $method));
    }

    public function test_only_platforms_that_can_receive_people_offer_a_direction(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key');
        $this->assertTrue($list['mailchimp']['supports_export']);
        $this->assertFalse($list['hubspot']['supports_export']);
        $this->assertTrue($list['mailchimp']['fields'][1]['export_required']);
    }

    public function test_import_only_is_the_default_and_sends_nothing_to_mailchimp(): void
    {
        $this->person('here@example.com');
        $this->fakeMailchimp();
        $this->connect();

        $this->assertSame([], $this->calls);
        $this->assertSame(1, Person::withoutGlobalScopes()->where('email', 'from-mc@example.com')->count()); // imported
        $this->assertSame(0, IntegrationSync::withoutGlobalScope('company')->sole()->exported);
    }

    public function test_export_only_sends_people_and_reads_nothing_from_mailchimp(): void
    {
        $this->person('sub@example.com', ['first_name' => 'Sue', 'last_name' => 'Sub', 'phone' => '+61400']);
        $this->person('unsub@example.com', ['is_subscribed' => false]);
        $this->person('nomail-phone@example.com'); // has an email too, so it is sent
        $this->fakeMailchimp();
        $this->connect(['direction' => 'export', 'audience_id' => 'aud1']);

        $put = $this->memberCalls('PUT');
        $this->assertCount(2, $put);
        $this->assertSame('/3.0/lists/aud1/members/'.md5('sub@example.com'), $put[0][1]);
        $this->assertSame('subscribed', $put[0][2]['status_if_new']);
        $this->assertArrayNotHasKey('status', $put[0][2]); // an existing member's status is never overwritten, so nobody who unsubscribed there is re-subscribed
        $this->assertSame(['FNAME' => 'Sue', 'LNAME' => 'Sub', 'PHONE' => '+61400'], (array) $put[0][2]['merge_fields']);

        $patch = $this->memberCalls('PATCH'); // unsubscribed here: marked unsubscribed there
        $this->assertCount(1, $patch);
        $this->assertSame(['status' => 'unsubscribed'], $patch[0][2]);

        $this->assertSame(0, Person::withoutGlobalScopes()->where('email', 'from-mc@example.com')->count()); // nothing imported
        $sync = IntegrationSync::withoutGlobalScope('company')->sole();
        $this->assertSame(['success', 3, 0], [$sync->status, $sync->exported, $sync->export_failed]);
    }

    public function test_both_directions_import_first_and_then_send_everyone_including_the_imported(): void
    {
        $this->person('local@example.com');
        $this->fakeMailchimp();
        $this->connect(['direction' => 'both', 'audience_id' => 'aud1']);

        $this->assertSame(1, Person::withoutGlobalScopes()->where('email', 'from-mc@example.com')->count());
        $sent = array_map(fn ($c) => $c[1], $this->memberCalls('PUT'));
        $this->assertEqualsCanonicalizing([
            '/3.0/lists/aud1/members/'.md5('local@example.com'), '/3.0/lists/aud1/members/'.md5('from-mc@example.com'),
        ], $sent);
        $this->assertSame(2, IntegrationSync::withoutGlobalScope('company')->sole()->exported);
    }

    public function test_sending_needs_an_audience_and_can_be_limited_to_one_list(): void
    {
        $this->fakeMailchimp();
        $this->connect(['direction' => 'export'], 422); // which audience?
        $this->connect(['direction' => 'both'], 422);

        $list = PeopleList::create(['name' => 'VIPs']);
        $in = $this->person('vip@example.com');
        $this->person('other@example.com');
        $in->lists()->attach($list->id);
        $this->calls = [];
        $this->connect(['direction' => 'export', 'audience_id' => 'aud1', 'export_list_id' => $list->id]);

        $this->assertSame(['/3.0/lists/aud1/members/'.md5('vip@example.com')], array_map(fn ($c) => $c[1], $this->memberCalls('PUT')));
    }

    public function test_changing_the_direction_later_needs_the_audience_too(): void
    {
        $this->fakeMailchimp();
        $id = $this->connect()->json('id');
        $this->putJson("/api/integrations/$id", ['direction' => 'both'], $this->h)->assertStatus(422);
        $this->putJson("/api/integrations/$id", ['direction' => 'both', 'audience_id' => 'aud1'], $this->h)->assertOk()->assertJsonPath('settings.direction', 'both');
    }

    public function test_mailchimp_refusals_are_counted_without_stopping_and_a_person_mailchimp_will_not_take_back_is_skipped(): void
    {
        $this->person('good@example.com');
        $this->person('bad@example.com');
        $this->person('gone@example.com');
        $this->fakeMailchimp([
            md5('bad@example.com') => ['status' => 400, 'body' => ['title' => 'Invalid Resource', 'detail' => 'bad@example.com looks fake or invalid, please enter a real email address.']],
            md5('gone@example.com') => ['status' => 400, 'body' => ['title' => 'Member In Compliance State', 'detail' => 'unsubscribed']],
        ]);
        $this->connect(['direction' => 'export', 'audience_id' => 'aud1']);

        $sync = IntegrationSync::withoutGlobalScope('company')->sole();
        $this->assertSame(['success', 1, 1, 1], [$sync->status, $sync->exported, $sync->export_failed, $sync->skipped]);
        $this->assertStringContainsString('looks fake', $sync->error); // the first refusal is kept as a hint
    }

    public function test_a_later_sync_sends_only_people_who_changed_since_and_a_rejected_key_fails_the_sync(): void
    {
        $old = $this->person('old@example.com');
        $this->fakeMailchimp();
        $id = $this->connect(['direction' => 'export', 'audience_id' => 'aud1'])->json('id');
        $old->forceFill(['updated_at' => now()->subDays(3)])->saveQuietly();
        Integration::withoutGlobalScope('company')->find($id)->forceFill(['last_synced_at' => now()->subHour()])->save();
        $this->person('new@example.com');

        $this->calls = [];
        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202);
        $this->assertSame(['/3.0/lists/aud1/members/'.md5('new@example.com')], array_map(fn ($c) => $c[1], $this->memberCalls('PUT')));

        Http::swap(new Factory);
        Http::fake(['*' => Http::response(['title' => 'API Key Invalid'], 401)]);
        $this->postJson("/api/integrations/$id/sync", ['full' => true], $this->h)->assertStatus(202);
        $this->assertSame('failed', IntegrationSync::withoutGlobalScope('company')->latest('id')->first()->status);
    }

    public function test_a_platform_that_cannot_receive_people_ignores_the_direction(): void
    {
        Http::fake(['api.hubapi.com/*' => Http::response(['results' => [], 'paging' => []])]);
        $r = $this->postJson('/api/integrations', ['provider' => 'hubspot', 'access_token' => 't', 'direction' => 'both'], $this->h)->assertStatus(201);
        $this->assertArrayNotHasKey('direction', $r->json('settings'));
    }
}
