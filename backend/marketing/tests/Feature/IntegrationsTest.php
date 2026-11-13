<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationSync;
use App\Models\PeopleList;
use App\Models\Person;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.outbound.allow_private' => true]); // no real DNS lookups in tests; the guard itself is tested below with literal addresses
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    protected function urlQuery(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);

        return $q;
    }

    // ----------------------------------------------------------------- fakes of the four platforms

    /** A new fake that replaces any earlier one (Http::fake keeps the first stub that matches, so a second call would not change anything). */
    protected function http(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
    }

    /** The envelope itself: json() shows only what is inside it. */
    protected function raw($response): array
    {
        return json_decode($response->getContent(), true);
    }

    protected function fakeWoo(int $pages = 1, int $perPage = 2): void
    {
        $this->http();
        Http::fake([
            '*/wp-json/wc/v3/customers*' => function (Request $r) use ($pages, $perPage) {
                $page = (int) ($this->urlQuery($r)['page'] ?? 1);
                $per = $perPage;
                $rows = [];
                for ($i = 1; $i <= $per; $i++) {
                    $n = ($page - 1) * $per + $i;
                    if ($page <= $pages) {
                        $rows[] = ['id' => $n, 'email' => "woo$n@example.com", 'first_name' => "Woo$n", 'last_name' => 'Buyer', 'billing' => ['phone' => '+6140000'.str_pad((string) $n, 4, '0', STR_PAD_LEFT)]];
                    }
                }

                return Http::response($rows, 200, ['X-WP-TotalPages' => $pages]);
            },
            '*/wp-json/' => Http::response(['name' => 'Woo Test Shop', 'namespaces' => ['wp/v2', 'wc/v3']]),
        ]);
    }

    protected function shopifyNode(int $n, string $state): array
    {
        return ['node' => ['id' => "gid://shopify/Customer/$n", 'email' => "Shop$n@Example.com", 'firstName' => "Shop$n", 'lastName' => 'Buyer', 'phone' => null, 'tags' => ['vip'],
            'emailMarketingConsent' => ['marketingState' => $state], 'defaultAddress' => ['phone' => '+6150000'.str_pad((string) $n, 4, '0', STR_PAD_LEFT)]]];
    }

    // ----------------------------------------------------------------- the catalogue

    public function test_the_catalogue_lists_the_store_platforms_with_their_form_and_steps(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->assertOk()->json());

        $this->assertSame(['shopify', 'woocommerce', 'magento', 'wordpress', 'ghost', 'salesforce', 'activecampaign'], $list->where('uses_url', true)->pluck('key')->values()->all());
        foreach ($list as $p) {
            $this->assertNotEmpty($p['fields']);
            $this->assertNotEmpty($p['steps']);
            $this->assertNotEmpty($p['consent_note']);
        }
        foreach ($list->where('uses_url', true) as $p) {
            $this->assertSame('url', $p['fields'][0]['key']);
        }
        $this->assertSame(['roles'], array_column($list->firstWhere('key', 'wordpress')['settings'], 'key'));
    }

    // ----------------------------------------------------------------- WooCommerce: connect and import

    public function test_connecting_woocommerce_tests_it_stores_the_keys_encrypted_and_imports_the_customers_as_people(): void
    {
        $this->fakeWoo(1, 2);
        $list = $this->postJson('/api/lists', ['name' => 'Shoppers'], $this->h)->assertCreated()->json('id');

        $r = $this->postJson('/api/integrations', [
            'provider' => 'woocommerce', 'url' => 'shop.test/wp-admin', 'consumer_key' => 'ck_abc1234', 'consumer_secret' => 'cs_secret9999',
            'list_id' => $list, 'tags' => ['Imported'],
        ], $this->h)->assertCreated();

        $this->assertSame('Woo Test Shop', $r->json('name'));
        $this->assertSame('https://shop.test', $r->json('store_url')); // /wp-admin was dropped
        $this->assertSame('connected', $r->json('status'));
        $this->assertSame(['consumer_key' => '••••1234', 'consumer_secret' => '••••9999'], $r->json('secret_hints'));
        $this->assertStringNotContainsString('cs_secret9999', $r->getContent()); // the credentials never come back

        // the credentials are encrypted in the database
        $raw = DB::table('integrations')->value('secrets');
        $this->assertStringNotContainsString('cs_secret9999', $raw);
        $this->assertSame('cs_secret9999', Integration::withoutGlobalScopes()->first()->secrets['consumer_secret']);

        // the import ran (the test queue is synchronous): two people, from woocommerce, on the list, tagged
        $people = Person::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertSame(['woo1@example.com', 'woo2@example.com'], $people->pluck('email')->all());
        $this->assertSame(['woocommerce', 'woocommerce'], $people->pluck('source')->all());
        $this->assertSame('Woo1', $people[0]->first_name);
        $this->assertSame('+61400000001', $people[0]->phone);
        $this->assertSame([$list], $people[0]->lists()->pluck('people_lists.id')->all());
        $this->assertEqualsCanonicalizing(['WooCommerce', 'Imported'], $people[0]->tags()->pluck('name')->all());
        // WooCommerce records no marketing permission, so the safe default subscribes nobody
        $this->assertSame([false, false], $people->map(fn ($p) => (bool) $p->is_subscribed)->all());

        $sync = IntegrationSync::withoutGlobalScopes()->first();
        $this->assertSame(['success', 'connect', true, 2, 2, 0], [$sync->status, $sync->trigger, $sync->full, $sync->fetched, $sync->created, $sync->updated]);
    }

    public function test_everyone_mode_needs_the_persons_confirmation_and_then_subscribes_new_people(): void
    {
        $this->fakeWoo(1, 2);
        $body = ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'ck_a', 'consumer_secret' => 'cs_b', 'subscribe_mode' => 'all'];

        $this->postJson('/api/integrations', $body, $this->h)->assertStatus(422)->assertJsonValidationErrors('confirm_permission');
        $this->assertSame(0, Integration::withoutGlobalScopes()->count());

        $this->postJson('/api/integrations', $body + ['confirm_permission' => true], $this->h)->assertCreated();
        $this->assertSame([true, true], Person::withoutGlobalScopes()->orderBy('id')->get()->map(fn ($p) => (bool) $p->is_subscribed)->all());
    }

    public function test_a_connection_that_does_not_work_is_refused_with_the_platforms_words_and_nothing_is_saved(): void
    {
        $this->http();
        Http::fake([
            '*/wp-json/wc/v3/customers*' => Http::response(['code' => 'woocommerce_rest_cannot_view'], 401),
            '*/wp-json/' => Http::response(['name' => 'Shop', 'namespaces' => ['wc/v3']]),
        ]);

        $r = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'bad', 'consumer_secret' => 'bad'], $this->h)->assertStatus(422);
        $this->assertStringContainsString('refused the key', $r->json('message'));
        $this->assertSame(0, Integration::withoutGlobalScopes()->count());
        $this->assertSame(0, Person::withoutGlobalScopes()->count());

        $this->http();
        Http::fake(['*/wp-json/' => Http::response(['name' => 'Blog', 'namespaces' => ['wp/v2']])]); // WordPress without WooCommerce
        $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://blog.test', 'consumer_key' => 'a', 'consumer_secret' => 'b'], $this->h)
            ->assertStatus(422)->assertJsonPath('message', 'WooCommerce is not available on this site (no WooCommerce REST API). Is the plugin active?');
    }

    public function test_private_addresses_and_plain_http_are_refused_so_credentials_never_go_where_they_should_not(): void
    {
        config(['services.outbound.allow_private' => false]);
        Http::fake();

        $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://10.0.0.5', 'consumer_key' => 'a', 'consumer_secret' => 'b'], $this->h)
            ->assertStatus(422)->assertJsonPath('message', 'Private or local addresses are not allowed.');
        $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'http://93.184.216.34', 'consumer_key' => 'a', 'consumer_secret' => 'b'], $this->h)
            ->assertStatus(422)->assertJsonPath('message', 'Use an https:// address. Credentials are only sent over a secure connection.');
        Http::assertNothingSent();
    }

    public function test_a_long_import_runs_in_chunks_and_still_imports_everyone_once(): void
    {
        $pages = \App\Jobs\SyncIntegrationJob::PAGES_PER_JOB + 3; // more than one job's share
        $this->fakeWoo($pages, 100);

        $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'a', 'consumer_secret' => 'b'], $this->h)->assertCreated();

        $this->assertSame($pages * 100, Person::withoutGlobalScopes()->count());
        $sync = IntegrationSync::withoutGlobalScopes()->first();
        $this->assertSame(['success', $pages * 100, null], [$sync->status, $sync->fetched, $sync->cursor]);
        $this->assertSame(1, IntegrationSync::withoutGlobalScopes()->count()); // one run, several jobs
    }

    // ----------------------------------------------------------------- Shopify

    public function test_shopify_customers_are_read_page_by_page_with_their_marketing_state(): void
    {
        $this->http();
        Http::fake(function (Request $r) {
            $vars = (array) ($r->data()['variables'] ?? []);
            if (str_contains($r->data()['query'] ?? '', 'shop {')) {
                return Http::response(['data' => ['shop' => ['name' => 'Shop Test']]]);
            }
            $first = empty($vars['after']);

            return Http::response(['data' => ['customers' => [
                'edges' => $first ? [$this->shopifyNode(1, 'SUBSCRIBED'), $this->shopifyNode(2, 'NOT_SUBSCRIBED')] : [$this->shopifyNode(3, 'UNSUBSCRIBED')],
                'pageInfo' => ['hasNextPage' => $first, 'endCursor' => $first ? 'cursor-1' : null],
            ]]]);
        });

        $r = $this->postJson('/api/integrations', ['provider' => 'shopify', 'url' => 'my-shop', 'access_token' => 'shpat_token0000'], $this->h)->assertCreated();
        $this->assertSame('https://my-shop.myshopify.com', $r->json('store_url'));
        $this->assertSame('Shop Test', $r->json('name'));

        $p = Person::withoutGlobalScopes()->orderBy('id')->get()->keyBy('email');
        $this->assertSame(['shop1@example.com', 'shop2@example.com', 'shop3@example.com'], $p->keys()->all()); // lower-cased, all three pages
        // on the default "only people who agreed": only the subscribed customer is subscribed
        $this->assertSame([true, false, false], $p->map(fn ($x) => (bool) $x->is_subscribed)->values()->all());
        $this->assertSame('+61500000001', $p['shop1@example.com']->phone); // from the default address
        $this->assertSame('shopify', $p['shop1@example.com']->source);
        $this->assertTrue($p['shop1@example.com']->tags()->where('name', 'Shopify')->exists());
        $this->assertFalse($p['shop1@example.com']->tags()->where('name', 'vip')->exists()); // the store's own tags are not copied
    }

    public function test_shopify_asks_only_for_customers_changed_since_the_last_sync_and_a_full_import_asks_for_all(): void
    {
        $seen = [];
        $this->http();
        Http::fake(function (Request $r) use (&$seen) {
            if (str_contains($r->data()['query'] ?? '', 'shop {')) {
                return Http::response(['data' => ['shop' => ['name' => 'S']]]);
            }
            $seen[] = ((array) ($r->data()['variables'] ?? []))['query'] ?? null;

            return Http::response(['data' => ['customers' => ['edges' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]]]]);
        });
        $id = $this->postJson('/api/integrations', ['provider' => 'shopify', 'url' => 'my-shop', 'access_token' => 'shpat_x'], $this->h)->assertCreated()->json('id');
        $seen = [];

        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202);
        $this->assertMatchesRegularExpression("/^updated_at:>='\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ'$/", $seen[0]);

        $seen = [];
        $this->postJson("/api/integrations/$id/sync", ['full' => true], $this->h)->assertStatus(202);
        $this->assertNull($seen[0]);
    }

    public function test_shopify_without_the_customer_permission_says_what_to_change(): void
    {
        $this->http();
        Http::fake(function (Request $r) {
            return str_contains($r->data()['query'] ?? '', 'shop {')
                ? Http::response(['data' => ['shop' => ['name' => 'S']]])
                : Http::response(['errors' => [['message' => 'Access denied for customers field.', 'extensions' => ['code' => 'ACCESS_DENIED']]]]);
        });

        $this->postJson('/api/integrations', ['provider' => 'shopify', 'url' => 'my-shop', 'access_token' => 'shpat_x'], $this->h)
            ->assertStatus(422)->assertJsonPath('message', 'The access token cannot read customers. Give the custom app the read_customers permission and install it again.');
        $this->postJson('/api/integrations', ['provider' => 'shopify', 'url' => 'not a store!', 'access_token' => 'x'], $this->h)->assertStatus(422);
    }

    // ----------------------------------------------------------------- WordPress and Magento

    public function test_wordpress_imports_only_the_chosen_roles_and_splits_a_display_name(): void
    {
        $asked = [];
        $this->http();
        Http::fake([
            '*/wp-json/wp/v2/users/me*' => Http::response(['id' => 1]),
            '*/wp-json/wp/v2/users*' => function (Request $r) use (&$asked) {
                $q = $this->urlQuery($r);
                $asked[] = $q['roles'] ?? null;
                if ((int) ($q['page'] ?? 1) > 1) {
                    return Http::response(['code' => 'rest_post_invalid_page_number'], 400);
                }

                return Http::response([
                    ['id' => 5, 'email' => 'Member@Example.com', 'first_name' => 'Mia', 'last_name' => 'Member', 'name' => 'Mia Member'],
                    ['id' => 6, 'email' => 'nofirst@example.com', 'first_name' => '', 'last_name' => '', 'name' => 'Nora Nameonly'],
                ], 200, ['X-WP-TotalPages' => 1]);
            },
            '*/wp-json/' => Http::response(['name' => 'My Blog']),
        ]);

        $this->postJson('/api/integrations', [
            'provider' => 'wordpress', 'url' => 'https://blog.test/wp-json/', 'username' => 'admin', 'app_password' => 'abcd efgh ijkl', 'roles' => ['subscriber', 'author'],
        ], $this->h)->assertCreated()->assertJsonPath('name', 'My Blog');

        $this->assertContains('subscriber,author', $asked);
        $p = Person::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertSame(['member@example.com', 'nofirst@example.com'], $p->pluck('email')->all());
        $this->assertSame(['Nora', 'Nameonly'], [$p[1]->first_name, $p[1]->last_name]);
        $this->assertSame('wordpress', $p[0]->source);

        // an unknown role is refused
        $this->postJson('/api/integrations', ['provider' => 'wordpress', 'url' => 'https://blog.test', 'username' => 'a', 'app_password' => 'b', 'roles' => ['hacker']], $this->h)->assertStatus(422)->assertJsonValidationErrors('roles.0');
    }

    public function test_magento_customers_are_paged_and_the_newsletter_status_decides_the_subscription(): void
    {
        $this->http();
        Http::fake(function (Request $r) {
            $c = $this->urlQuery($r)['searchCriteria'];
            $page = (int) ($c['currentPage'] ?? 1);
            $size = (int) ($c['pageSize'] ?? 100);
            $items = [];
            for ($n = ($page - 1) * $size + 1; $n <= min($page * $size, 250); $n++) { // 250 customers in all
                $items[] = ['id' => $n, 'email' => "m$n@example.com", 'firstname' => 'Mag', 'lastname' => "No$n",
                    'addresses' => $n === 1 ? [['telephone' => ''], ['telephone' => '+61460000001']] : [],
                    'extension_attributes' => $n === 1 ? ['is_subscribed' => true] : ($n === 2 ? ['is_subscribed' => false] : [])];
            }

            return Http::response(['items' => $items, 'total_count' => 250]);
        });

        $this->postJson('/api/integrations', ['provider' => 'magento', 'url' => 'https://store.test/index.php', 'access_token' => 'mag_token_1', 'sync_every' => 0], $this->h)->assertCreated();

        $p = Person::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertCount(250, $p); // three pages: 100, 100 and 50
        $this->assertSame(['m1@example.com', 'm2@example.com', 'm3@example.com'], $p->take(3)->pluck('email')->all());
        $this->assertSame([true, false, false], $p->take(3)->map(fn ($x) => (bool) $x->is_subscribed)->all()); // subscribed, not subscribed, no information
        $this->assertSame('+61460000001', $p[0]->phone);
        $this->assertSame('https://store.test', Integration::withoutGlobalScopes()->first()->store_url);
    }

    // ----------------------------------------------------------------- the importer's rules

    public function test_existing_people_are_completed_never_overwritten_never_resubscribed_and_a_store_side_unsubscribe_is_honoured(): void
    {
        $this->fakeWoo(1, 1); // one customer: woo1@example.com, Woo1 Buyer, +61400000001
        $mk = fn ($attrs) => (new Person)->forceFill(['company_id' => \App\Models\Company::value('id')] + $attrs)->save();
        $mk(['email' => 'woo1@example.com', 'first_name' => 'Kept', 'last_name' => null, 'phone' => null, 'is_subscribed' => false, 'source' => 'form']);

        $id = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'a', 'consumer_secret' => 'b', 'subscribe_mode' => 'all', 'confirm_permission' => true], $this->h)->assertCreated()->json('id');

        $this->assertSame(1, Person::withoutGlobalScopes()->count()); // matched by email, not duplicated
        $p = Person::withoutGlobalScopes()->first();
        $this->assertSame(['Kept', 'Buyer', '+61400000001', 'form'], [$p->first_name, $p->last_name, $p->phone, $p->source]); // blanks filled, nothing overwritten
        $this->assertFalse((bool) $p->is_subscribed); // even on "everyone", someone who opted out here stays out
        $sync = IntegrationSync::withoutGlobalScopes()->first();
        $this->assertSame([0, 1], [$sync->created, $sync->updated]);

        // Shopify says this customer unsubscribed: they are unsubscribed here too, even though they were subscribed
        $p->forceFill(['is_subscribed' => true])->save();
        $this->http();
        Http::fake(function (Request $r) {
            return str_contains($r->data()['query'] ?? '', 'shop {')
                ? Http::response(['data' => ['shop' => ['name' => 'S']]])
                : Http::response(['data' => ['customers' => ['edges' => [['node' => ['id' => 'gid://shopify/Customer/9', 'email' => 'woo1@example.com', 'firstName' => null, 'lastName' => null, 'phone' => null, 'tags' => [],
                    'emailMarketingConsent' => ['marketingState' => 'UNSUBSCRIBED'], 'defaultAddress' => null]]], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]]]]);
        });
        $this->postJson('/api/integrations', ['provider' => 'shopify', 'url' => 'my-shop', 'access_token' => 'shpat_x'], $this->h)->assertCreated();
        $this->assertFalse((bool) Person::withoutGlobalScopes()->first()->fresh()->is_subscribed);
    }

    public function test_customers_without_a_usable_email_or_phone_are_skipped_and_phone_only_customers_are_kept(): void
    {
        $this->http();
        Http::fake([
            '*/wp-json/wc/v3/customers*' => Http::response([
                ['id' => 1, 'email' => '', 'first_name' => 'No', 'last_name' => 'Contact', 'billing' => ['phone' => '']],
                ['id' => 2, 'email' => 'not-an-email', 'first_name' => 'Bad', 'last_name' => 'Email', 'billing' => ['phone' => '']],
                ['id' => 3, 'email' => '', 'first_name' => 'Phone', 'last_name' => 'Only', 'billing' => ['phone' => '+61477000000']],
                ['id' => 4, 'email' => 'ok@example.com', 'first_name' => 'Ok', 'last_name' => 'Customer', 'billing' => ['phone' => '']],
            ], 200, ['X-WP-TotalPages' => 1]),
            '*/wp-json/' => Http::response(['name' => 'S', 'namespaces' => ['wc/v3']]),
        ]);

        $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'a', 'consumer_secret' => 'b'], $this->h)->assertCreated();

        $this->assertSame(['Phone', 'Ok'], Person::withoutGlobalScopes()->orderBy('id')->pluck('first_name')->all());
        $sync = IntegrationSync::withoutGlobalScopes()->first();
        $this->assertSame([4, 2, 2], [$sync->fetched, $sync->created, $sync->skipped]);
    }

    public function test_importing_does_not_start_workflows_unless_the_connection_says_so(): void
    {
        $this->fakeWoo(1, 1);
        $wf = $this->postJson('/api/workflows', ['name' => 'Welcome', 'trigger_type' => 'person_created', 'trigger_config' => [], 'steps' => [['type' => 'add_tag', 'tag_id' => $this->postJson('/api/tags', ['name' => 'Welcomed'], $this->h)->json('id')]]], $this->h)->assertCreated()->json('id');
        $this->postJson("/api/workflows/$wf/toggle", [], $this->h)->assertOk(); // new workflows start as drafts

        $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'a', 'consumer_secret' => 'b'], $this->h)->assertCreated();
        $this->assertSame(0, DB::table('workflow_runs')->where('workflow_id', $wf)->count()); // a whole store is not welcomed by surprise

        $this->fakeWoo(1, 2);
        $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://other.test', 'consumer_key' => 'a', 'consumer_secret' => 'b', 'run_automations' => true], $this->h)->assertCreated();
        $this->assertSame(1, DB::table('workflow_runs')->where('workflow_id', $wf)->count()); // woo2 is new; woo1 existed
    }

    // ----------------------------------------------------------------- managing connections

    public function test_settings_can_be_changed_credentials_are_replaced_only_when_given_and_disconnecting_keeps_the_people(): void
    {
        $this->fakeWoo(1, 2);
        $id = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'ck_old1111', 'consumer_secret' => 'cs_old2222', 'tags' => ['A'], 'import_now' => false], $this->h)->assertCreated()->json('id');
        $this->assertSame(0, Person::withoutGlobalScopes()->count()); // import_now=false: connected, nothing imported yet

        $r = $this->putJson("/api/integrations/$id", ['sync_every' => 360, 'tags' => ['B', 'C']], $this->h)->assertOk();
        $this->assertSame(360, $r->json('settings.sync_every'));
        $this->assertSame(['B', 'C'], $r->json('settings.tags'));
        $this->assertSame('consent', $r->json('settings.subscribe_mode')); // untouched settings stay
        $this->assertSame('cs_old2222', Integration::withoutGlobalScopes()->first()->secrets['consumer_secret']); // no credentials sent: the stored ones stay

        // a new secret is tested first; a wrong one is refused and the old one stays
        $this->http();
        Http::fake(['*/wp-json/wc/v3/customers*' => Http::response([], 401), '*/wp-json/' => Http::response(['name' => 'S', 'namespaces' => ['wc/v3']])]);
        $this->putJson("/api/integrations/$id", ['consumer_secret' => 'cs_wrong'], $this->h)->assertStatus(422);
        $this->assertSame('cs_old2222', Integration::withoutGlobalScopes()->first()->secrets['consumer_secret']);
        $this->fakeWoo(1, 2);
        $this->putJson("/api/integrations/$id", ['consumer_secret' => 'cs_new3333'], $this->h)->assertOk()->assertJsonPath('secret_hints.consumer_secret', '••••3333');

        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202);
        $this->assertSame(2, Person::withoutGlobalScopes()->count());
        $this->deleteJson("/api/integrations/$id", [], $this->h)->assertNoContent();
        $this->assertSame(2, Person::withoutGlobalScopes()->count()); // the people stay
        $this->assertSame([], Integration::withoutGlobalScopes()->withTrashed()->first()->secrets); // and the credentials are gone
        $this->assertSame([], $this->getJson('/api/integrations', $this->h)->json());
    }

    public function test_the_connection_can_be_checked_again_and_a_failure_is_remembered(): void
    {
        $this->fakeWoo(1, 1);
        $id = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'a', 'consumer_secret' => 'b', 'import_now' => false], $this->h)->assertCreated()->json('id');
        $this->assertSame('The connection works.', $this->raw($this->postJson("/api/integrations/$id/check", [], $this->h)->assertOk())['message']);

        $this->http();
        Http::fake(['*/wp-json/wc/v3/customers*' => Http::response([], 401), '*/wp-json/' => Http::response(['name' => 'S', 'namespaces' => ['wc/v3']])]);
        $this->postJson("/api/integrations/$id/check", [], $this->h)->assertStatus(422);
        $row = collect($this->getJson('/api/integrations', $this->h)->json())->first();
        $this->assertSame('error', $row['status']);
        $this->assertStringContainsString('refused the key', $row['last_error']);
    }

    public function test_a_failing_import_is_recorded_on_the_run_and_the_connection(): void
    {
        $this->fakeWoo(1, 1);
        $id = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'a', 'consumer_secret' => 'b', 'import_now' => false], $this->h)->assertCreated()->json('id');

        $this->http();
        Http::fake(['*/wp-json/wc/v3/customers*' => Http::response([], 500)]);
        $this->postJson("/api/integrations/$id/sync", [], $this->h)->assertStatus(202);

        $run = IntegrationSync::withoutGlobalScopes()->latest('id')->first();
        $this->assertSame('failed', $run->status);
        $this->assertStringContainsString('had a problem', $run->error);
        $this->assertSame('error', Integration::withoutGlobalScopes()->first()->status);
    }

    public function test_the_sync_history_lists_runs_for_one_connection_and_for_all(): void
    {
        $this->fakeWoo(1, 1);
        $id = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'a', 'consumer_secret' => 'b'], $this->h)->assertCreated()->json('id');
        $this->postJson("/api/integrations/$id/sync", ['full' => true], $this->h)->assertStatus(202);

        $one = $this->getJson("/api/integrations/$id/syncs", $this->h)->assertOk()->json();
        $this->assertSame(2, $one['total']);
        $all = $this->getJson('/api/integration-syncs?status=success', $this->h)->assertOk()->json('data');
        $this->assertSame('WooCommerce', $all[0]['integration']['provider_label']);
        $this->assertSame('Woo Test Shop', $all[0]['integration']['name']);
        $this->assertSame([], $this->getJson('/api/integration-syncs?status=failed', $this->h)->json('data'));
    }

    public function test_another_company_cannot_see_or_change_a_connection(): void
    {
        $this->fakeWoo(1, 1);
        $id = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'a', 'consumer_secret' => 'b'], $this->h)->assertCreated()->json('id');

        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', ['company_name' => 'Other', 'name' => 'O', 'email' => 'x@y.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->json('token');
        $other = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];

        $this->assertSame([], $this->getJson('/api/integrations', $other)->json());
        $this->putJson("/api/integrations/$id", ['sync_every' => 15], $other)->assertNotFound();
        $this->postJson("/api/integrations/$id/sync", [], $other)->assertNotFound();
        $this->deleteJson("/api/integrations/$id", [], $other)->assertNotFound();
        $this->assertSame([], $this->getJson('/api/integration-syncs', $other)->json('data'));
    }

    // ----------------------------------------------------------------- the schedule

    public function test_the_scheduled_command_starts_only_connections_that_are_due(): void
    {
        $this->fakeWoo(1, 1);
        $due = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://a.test', 'consumer_key' => 'a', 'consumer_secret' => 'b', 'sync_every' => 15], $this->h)->json('id');
        $recent = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://b.test', 'consumer_key' => 'a', 'consumer_secret' => 'b', 'sync_every' => 15], $this->h)->json('id');
        $manual = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://c.test', 'consumer_key' => 'a', 'consumer_secret' => 'b', 'sync_every' => 0], $this->h)->json('id');
        Integration::withoutGlobalScopes()->whereKey($due)->update(['last_synced_at' => now()->subMinutes(30)]);
        Integration::withoutGlobalScopes()->whereKey($recent)->update(['last_synced_at' => now()->subMinutes(5)]);
        Integration::withoutGlobalScopes()->whereKey($manual)->update(['last_synced_at' => now()->subDays(3)]);
        $before = IntegrationSync::withoutGlobalScopes()->count();

        $this->artisan('integrations:sync')->expectsOutput('Started 1 sync.')->assertSuccessful();

        $new = IntegrationSync::withoutGlobalScopes()->latest('id')->first();
        $this->assertSame([$before + 1, $due, 'scheduled', false], [IntegrationSync::withoutGlobalScopes()->count(), $new->integration_id, $new->trigger, $new->full]);
        $this->assertNotNull($new->since); // an automatic sync only asks for what changed
    }

    // ----------------------------------------------------------------- history

    public function test_changes_to_a_connection_are_in_its_history_without_the_credentials(): void
    {
        $this->fakeWoo(1, 1);
        $id = $this->postJson('/api/integrations', ['provider' => 'woocommerce', 'url' => 'https://shop.test', 'consumer_key' => 'ck_plain_key', 'consumer_secret' => 'cs_plain_secret', 'import_now' => false], $this->h)->assertCreated()->json('id');
        $this->putJson("/api/integrations/$id", ['sync_every' => 15], $this->h)->assertOk();

        $rows = collect($this->getJson("/api/history/integration/$id", $this->h)->assertOk()->json());
        $this->assertGreaterThanOrEqual(2, $rows->count());
        $this->assertStringNotContainsString('cs_plain_secret', json_encode($rows));
        $this->assertStringNotContainsString('ck_plain_key', json_encode(DB::table('integrations_history')->get()));
        $this->assertSame('WooCommerce · Woo Test Shop', DB::table('integrations_history')->where('id', $id)->value('record_label'));
    }
}
