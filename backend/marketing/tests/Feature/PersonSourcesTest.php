<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Person;
use App\Models\PersonSource;
use App\Models\PersonSyncEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Where a person comes from, their id on each platform, and the sync history. */
class PersonSourcesTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected int $company;

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

    protected function fakeMailchimp(): void
    {
        Http::fake(function (Request $r) {
            $path = parse_url($r->url(), PHP_URL_PATH);

            return match (true) {
                str_contains($path, '/members/') => Http::response(['id' => basename($path), 'status' => 'subscribed']),
                $path === '/3.0/' => Http::response(['account_name' => 'Acme Mailchimp']),
                $path === '/3.0/lists/aud1/members' => Http::response(['members' => [
                    ['id' => md5('from-mc@example.com'), 'email_address' => 'from-mc@example.com', 'status' => 'subscribed', 'merge_fields' => ['FNAME' => 'Mia']],
                ], 'total_items' => 1]),
                preg_match('~^/3.0/lists/\w+$~', $path) === 1 => Http::response(['id' => 'aud1']),
                default => Http::response([], 404),
            };
        });
    }

    public function test_a_person_added_by_hand_records_where_it_came_from(): void
    {
        $id = $this->postJson('/api/people', ['email' => 'hand@example.com'], $this->h)->assertCreated()->json('id');

        $history = $this->getJson("/api/people/$id/sync-history", $this->h)->assertOk()->json();
        $this->assertSame(['manual', 'Added manually'], [$history['sources'][0]['platform'], $history['sources'][0]['platform_label']]);
        $this->assertSame(['created', 'in'], [strtolower($history['events'][0]['action']), strtolower($history['events'][0]['direction'])]);

        $row = collect($this->getJson('/api/people', $this->h)->json('data'))->firstWhere('id', $id);
        $this->assertSame('manual', $row['sources'][0]['platform']); // the list carries them, for the edit window
    }

    public function test_an_imported_customer_keeps_its_id_and_a_person_on_two_platforms_has_two_sources(): void
    {
        $this->fakeMailchimp();
        $existing = Person::withoutGlobalScopes()->findOrFail($this->postJson('/api/people', ['email' => 'from-mc@example.com'], $this->h)->assertCreated()->json('id'));

        $this->postJson('/api/integrations', ['provider' => 'mailchimp', 'api_key' => 'abcdef123456-us21', 'direction' => 'both', 'audience_id' => 'aud1'], $this->h)->assertStatus(201);

        $sources = PersonSource::withoutGlobalScope('company')->where('person_id', $existing->id)->get()->keyBy('platform');
        $this->assertSame(md5('from-mc@example.com'), $sources['mailchimp']->external_id); // the id Mailchimp has for them
        $this->assertTrue(isset($sources['manual'])); // and where they were first added is kept

        $events = PersonSyncEvent::withoutGlobalScope('company')->where('person_id', $existing->id)->where('platform', 'mailchimp')->orderBy('id')->get();
        $this->assertSame(['linked', 'in'], [$events[0]->action, $events[0]->direction]); // already a person: now also found on Mailchimp
        $sent = $events->firstWhere('action', 'sent');
        $this->assertSame(['out', 'mailchimp'], [$sent->direction, $sent->platform]); // pushed back to Mailchimp
        $this->assertNotNull($sent->sync_id);
    }

    public function test_a_person_sent_to_mailchimp_stores_the_id_mailchimp_gave_them(): void
    {
        $this->fakeMailchimp();
        $person = tap((new Person)->forceFill(['company_id' => $this->company, 'email' => 'sub@example.com', 'source' => 'form', 'is_subscribed' => true]))->save();

        $this->postJson('/api/integrations', ['provider' => 'mailchimp', 'api_key' => 'abcdef123456-us21', 'direction' => 'export', 'audience_id' => 'aud1'], $this->h)->assertStatus(201);

        $source = PersonSource::withoutGlobalScope('company')->where('person_id', $person->id)->where('platform', 'mailchimp')->sole();
        $this->assertSame(md5('sub@example.com'), $source->external_id);
        $this->assertSame('sent', PersonSyncEvent::withoutGlobalScope('company')->where('person_id', $person->id)->sole()->action);
    }
}
