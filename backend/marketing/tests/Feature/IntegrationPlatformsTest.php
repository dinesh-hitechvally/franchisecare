<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationSync;
use App\Models\Person;
use App\Services\Integrations\Providers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The CRM, email-marketing and payments platforms, and connecting several stores or accounts of the same platform. */
class IntegrationPlatformsTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.outbound.allow_private' => true]); // no real DNS lookups in tests
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
        Http::swap(new Factory);
    }

    protected function urlQuery(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);

        return $q;
    }

    protected function connect(array $body, int $status = 201)
    {
        return $this->postJson('/api/integrations', $body, $this->h)->assertStatus($status);
    }

    protected function people(): \Illuminate\Support\Collection
    {
        return Person::withoutGlobalScopes()->orderBy('id')->get()->keyBy('email');
    }

    protected function fakeWooShop(string $host, string $name, string $prefix): void
    {
        Http::fake([
            "$host/wp-json/wc/v3/customers*" => Http::response([['id' => 1, 'email' => "{$prefix}1@example.com", 'first_name' => $prefix, 'last_name' => 'One', 'billing' => ['phone' => '']]], 200, ['X-WP-TotalPages' => 1]),
            "$host/wp-json/" => Http::response(['name' => $name, 'namespaces' => ['wc/v3']]),
        ]);
    }

    // ----------------------------------------------------------------- the catalogue

    public function test_the_catalogue_has_at_least_five_platforms_in_each_group_and_only_some_ask_for_an_address(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->assertOk()->json());

        $this->assertCount(37, $list);
        foreach (['store', 'website', 'crm', 'email', 'payments'] as $group) {
            $this->assertGreaterThanOrEqual(5, $list->where('category', $group)->count(), $group);
        }
        $this->assertSame(['shopify', 'woocommerce', 'magento', 'bigcommerce', 'ecwid'], $list->where('category', 'store')->pluck('key')->values()->all());
        $this->assertSame(['etsy', 'ebay', 'amazon', 'walmart', 'mercadolibre', 'daraz'], $list->where('category', 'marketplace')->pluck('key')->values()->all());
        $this->assertSame(['shopify', 'woocommerce', 'magento', 'wordpress', 'ghost', 'salesforce', 'activecampaign'], $list->where('uses_url', true)->pluck('key')->values()->all());
        foreach ($list as $p) {
            $this->assertNotEmpty($p['steps'], $p['key']);
            $this->assertNotEmpty($p['consent_note'], $p['key']);
            $this->assertSame($p['uses_url'] ? 'url' : null, collect($p['fields'])->firstWhere('key', 'url')['key'] ?? null, $p['key']);
            $this->assertLessThanOrEqual(20, strlen($p['key'])); // people.source is 20 characters
        }
        $this->assertFalse($list->firstWhere('key', 'mailchimp')['fields'][1]['required']); // the audience is optional
    }

    // ----------------------------------------------------------------- several stores or accounts of one platform

    public function test_several_stores_of_the_same_platform_can_be_connected_each_with_its_own_name_list_and_tags(): void
    {
        $a = $this->postJson('/api/lists', ['name' => 'Shop A'], $this->h)->json('id');
        $b = $this->postJson('/api/lists', ['name' => 'Shop B'], $this->h)->json('id');

        $this->fakeWooShop('shop-a.test', 'Shop A', 'a');
        $one = $this->connect(['provider' => 'woocommerce', 'url' => 'https://shop-a.test', 'consumer_key' => 'k', 'consumer_secret' => 's', 'name' => 'Australia shop', 'list_id' => $a, 'tags' => ['AU']]);
        $this->fakeWooShop('shop-b.test', 'Shop B', 'b');
        $two = $this->connect(['provider' => 'woocommerce', 'url' => 'https://shop-b.test', 'consumer_key' => 'k', 'consumer_secret' => 's', 'list_id' => $b, 'tags' => ['NZ']]);

        $this->assertSame('Australia shop', $one->json('name')); // the person's own name wins ...
        $this->assertSame('Shop B', $two->json('name'));         // ... otherwise the store's
        $this->assertCount(2, $this->getJson('/api/integrations', $this->h)->json());

        $p = $this->people();
        $this->assertSame([$a], $p['a1@example.com']->lists()->pluck('people_lists.id')->all());
        $this->assertSame([$b], $p['b1@example.com']->lists()->pluck('people_lists.id')->all());
        $this->assertEqualsCanonicalizing(['WooCommerce', 'AU'], $p['a1@example.com']->tags()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['WooCommerce', 'NZ'], $p['b1@example.com']->tags()->pluck('name')->all());
        $this->assertSame(2, IntegrationSync::withoutGlobalScopes()->count()); // each store has its own import
    }

    public function test_two_shopify_stores_can_be_connected_but_the_same_store_cannot_be_connected_twice(): void
    {
        Http::fake(function (Request $r) {
            return str_contains($r->data()['query'] ?? '', 'shop {')
                ? Http::response(['data' => ['shop' => ['name' => 'A Shop']]])
                : Http::response(['data' => ['customers' => ['edges' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]]]]);
        });

        $this->connect(['provider' => 'shopify', 'url' => 'store-one', 'access_token' => 'shpat_1']);
        $this->connect(['provider' => 'shopify', 'url' => 'store-two.myshopify.com', 'access_token' => 'shpat_2']); // another store: fine
        $this->assertSame(2, Integration::withoutGlobalScope('company')->count());

        $again = $this->connect(['provider' => 'shopify', 'url' => 'https://STORE-ONE.myshopify.com/admin', 'access_token' => 'shpat_new'], 422); // the same store, typed differently
        $this->assertStringContainsString('already connected', $again->json('message'));
        $this->assertStringContainsString('A Shop', $again->json('message'));
        $this->assertSame(2, Integration::withoutGlobalScope('company')->count());

        // changing a connection to point at the other store is refused too
        $id = Integration::withoutGlobalScopes()->where('store_url', 'https://store-two.myshopify.com')->value('id');
        $this->putJson("/api/integrations/$id", ['url' => 'store-one', 'access_token' => 'shpat_x'], $this->h)->assertStatus(422);
    }

    public function test_the_same_connection_can_be_removed_and_connected_again(): void
    {
        $this->fakeWooShop('shop-a.test', 'Shop A', 'a');
        $id = $this->connect(['provider' => 'woocommerce', 'url' => 'https://shop-a.test', 'consumer_key' => 'k', 'consumer_secret' => 's', 'import_now' => false])->json('id');
        $this->deleteJson("/api/integrations/$id", [], $this->h)->assertNoContent();

        $this->connect(['provider' => 'woocommerce', 'url' => 'https://shop-a.test', 'consumer_key' => 'k', 'consumer_secret' => 's', 'import_now' => false]);
        $this->assertSame(1, Integration::withoutGlobalScope('company')->count());
    }

    // ----------------------------------------------------------------- HubSpot

    public function test_hubspot_contacts_are_paged_with_the_cursor_and_an_email_opt_out_stays_unsubscribed_even_on_everyone(): void
    {
        $seen = [];
        Http::fake(['api.hubapi.com/crm/v3/objects/contacts*' => function (Request $r) use (&$seen) {
            $q = $this->urlQuery($r);
            $seen[] = $r->header('Authorization')[0] ?? null;
            $first = empty($q['after']);

            return Http::response([
                'results' => $first
                    ? [['id' => '1', 'properties' => ['email' => 'Ann@Example.com', 'firstname' => 'Ann', 'lastname' => 'Lee', 'phone' => '', 'mobilephone' => '+61400000001', 'hs_email_optout' => 'false']],
                        ['id' => '2', 'properties' => ['email' => 'out@example.com', 'firstname' => 'Opted', 'lastname' => 'Out', 'phone' => '+61400000002', 'hs_email_optout' => 'true']]]
                    : [['id' => '3', 'properties' => ['email' => 'bob@example.com', 'firstname' => 'Bob', 'lastname' => null, 'phone' => null, 'mobilephone' => null, 'hs_email_optout' => null]]],
                'paging' => $first ? ['next' => ['after' => 'cursor-2']] : [],
            ]);
        }]);

        $r = $this->connect(['provider' => 'hubspot', 'access_token' => 'pat-na1-secret', 'subscribe_mode' => 'all', 'confirm_permission' => true]);
        $this->assertSame('hubspot', $r->json('provider'));
        $this->assertFalse($r->json('uses_url'));
        $this->assertSame('https://api.hubapi.com', $r->json('store_url'));
        $this->assertSame('Bearer pat-na1-secret', $seen[0]);

        $p = $this->people();
        $this->assertSame(['ann@example.com', 'out@example.com', 'bob@example.com'], $p->keys()->all());
        $this->assertSame('+61400000001', $p['ann@example.com']->phone); // mobile when there is no phone
        $this->assertSame([true, false, true], $p->map(fn ($x) => (bool) $x->is_subscribed)->values()->all()); // "everyone" except the opt-out
        $this->assertSame('hubspot', $p['ann@example.com']->source);

        // a token that cannot read contacts is explained
        $this->fakeHubspotDenied();
        $this->connect(['provider' => 'hubspot', 'access_token' => 'pat-other'], 422);
    }

    protected function fakeHubspotDenied(): void
    {
        Http::swap(new Factory);
        Http::fake(['api.hubapi.com/*' => Http::response(['status' => 'error', 'category' => 'MISSING_SCOPES'], 403)]);
    }

    // ----------------------------------------------------------------- Mailchimp

    protected function fakeMailchimp(array &$seen = []): void
    {
        Http::fake(function (Request $r) use (&$seen) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            $q = $this->urlQuery($r);
            $seen[] = $path.'?'.http_build_query($q);

            return match (true) {
                $path === '/3.0/' => Http::response(['account_name' => 'Acme Mailchimp']),
                $path === '/3.0/lists' => Http::response(['lists' => [['id' => 'aud1'], ['id' => 'aud2']]]),
                preg_match('~^/3.0/lists/(\w+)$~', $path) === 1 => Http::response(['id' => 'x']),
                $path === '/3.0/lists/aud1/members' => Http::response(['members' => [
                    ['id' => 'm1', 'email_address' => 'sub@example.com', 'status' => 'subscribed', 'merge_fields' => ['FNAME' => 'Sue', 'LNAME' => 'Sub', 'PHONE' => '+61411111111']],
                    ['id' => 'm2', 'email_address' => 'unsub@example.com', 'status' => 'unsubscribed', 'merge_fields' => ['FNAME' => 'Una', 'LNAME' => 'Unsub']],
                    ['id' => 'm3', 'email_address' => 'bounced@example.com', 'status' => 'cleaned', 'merge_fields' => []],
                ], 'total_items' => 3]),
                $path === '/3.0/lists/aud2/members' => Http::response(['members' => [
                    ['id' => 'm4', 'email_address' => 'pending@example.com', 'status' => 'pending', 'merge_fields' => ['FNAME' => 'Pen']],
                    ['id' => 'm5', 'email_address' => 'sub@example.com', 'status' => 'subscribed', 'merge_fields' => []], // the same person on two audiences
                ], 'total_items' => 2]),
                default => Http::response([], 404),
            };
        });
    }

    public function test_mailchimp_reads_every_audience_maps_the_status_and_needs_a_data_centre_in_the_key(): void
    {
        $this->connect(['provider' => 'mailchimp', 'api_key' => 'nodatacentre'], 422)->assertJsonPath('message', 'That does not look like a Mailchimp API key. It ends with a dash and a data centre, for example -us21.');

        $seen = [];
        $this->fakeMailchimp($seen);
        $r = $this->connect(['provider' => 'mailchimp', 'api_key' => 'abcdef123456-us21']);
        $this->assertSame('Acme Mailchimp', $r->json('name'));
        $this->assertSame('https://us21.api.mailchimp.com', $r->json('store_url'));

        $p = $this->people();
        $this->assertSame(['sub@example.com', 'unsub@example.com', 'bounced@example.com', 'pending@example.com'], $p->keys()->all()); // two audiences, the repeat is one person
        // the default "only people who agreed": subscribed members only; unsubscribed, cleaned and pending are imported but not subscribed
        $this->assertSame([true, false, false, false], $p->map(fn ($x) => (bool) $x->is_subscribed)->values()->all());
        $this->assertSame(['Sue', 'Sub', '+61411111111'], [$p['sub@example.com']->first_name, $p['sub@example.com']->last_name, $p['sub@example.com']->phone]);
        $sync = IntegrationSync::withoutGlobalScopes()->first();
        $this->assertSame([5, 4], [$sync->fetched, $sync->created]);
    }

    public function test_mailchimp_can_import_one_audience_and_the_same_account_can_be_connected_once_per_audience(): void
    {
        $seen = [];
        $this->fakeMailchimp($seen);
        $this->connect(['provider' => 'mailchimp', 'api_key' => 'abcdef123456-us21', 'audience_id' => 'aud2']);
        $this->assertSame(['pending@example.com', 'sub@example.com'], $this->people()->keys()->all()); // aud2 only
        $this->assertSame('aud2', Integration::withoutGlobalScopes()->first()->settings['config']['audience_id']);

        $this->connect(['provider' => 'mailchimp', 'api_key' => 'abcdef123456-us21', 'audience_id' => 'aud1']); // another audience of the same account: fine
        $this->assertSame(2, Integration::withoutGlobalScope('company')->count());
        $this->connect(['provider' => 'mailchimp', 'api_key' => 'abcdef123456-us21', 'audience_id' => 'aud1'], 422); // the same audience again: refused
        $this->connect(['provider' => 'mailchimp', 'api_key' => 'zzzzzz999999-us21', 'audience_id' => 'aud1']); // a different account: fine
        $this->assertSame(3, Integration::withoutGlobalScope('company')->count());
    }

    public function test_a_mailchimp_sync_after_the_first_asks_only_for_members_changed_since(): void
    {
        $seen = [];
        $this->fakeMailchimp($seen);
        $id = $this->connect(['provider' => 'mailchimp', 'api_key' => 'abcdef123456-us21'])->json('id');
        $this->assertStringNotContainsString('since_last_changed', implode(' ', $seen));

        $seen = [];
        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202);
        $this->assertStringContainsString('since_last_changed=', implode(' ', $seen));
    }

    // ----------------------------------------------------------------- Klaviyo

    public function test_klaviyo_profiles_carry_their_consent_and_the_next_page_cursor_is_taken_from_the_link(): void
    {
        $revision = null;
        Http::fake(function (Request $r) use (&$revision) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            $revision = $r->header('revision')[0] ?? null;
            if ($path === '/api/accounts/') {
                return Http::response(['data' => [['attributes' => ['contact_information' => ['organization_name' => 'Acme Klaviyo']]]]]);
            }
            $q = $this->urlQuery($r);
            $cursor = $q['page']['cursor'] ?? null;
            $row = fn ($id, $email, $consent) => ['id' => $id, 'attributes' => ['email' => $email, 'first_name' => "K$id", 'last_name' => 'Profile', 'phone_number' => null, 'subscriptions' => ['email' => ['marketing' => $consent ? ['consent' => $consent] : []]]]];

            return Http::response($cursor === 'next-page'
                ? ['data' => [$row('3', 'never@example.com', 'NEVER_SUBSCRIBED'), $row('4', 'plain@example.com', null)], 'links' => ['next' => null]]
                : ['data' => [$row('1', 'yes@example.com', 'SUBSCRIBED'), $row('2', 'no@example.com', 'UNSUBSCRIBED')], 'links' => ['next' => 'https://a.klaviyo.com/api/profiles/?page%5Bcursor%5D=next-page&page%5Bsize%5D=100']]);
        });

        $r = $this->connect(['provider' => 'klaviyo', 'private_key' => 'pk_secret1234']);
        $this->assertSame('Acme Klaviyo', $r->json('name'));
        $this->assertSame(config('services.integrations.klaviyo.api_version'), $revision); // Klaviyo needs an API revision on every call

        $p = $this->people();
        $this->assertSame(['yes@example.com', 'no@example.com', 'never@example.com', 'plain@example.com'], $p->keys()->all());
        $this->assertSame([true, false, false, false], $p->map(fn ($x) => (bool) $x->is_subscribed)->values()->all());
    }

    // ----------------------------------------------------------------- Brevo

    public function test_brevo_contacts_are_paged_by_offset_and_blocklisted_ones_stay_unsubscribed(): void
    {
        $asked = [];
        Http::fake(function (Request $r) use (&$asked) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            if ($path === '/v3/account') {
                return Http::response(['companyName' => 'Acme Brevo', 'email' => 'a@acme.com']);
            }
            $q = $this->urlQuery($r);
            $asked[] = $q;
            $offset = (int) ($q['offset'] ?? 0);
            $rows = [];
            for ($n = $offset + 1; $n <= min($offset + (int) $q['limit'], 3); $n++) {
                $rows[] = ['id' => $n, 'email' => "b$n@example.com", 'emailBlacklisted' => $n === 2, 'attributes' => ['FIRSTNAME' => "B$n", 'LASTNAME' => 'Contact', 'SMS' => $n === 1 ? '+61422222222' : '']];
            }

            return Http::response(['contacts' => $rows, 'count' => 3]);
        });

        $id = $this->connect(['provider' => 'brevo', 'api_key' => 'xkeysib-secret', 'subscribe_mode' => 'all', 'confirm_permission' => true])->assertJsonPath('name', 'Acme Brevo')->json('id');
        $p = $this->people();
        $this->assertSame(['b1@example.com', 'b2@example.com', 'b3@example.com'], $p->keys()->all());
        $this->assertSame([true, false, true], $p->map(fn ($x) => (bool) $x->is_subscribed)->values()->all());
        $this->assertSame('+61422222222', $p['b1@example.com']->phone);
        $this->assertArrayNotHasKey('modifiedSince', $asked[count($asked) - 1]);

        $asked = [];
        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202);
        $this->assertArrayHasKey('modifiedSince', $asked[count($asked) - 1]); // later syncs ask for what changed
    }

    // ----------------------------------------------------------------- Stripe

    public function test_stripe_customers_are_paged_by_the_last_id_and_a_full_name_is_split(): void
    {
        $seen = [];
        Http::fake(function (Request $r) use (&$seen) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            if ($path === '/v1/account') {
                return Http::response(['business_profile' => ['name' => 'Acme Pty Ltd']]);
            }
            $q = $this->urlQuery($r);
            $seen[] = $q;
            $after = $q['starting_after'] ?? null;

            return Http::response($after === 'cus_2'
                ? ['data' => [['id' => 'cus_3', 'email' => 'c3@example.com', 'name' => null, 'phone' => null]], 'has_more' => false]
                : ['data' => [['id' => 'cus_1', 'email' => 'c1@example.com', 'name' => 'Carla De Souza', 'phone' => '+61433333333'], ['id' => 'cus_2', 'email' => 'c2@example.com', 'name' => 'Cher', 'phone' => null]], 'has_more' => true]);
        });

        $id = $this->connect(['provider' => 'stripe', 'secret_key' => 'rk_live_secret'])->assertJsonPath('name', 'Acme Pty Ltd')->json('id');
        $p = $this->people();
        $this->assertSame(['c1@example.com', 'c2@example.com', 'c3@example.com'], $p->keys()->all());
        $this->assertSame(['Carla', 'De Souza', '+61433333333'], [$p['c1@example.com']->first_name, $p['c1@example.com']->last_name, $p['c1@example.com']->phone]);
        $this->assertSame([false, false, false], $p->map(fn ($x) => (bool) $x->is_subscribed)->values()->all()); // Stripe records no marketing permission

        $seen = [];
        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202);
        $this->assertArrayHasKey('created', $seen[0]); // later syncs ask only for newer customers
        $this->assertArrayHasKey('gte', $seen[0]['created']);
    }

    public function test_a_key_the_platform_refuses_is_explained_for_each_platform(): void
    {
        Http::fake(['*' => Http::response(['error' => 'no'], 401)]);

        foreach ([
            ['provider' => 'mailchimp', 'api_key' => 'abcdef-us21', 'needle' => 'Mailchimp refused the API key'],
            ['provider' => 'klaviyo', 'private_key' => 'pk_bad', 'needle' => 'Klaviyo refused the key'],
            ['provider' => 'brevo', 'api_key' => 'bad', 'needle' => 'Brevo refused the API key'],
            ['provider' => 'stripe', 'secret_key' => 'bad', 'needle' => 'Stripe refused the key'],
            ['provider' => 'hubspot', 'access_token' => 'bad', 'needle' => 'HubSpot refused the token'],
        ] as $case) {
            $needle = array_pop($case);
            $this->connect($case, 422);
            $this->assertStringContainsString($needle, json_decode($this->postJson('/api/integrations', $case, $this->h)->getContent(), true)['message']);
        }
        $this->assertSame(0, Integration::withoutGlobalScope('company')->count()); // nothing was saved
    }

    public function test_disconnected_connections_are_listed_only_when_asked_for_and_can_be_connected_again(): void
    {
        $this->fakeWooShop('shop-a.test', 'Shop A', 'a');
        $keep = $this->connect(['provider' => 'woocommerce', 'url' => 'https://shop-a.test', 'consumer_key' => 'k', 'consumer_secret' => 's', 'import_now' => false])->json('id');
        $this->fakeWooShop('shop-b.test', 'Shop B', 'b');
        $gone = $this->connect(['provider' => 'woocommerce', 'url' => 'https://shop-b.test', 'consumer_key' => 'k', 'consumer_secret' => 's', 'import_now' => false])->json('id');
        $this->deleteJson("/api/integrations/$gone", [], $this->h)->assertNoContent();

        $this->assertSame([$keep], array_column($this->getJson('/api/integrations', $this->h)->json(), 'id')); // the default list: connected only

        $all = $this->getJson('/api/integrations?include_disconnected=1', $this->h)->json();
        $this->assertSame([$keep, $gone], array_column($all, 'id')); // disconnected ones come last
        $this->assertSame(['connected', 'disconnected'], array_column($all, 'status'));
        $this->assertNotNull($all[1]['disconnected_at']);
        $this->assertArrayNotHasKey('secrets', $all[1]);

        $this->connect(['provider' => 'woocommerce', 'url' => 'https://shop-b.test', 'consumer_key' => 'k2', 'consumer_secret' => 's2', 'import_now' => false]); // connect again
        $this->assertCount(3, $this->getJson('/api/integrations?include_disconnected=1', $this->h)->json()); // the old row stays as history
    }

    public function test_facebook_lead_ads_are_listed_under_social_media_and_a_form_s_leads_become_people(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->assertOk()->json());
        $this->assertSame(['facebook', 'instagram', 'linkedin'], $list->where('category', 'social')->pluck('key')->values()->all());

        Http::fake(function ($request) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/12345678')) {
                return Http::response(['name' => 'Spring offer']);
            }

            return Http::response(['data' => [['id' => 'l1', 'field_data' => [['name' => 'full_name', 'values' => ['Ann Lee']], ['name' => 'email', 'values' => ['ANN@x.com']]]]]]);
        });
        $this->connect(['provider' => 'facebook', 'access_token' => 'EAAtoken', 'form_id' => '12345678']);
        $i = Integration::withoutGlobalScope('company')->first();
        [$rows, $next] = Providers::find('facebook')->customers($i, null, null);
        $this->assertNull($next);
        $this->assertSame(['ann@x.com', 'Ann', 'Lee', 'none'], [$rows[0]['email'], $rows[0]['first_name'], $rows[0]['last_name'], $rows[0]['consent']]);
    }

    public function test_linkedin_lead_gen_answers_are_mapped_through_the_form_s_questions(): void
    {
        $seen = [];
        Http::fake(function ($request) use (&$seen) {
            $seen[] = [$request->url(), $request->header('LinkedIn-Version')[0] ?? null];
            if (str_contains($request->url(), '/leadForms/3162')) {
                return Http::response(['content' => ['questions' => [['questionId' => 1, 'predefinedField' => 'FIRST_NAME'], ['questionId' => 2, 'predefinedField' => 'LAST_NAME'], ['questionId' => 3, 'predefinedField' => 'EMAIL']]]]);
            }

            return Http::response(['elements' => [['id' => 'abc-5', 'versionedLeadGenFormUrn' => 'urn:li:versionedLeadGenForm:(urn:li:leadGenForm:3162,1)', 'formResponse' => ['answers' => [
                ['questionId' => 1, 'answerDetails' => ['textQuestionAnswer' => ['answer' => 'Ann']]],
                ['questionId' => 2, 'answerDetails' => ['textQuestionAnswer' => ['answer' => 'Lee']]],
                ['questionId' => 3, 'answerDetails' => ['textQuestionAnswer' => ['answer' => 'Ann@X.com']]],
            ]]]], 'paging' => ['start' => 0, 'count' => 100, 'total' => 1]]);
        });
        $this->connect(['provider' => 'linkedin', 'access_token' => 'AQVtoken', 'account_id' => '512345678']);
        $i = Integration::withoutGlobalScope('company')->first();
        [$rows, $next] = Providers::find('linkedin')->customers($i, null, null);

        $this->assertNull($next);
        $this->assertSame(['ann@x.com', 'Ann', 'Lee'], [$rows[0]['email'], $rows[0]['first_name'], $rows[0]['last_name']]);
        $this->assertStringContainsString('owner=(sponsoredAccount:urn%3Ali%3AsponsoredAccount%3A512345678)', $seen[0][0]); // Rest.li form is kept intact
        $this->assertSame('202610', $seen[0][1]);
    }
}
