<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The second wave of platforms: BigCommerce, Ecwid, Ghost, Squarespace, Webflow, Wix, Pipedrive, Salesforce, ActiveCampaign, Close, MailerLite, Kit, Square, Paddle, Chargebee, GoCardless. Responses are faked in each platform's documented shape. */
class IntegrationMorePlatformsTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.outbound.allow_private' => true]);
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
        Http::swap(new Factory);
    }

    protected function q(Request $r): array
    {
        parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);

        return $q;
    }

    protected function path(Request $r): string
    {
        return (string) parse_url($r->url(), PHP_URL_PATH);
    }

    protected function connect(array $body, int $status = 201)
    {
        return $this->postJson('/api/integrations', $body, $this->h)->assertStatus($status);
    }

    /** @return array<string, Person> */
    protected function people(): array
    {
        return Person::withoutGlobalScopes()->orderBy('id')->get()->keyBy('email')->all();
    }

    protected function subscribed(): array
    {
        return array_map(fn ($p) => (bool) $p->is_subscribed, $this->people());
    }

    public function test_bigcommerce_pages_by_number_and_sends_the_token_and_store_hash(): void
    {
        $sent = [];
        Http::fake(function (Request $r) use (&$sent) {
            $sent[] = [$this->path($r), $r->header('X-Auth-Token')[0] ?? null];
            if ($this->path($r) === '/stores/abc123/v2/store') {
                return Http::response(['name' => 'Big Shop']);
            }
            $page = (int) ($this->q($r)['page'] ?? 1);

            return Http::response(['data' => $page === 1
                ? [['id' => 1, 'email' => 'A@x.com', 'first_name' => 'Al', 'last_name' => 'B', 'phone' => '0400']]
                : [['id' => 2, 'email' => 'b@x.com', 'first_name' => 'Bo', 'last_name' => '', 'phone' => '']],
                'meta' => ['pagination' => ['current_page' => $page, 'total_pages' => 2]]]);
        });

        $this->connect(['provider' => 'bigcommerce', 'store_hash' => 'abc123', 'access_token' => 'tok'])->assertJsonPath('name', 'Big Shop')->assertJsonPath('uses_url', false);
        $this->assertSame(['a@x.com', 'b@x.com'], array_keys($this->people()));
        $this->assertSame('tok', $sent[0][1]);
        $this->connect(['provider' => 'bigcommerce', 'store_hash' => 'abc123', 'access_token' => 'other'], 422); // same store
        $this->connect(['provider' => 'bigcommerce', 'store_hash' => 'bad hash!', 'access_token' => 'tok'], 422);
    }

    public function test_ecwid_reads_the_marketing_choice_and_pages_by_offset(): void
    {
        Http::fake(function (Request $r) {
            if ($this->path($r) === '/api/v3/777/profile') {
                return Http::response(['company' => ['companyName' => 'Ecwid Co']]);
            }
            $offset = (int) ($this->q($r)['offset'] ?? 0);

            return Http::response(['total' => 150, 'items' => $offset === 0
                ? [['id' => 1, 'email' => 'yes@x.com', 'acceptMarketing' => true, 'billingPerson' => ['firstName' => 'Y', 'lastName' => 'Es', 'phone' => '1']]]
                : [['id' => 2, 'email' => 'no@x.com', 'acceptMarketing' => false, 'billingPerson' => ['name' => 'No Way']]]]);
        });

        $this->connect(['provider' => 'ecwid', 'store_id' => '777', 'secret_token' => 'secret_x'])->assertJsonPath('name', 'Ecwid Co');
        $this->assertSame(['yes@x.com' => true, 'no@x.com' => false], $this->subscribed());
        $this->assertSame(['No', 'Way'], [$this->people()['no@x.com']->first_name, $this->people()['no@x.com']->last_name]);
    }

    public function test_ghost_signs_a_token_and_maps_the_newsletter_flag(): void
    {
        $auth = null;
        Http::fake(function (Request $r) use (&$auth) {
            $auth = $r->header('Authorization')[0] ?? null;
            if (str_contains($this->path($r), '/site/')) {
                return Http::response(['site' => ['title' => 'My Blog']]);
            }

            return Http::response(['members' => [
                ['id' => 'm1', 'email' => 'sub@x.com', 'name' => 'Sue Reader', 'subscribed' => true],
                ['id' => 'm2', 'email' => 'off@x.com', 'name' => 'Ollie', 'subscribed' => false],
            ], 'meta' => ['pagination' => ['next' => null]]]);
        });

        $key = 'abcdef0123456789abcdef01:'.str_repeat('ab', 32);
        $this->connect(['provider' => 'ghost', 'url' => 'blog.test', 'admin_key' => $key])->assertJsonPath('name', 'My Blog')->assertJsonPath('store_url', 'https://blog.test');
        $this->assertMatchesRegularExpression('/^Ghost [\w-]+\.[\w-]+\.[\w-]+$/', $auth);
        $this->assertSame(['sub@x.com' => true, 'off@x.com' => false], $this->subscribed());
        $this->connect(['provider' => 'ghost', 'url' => 'https://other.test', 'admin_key' => 'nocolon'], 422);
    }

    public function test_squarespace_follows_the_cursor(): void
    {
        Http::fake(function (Request $r) {
            if (str_contains($this->path($r), 'authorization/website')) {
                return Http::response(['title' => 'Sq Site']);
            }
            $cursor = $this->q($r)['cursor'] ?? null;

            return Http::response($cursor
                ? ['profiles' => [['id' => 'p2', 'email' => 'two@x.com', 'firstName' => 'Two', 'lastName' => 'T', 'acceptsMarketing' => false]], 'pagination' => ['hasNextPage' => false]]
                : ['profiles' => [['id' => 'p1', 'email' => 'one@x.com', 'firstName' => 'One', 'lastName' => 'O', 'acceptsMarketing' => true]], 'pagination' => ['hasNextPage' => true, 'nextPageCursor' => 'c2']]);
        });

        $this->connect(['provider' => 'squarespace', 'api_key' => 'sqk'])->assertJsonPath('name', 'Sq Site');
        $this->assertSame(['one@x.com' => true, 'two@x.com' => false], $this->subscribed());
    }

    public function test_webflow_users_are_paged_by_offset(): void
    {
        Http::fake(function (Request $r) {
            if ($this->path($r) === '/v2/sites/site1') {
                return Http::response(['displayName' => 'WF Site']);
            }
            $offset = (int) ($this->q($r)['offset'] ?? 0);

            return Http::response(['users' => [['id' => 'u'.$offset, 'data' => ['email' => "u$offset@x.com", 'name' => 'Web Flow']]], 'pagination' => ['total' => 150]]);
        });

        $this->connect(['provider' => 'webflow', 'site_id' => 'site1', 'api_token' => 't'])->assertJsonPath('name', 'WF Site');
        $this->assertSame(['u0@x.com', 'u100@x.com'], array_keys($this->people()));
    }

    public function test_wix_maps_the_subscription_status(): void
    {
        $siteHeader = null;
        Http::fake(function (Request $r) use (&$siteHeader) {
            $siteHeader = $r->header('wix-site-id')[0] ?? null;

            return Http::response(['contacts' => [
                ['id' => 'c1', 'info' => ['name' => ['first' => 'Wi', 'last' => 'X'], 'emails' => ['items' => [['email' => 'wi@x.com', 'primary' => true]]]], 'primaryEmail' => ['email' => 'wi@x.com', 'subscriptionStatus' => 'SUBSCRIBED']],
                ['id' => 'c2', 'info' => ['emails' => ['items' => [['email' => 'out@x.com']]]], 'primaryEmail' => ['email' => 'out@x.com', 'subscriptionStatus' => 'UNSUBSCRIBED']],
                ['id' => 'c3', 'info' => ['emails' => ['items' => [['email' => 'new@x.com']]]], 'primaryEmail' => ['email' => 'new@x.com', 'subscriptionStatus' => 'NOT_SET']],
            ], 'pagingMetadata' => ['total' => 3]]);
        });

        $this->connect(['provider' => 'wix', 'site_id' => 'site-uuid-1', 'api_key' => 'IST.k']);
        $this->assertSame('site-uuid-1', $siteHeader);
        $this->assertSame(['wi@x.com' => true, 'out@x.com' => false, 'new@x.com' => false], $this->subscribed());
    }

    public function test_pipedrive_uses_the_first_email_and_the_marketing_status(): void
    {
        Http::fake(function (Request $r) {
            if (str_contains($this->path($r), '/users/me')) {
                return Http::response(['data' => ['company_name' => 'Pipe Co']]);
            }

            return Http::response(['data' => [
                ['id' => 1, 'first_name' => 'Pi', 'last_name' => 'Pe', 'email' => [['value' => 'old@x.com', 'primary' => false], ['value' => 'main@x.com', 'primary' => true]], 'phone' => [['value' => '555']], 'marketing_status' => 'subscribed'],
                ['id' => 2, 'first_name' => 'No', 'last_name' => 'Mail', 'email' => [], 'phone' => [], 'marketing_status' => 'unsubscribed'],
                ['id' => 3, 'first_name' => 'Un', 'last_name' => 'Sub', 'email' => [['value' => 'unsub@x.com']], 'phone' => [], 'marketing_status' => 'unsubscribed'],
            ], 'additional_data' => ['pagination' => ['more_items_in_collection' => false]]]);
        });

        $this->connect(['provider' => 'pipedrive', 'api_token' => 'tok', 'subscribe_mode' => 'all', 'confirm_permission' => true])->assertJsonPath('name', 'Pipe Co');
        $this->assertSame(['main@x.com' => true, 'unsub@x.com' => false], $this->subscribed()); // the person with no email is skipped, the opt-out is kept out even on "everyone"
    }

    public function test_salesforce_follows_the_next_records_url_and_keeps_email_opt_outs_unsubscribed(): void
    {
        $queries = [];
        Http::fake(function (Request $r) use (&$queries) {
            $queries[] = $r->url();
            if (str_contains($r->url(), '/query/01g-next')) {
                return Http::response(['done' => true, 'records' => [['Id' => 'c3', 'Email' => 'c@x.com', 'FirstName' => 'C', 'LastName' => 'D', 'Phone' => '1', 'MobilePhone' => null, 'HasOptedOutOfEmail' => false]]]);
            }
            $first = ['Id' => 'c1', 'Email' => 'a@x.com', 'FirstName' => 'A', 'LastName' => 'B', 'Phone' => null, 'MobilePhone' => '+61400', 'HasOptedOutOfEmail' => true];

            return str_contains($r->url(), 'LIMIT') ? Http::response(['records' => [], 'done' => true]) : Http::response(['done' => false, 'nextRecordsUrl' => '/services/data/v60.0/query/01g-next', 'records' => [$first]]);
        });

        $this->connect(['provider' => 'salesforce', 'url' => 'https://acme.my.salesforce.com', 'access_token' => 'tok', 'subscribe_mode' => 'all', 'confirm_permission' => true])->assertJsonPath('name', 'acme.my.salesforce.com');
        $this->assertSame(['a@x.com' => false, 'c@x.com' => true], $this->subscribed());
        $this->assertSame('+61400', $this->people()['a@x.com']->phone);
    }

    public function test_activecampaign_pages_by_offset(): void
    {
        Http::fake(function (Request $r) {
            $offset = (int) ($this->q($r)['offset'] ?? 0);
            $limit = (int) ($this->q($r)['limit'] ?? 1);

            return Http::response(['contacts' => $limit === 1 ? [] : [['id' => (string) ($offset + 1), 'email' => 'ac'.$offset.'@x.com', 'firstName' => 'A', 'lastName' => 'C', 'phone' => '']], 'meta' => ['total' => '150']]);
        });

        $this->connect(['provider' => 'activecampaign', 'url' => 'acme.api-us1.com', 'api_key' => 'k'])->assertJsonPath('name', 'acme');
        $this->assertSame(['ac0@x.com', 'ac100@x.com'], array_keys($this->people()));
    }

    public function test_close_uses_basic_auth_and_skips(): void
    {
        $auth = null;
        Http::fake(function (Request $r) use (&$auth) {
            $auth = $r->header('Authorization')[0] ?? null;
            if (str_contains($this->path($r), '/me/')) {
                return Http::response(['organizations' => [['name' => 'Close Org']]]);
            }
            $skip = (int) ($this->q($r)['_skip'] ?? 0);

            return Http::response(['has_more' => $skip === 0, 'data' => [['id' => 'cont_'.$skip, 'name' => 'Cl Ose', 'emails' => [['email' => "cl$skip@x.com"]], 'phones' => [['phone' => '9']]]]]);
        });

        $this->connect(['provider' => 'close', 'api_key' => 'api_k'])->assertJsonPath('name', 'Close Org');
        $this->assertSame('Basic '.base64_encode('api_k:'), $auth);
        $this->assertSame(['cl0@x.com', 'cl100@x.com'], array_keys($this->people()));
    }

    public function test_mailerlite_maps_status_and_follows_the_cursor(): void
    {
        Http::fake(function (Request $r) {
            $cursor = $this->q($r)['cursor'] ?? null;

            return Http::response($cursor
                ? ['data' => [['id' => '3', 'email' => 'junk@x.com', 'status' => 'junk', 'fields' => []]], 'meta' => ['next_cursor' => null]]
                : ['data' => [['id' => '1', 'email' => 'act@x.com', 'status' => 'active', 'fields' => ['name' => 'Ml', 'last_name' => 'A', 'phone' => '7']], ['id' => '2', 'email' => 'unc@x.com', 'status' => 'unconfirmed', 'fields' => []]], 'meta' => ['next_cursor' => 'abc']]);
        });

        $this->connect(['provider' => 'mailerlite', 'api_token' => 't']);
        $this->assertSame(['act@x.com' => true, 'unc@x.com' => false, 'junk@x.com' => false], $this->subscribed());
    }

    public function test_kit_asks_for_every_status_and_follows_the_end_cursor(): void
    {
        $statuses = [];
        Http::fake(function (Request $r) use (&$statuses) {
            if ($this->path($r) === '/v4/account') {
                return Http::response(['account' => ['name' => 'Kit Co']]);
            }
            $statuses[] = $this->q($r)['status'] ?? null;
            $after = $this->q($r)['after'] ?? null;

            return Http::response($after
                ? ['subscribers' => [['id' => 2, 'email_address' => 'bounced@x.com', 'first_name' => 'B', 'state' => 'bounced']], 'pagination' => ['has_next_page' => false]]
                : ['subscribers' => [['id' => 1, 'email_address' => 'ok@x.com', 'first_name' => 'O', 'state' => 'active']], 'pagination' => ['has_next_page' => true, 'end_cursor' => 'WzFd']]);
        });

        $this->connect(['provider' => 'kit', 'api_key' => 'kit_k'])->assertJsonPath('name', 'Kit Co');
        $this->assertSame(['all', 'all'], $statuses);
        $this->assertSame(['ok@x.com' => true, 'bounced@x.com' => false], $this->subscribed());
    }

    public function test_square_reads_unsubscribes_and_the_cursor(): void
    {
        Http::fake(function (Request $r) {
            if (str_contains($this->path($r), 'merchants/me')) {
                return Http::response(['merchant' => ['business_name' => 'Sq Biz']]);
            }
            $cursor = $this->q($r)['cursor'] ?? null;

            return Http::response($cursor
                ? ['customers' => [['id' => 'c2', 'email_address' => 'sq2@x.com', 'given_name' => 'S', 'family_name' => 'Two', 'preferences' => ['email_unsubscribed' => true]]]]
                : ['customers' => [['id' => 'c1', 'email_address' => 'sq1@x.com', 'given_name' => 'S', 'family_name' => 'One', 'phone_number' => '+61']], 'cursor' => 'next']);
        });

        $this->connect(['provider' => 'square', 'access_token' => 'EAAA', 'subscribe_mode' => 'all', 'confirm_permission' => true])->assertJsonPath('name', 'Sq Biz');
        $this->assertSame(['sq1@x.com' => true, 'sq2@x.com' => false], $this->subscribed());
    }

    public function test_paddle_uses_the_sandbox_host_for_sandbox_keys_and_the_marketing_consent(): void
    {
        $hosts = [];
        Http::fake(function (Request $r) use (&$hosts) {
            $hosts[] = parse_url($r->url(), PHP_URL_HOST);
            $after = $this->q($r)['after'] ?? null;

            return Http::response($after
                ? ['data' => [['id' => 'ctm_2', 'email' => 'p2@x.com', 'name' => null, 'marketing_consent' => false]], 'meta' => ['pagination' => ['has_more' => false]]]
                : ['data' => [['id' => 'ctm_1', 'email' => 'p1@x.com', 'name' => 'Pa Ddle', 'marketing_consent' => true]], 'meta' => ['pagination' => ['has_more' => true]]]);
        });

        $this->connect(['provider' => 'paddle', 'api_key' => 'pdl_sdbx_apikey_1']);
        $this->assertSame('sandbox-api.paddle.com', $hosts[0]);
        $this->assertSame(['p1@x.com' => true, 'p2@x.com' => false], $this->subscribed());
    }

    public function test_chargebee_builds_its_address_from_the_site_and_pages_by_next_offset(): void
    {
        $auth = $host = null;
        Http::fake(function (Request $r) use (&$auth, &$host) {
            $auth = $r->header('Authorization')[0] ?? null;
            $host = parse_url($r->url(), PHP_URL_HOST);
            $offset = $this->q($r)['offset'] ?? null;

            return Http::response($offset
                ? ['list' => [['customer' => ['id' => 'cb2', 'email' => 'cb2@x.com', 'first_name' => 'C', 'last_name' => 'Two']]]]
                : ['list' => [['customer' => ['id' => 'cb1', 'email' => 'cb1@x.com', 'first_name' => 'C', 'last_name' => 'One', 'phone' => '1']]], 'next_offset' => '["1"]']);
        });

        $this->connect(['provider' => 'chargebee', 'site' => 'acme-test', 'api_key' => 'live_k'])->assertJsonPath('name', 'Acme-test (Chargebee)');
        $this->assertSame('acme-test.chargebee.com', $host);
        $this->assertSame('Basic '.base64_encode('live_k:'), $auth);
        $this->assertSame(['cb1@x.com', 'cb2@x.com'], array_keys($this->people()));
        $this->connect(['provider' => 'chargebee', 'site' => 'evil.com/x', 'api_key' => 'k'], 422);
    }

    public function test_gocardless_follows_the_after_cursor(): void
    {
        $version = null;
        Http::fake(function (Request $r) use (&$version) {
            $version = $r->header('GoCardless-Version')[0] ?? null;
            if (str_contains($this->path($r), 'creditors')) {
                return Http::response(['creditors' => [['name' => 'GC Ltd']]]);
            }
            $after = $this->q($r)['after'] ?? null;

            return Http::response($after
                ? ['customers' => [['id' => 'CU2', 'email' => 'g2@x.com', 'given_name' => 'G', 'family_name' => 'Two']], 'meta' => ['cursors' => ['after' => null]]]
                : ['customers' => [['id' => 'CU1', 'email' => 'g1@x.com', 'given_name' => 'G', 'family_name' => 'One', 'phone_number' => '+44']], 'meta' => ['cursors' => ['after' => 'CU1']]]);
        });

        $this->connect(['provider' => 'gocardless', 'access_token' => 'live_t'])->assertJsonPath('name', 'GC Ltd');
        $this->assertSame('2015-07-06', $version);
        $this->assertSame(['g1@x.com', 'g2@x.com'], array_keys($this->people()));
    }

    public function test_a_refused_key_is_explained_and_nothing_is_saved_for_every_new_platform(): void
    {
        Http::fake(['*' => Http::response(['error' => 'no'], 401)]);
        $key = 'abcdef0123456789abcdef01:'.str_repeat('ab', 32);

        foreach ([
            ['provider' => 'bigcommerce', 'store_hash' => 'abc', 'access_token' => 'x'],
            ['provider' => 'ecwid', 'store_id' => '1', 'secret_token' => 'x'],
            ['provider' => 'ghost', 'url' => 'g.test', 'admin_key' => $key],
            ['provider' => 'squarespace', 'api_key' => 'x'],
            ['provider' => 'webflow', 'site_id' => 's', 'api_token' => 'x'],
            ['provider' => 'wix', 'site_id' => 's', 'api_key' => 'x'],
            ['provider' => 'pipedrive', 'api_token' => 'x'],
            ['provider' => 'salesforce', 'url' => 's.test', 'access_token' => 'x'],
            ['provider' => 'activecampaign', 'url' => 'a.test', 'api_key' => 'x'],
            ['provider' => 'close', 'api_key' => 'x'],
            ['provider' => 'mailerlite', 'api_token' => 'x'],
            ['provider' => 'kit', 'api_key' => 'x'],
            ['provider' => 'square', 'access_token' => 'x'],
            ['provider' => 'paddle', 'api_key' => 'x'],
            ['provider' => 'chargebee', 'site' => 'acme', 'api_key' => 'x'],
            ['provider' => 'gocardless', 'access_token' => 'x'],
        ] as $case) {
            $message = json_decode($this->postJson('/api/integrations', $case, $this->h)->assertStatus(422)->getContent(), true)['message'] ?? '';
            $this->assertStringContainsString('refused', $message, $case['provider']);
        }
        $this->assertSame(0, Integration::withoutGlobalScope('company')->count());
    }
}
