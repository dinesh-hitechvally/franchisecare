<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** "Connect with …": the person approves on the platform and the app receives the keys itself. */
class IntegrationAutoConnectTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.outbound.allow_private' => true, 'app.url' => 'http://app.test', 'services.mail_oauth.frontend_url' => 'http://front.test',
            'services.integrations.mailchimp' => ['client_id' => 'mc-id', 'client_secret' => 'mc-secret'],
            'services.integrations.shopify' => ['client_id' => 'sh-id', 'client_secret' => 'sh-secret'],
            'services.integrations.klaviyo' => ['client_id' => 'kl-id', 'client_secret' => 'kl-secret'],
            'services.integrations.hubspot' => ['client_id' => 'hs-id', 'client_secret' => 'hs-secret'],
            'services.integrations.stripe' => ['client_id' => null, 'client_secret' => null], // not set up, whatever the developer's own .env holds
        ]);
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
        Http::swap(new Factory);
    }

    protected function start(array $body, int $status = 200): array
    {
        $r = $this->postJson('/api/integrations/oauth/start', $body, $this->h)->assertStatus($status);
        $url = $status === 200 ? $r->json('url') : '';
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);

        return [$url, $q];
    }

    /** What the platform's browser redirect does: an unauthenticated request to our callback. */
    protected function comeBack(array $query)
    {
        $this->app['auth']->forgetGuards();

        return $this->get('/api/oauth/integrations/callback?'.http_build_query($query));
    }

    protected function fakeMailchimp(?array &$tokenCalls = null): void
    {
        Http::fake(function (Request $r) use (&$tokenCalls) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            $host = parse_url($r->url(), PHP_URL_HOST);
            if ($host === 'login.mailchimp.com' && $path === '/oauth2/token') {
                $tokenCalls[] = $r->data();

                return Http::response(['access_token' => 'tok123', 'token_type' => 'bearer']);
            }
            if ($host === 'login.mailchimp.com') {
                return Http::response(['dc' => 'us21', 'login' => ['login_id' => 42]]);
            }

            return match (true) {
                $path === '/3.0/' => Http::response(['account_name' => 'Acme Mailchimp']),
                $path === '/3.0/lists' => Http::response(['lists' => [['id' => 'aud1']]]),
                $path === '/3.0/lists/aud1/members' => Http::response(['members' => [['id' => 'm1', 'email_address' => 'sub@example.com', 'status' => 'subscribed', 'merge_fields' => ['FNAME' => 'Sue']]], 'total_items' => 1]),
                preg_match('~^/3.0/lists/\w+$~', $path) === 1 => Http::response(['id' => 'x']),
                default => Http::response([], 404),
            };
        });
    }

    public function test_the_catalogue_says_which_platforms_can_connect_in_one_click(): void
    {
        config(['services.outbound.allow_private' => false]);
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->assertOk()->json())->keyBy('key');

        $this->assertSame(['mode' => 'oauth', 'available' => true, 'asks' => [], 'callback_url' => 'http://app.test/api/oauth/integrations/callback', 'env' => [['name' => 'MAILCHIMP_CLIENT_ID', 'example' => 'your-client-id'], ['name' => 'MAILCHIMP_CLIENT_SECRET', 'example' => 'your-client-secret']], 'scopes' => [], 'scope_notes' => [], 'can_check_scopes' => false], $list['mailchimp']['auto_connect']);
        $this->assertSame(['url'], $list['shopify']['auto_connect']['asks']);
        $this->assertFalse($list['stripe']['auto_connect']['available']); // no client id/secret in .env
        $this->assertFalse($list['woocommerce']['auto_connect']['available']); // the keys are posted back to an http address
        $this->assertTrue($list['wordpress']['auto_connect']['available']);
        $this->assertNull($list['brevo']['auto_connect']); // keys only
        $this->assertNull($list['ghost']['auto_connect']);
    }

    public function test_oauth_platforms_tell_the_callback_url_to_register_and_the_others_do_not(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key');

        foreach (['mailchimp', 'shopify', 'hubspot', 'klaviyo'] as $key) {
            $this->assertSame('http://app.test/api/oauth/integrations/callback', $list[$key]['auto_connect']['callback_url'], $key); // the address the platform's app must list as its redirect URL
        }
        $this->assertNull($list['woocommerce']['auto_connect']['callback_url']); // approved on its own page: nothing to register
        $this->assertNull($list['magento']['auto_connect']); // keys are pasted: no callback at all
    }
    public function test_the_help_shows_the_env_names_the_server_really_reads(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key');
        $config = file_get_contents(config_path('services.php'));
        $oauth = $list->filter(fn ($p) => ($p['auto_connect']['mode'] ?? null) === 'oauth');

        $this->assertGreaterThanOrEqual(10, $oauth->count());
        foreach ($oauth as $key => $p) {
            $this->assertCount($key === 'ebay' ? 3 : 2, $p['auto_connect']['env'], $key); // client id and client secret (eBay also needs the RuName of its callback)
            foreach ($p['auto_connect']['env'] as $var) {
                $this->assertStringContainsString("env('{$var['name']}')", $config, "$key: {$var['name']} is not read by config/services.php");
                $this->assertNotSame('', $var['example']);
            }
        }
        $this->assertSame('STRIPE_CONNECT_SECRET_KEY', $list['stripe']['auto_connect']['env'][1]['name']);
        $this->assertSame([], $list['woocommerce']['auto_connect']['env']); // nothing to configure for the others
    }
    public function test_the_catalogue_says_what_each_permission_is_for(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key');
        $notes = $list['klaviyo']['auto_connect']['scope_notes'];

        $this->assertEqualsCanonicalizing($list['klaviyo']['auto_connect']['scopes'], array_keys($notes)); // every permission the app asks for is explained
        $this->assertSame(['send'], $notes['profiles:write']['for']); // and says when it is needed
        $this->assertSame(['import', 'send'], $notes['lists:read']['for']);
        $this->assertSame(['read_write'], array_keys($list['stripe']['auto_connect']['scope_notes']));
    }

    public function test_stripe_asks_for_the_scope_it_accepts_without_any_setting(): void
    {
        config(['services.integrations.stripe' => ['client_id' => 'ca_test', 'client_secret' => 'sk_test']]);

        $this->assertSame('read_write', $this->start(['provider' => 'stripe'])[1]['scope']); // read_only is refused by Stripe unless it approved the platform
    }

    public function test_one_click_is_refused_when_the_platform_has_no_oauth_app_and_for_key_only_platforms(): void
    {
        $this->start(['provider' => 'stripe'], 422);
        $this->start(['provider' => 'brevo'], 422);
        $this->start(['provider' => 'nope'], 422);
    }

    public function test_mailchimp_connects_without_pasting_a_key_and_keeps_what_was_chosen(): void
    {
        $list = $this->postJson('/api/lists', ['name' => 'Mailchimp'], $this->h)->json('id');
        [$url, $q] = $this->start(['provider' => 'mailchimp', 'name' => 'Main audience', 'list_id' => $list, 'tags' => ['MC']]);

        $this->assertStringStartsWith('https://login.mailchimp.com/oauth2/authorize?', $url);
        $this->assertSame(['mc-id', 'http://app.test/api/oauth/integrations/callback', 'code'], [$q['client_id'], $q['redirect_uri'], $q['response_type']]);
        $this->assertSame(48, strlen($q['state']));

        $tokenCalls = [];
        $this->fakeMailchimp($tokenCalls);
        $this->comeBack(['code' => 'abc', 'state' => $q['state']])->assertRedirect('http://front.test/integrations?connected=Main+audience');

        $this->assertSame(['authorization_code', 'abc', 'mc-secret'], [$tokenCalls[0]['grant_type'], $tokenCalls[0]['code'], $tokenCalls[0]['client_secret']]);
        $i = Integration::withoutGlobalScope('company')->sole();
        $this->assertSame(['tok123-us21', true], [$i->secrets['api_key'], $i->secrets['oauth']]);
        $this->assertSame('https://us21.api.mailchimp.com', $i->store_url);
        $this->assertSame([$list], array_map('intval', [$i->settings['list_id']]));
        $person = Person::withoutGlobalScopes()->where('email', 'sub@example.com')->sole(); // the import ran
        $this->assertTrue((bool) $person->is_subscribed);
        $this->assertSame(['MC', 'Mailchimp'], $person->tags()->pluck('name')->sort()->values()->all());

        // the approval can only be used once
        $this->comeBack(['code' => 'abc', 'state' => $q['state']])->assertRedirect()->assertRedirectContains('oauth_error=');
        $this->assertSame(1, Integration::withoutGlobalScope('company')->count());
    }

    public function test_connecting_the_same_account_again_renews_the_connection_instead_of_adding_a_copy(): void
    {
        $this->fakeMailchimp();
        [, $q] = $this->start(['provider' => 'mailchimp']);
        $this->comeBack(['code' => 'a', 'state' => $q['state']])->assertRedirectContains('connected=');

        Http::swap(new Factory);
        $this->fakeMailchimp();
        Http::fake(['login.mailchimp.com/oauth2/token' => Http::response(['access_token' => 'tok999'])] + []);
        $this->app['auth']->forgetGuards();
        [, $q2] = $this->start(['provider' => 'mailchimp']);
        $this->comeBack(['code' => 'b', 'state' => $q2['state']])->assertRedirectContains('reconnected=');

        $this->assertSame(1, Integration::withoutGlobalScope('company')->count());
    }

    public function test_a_denied_approval_returns_with_a_message_and_saves_nothing(): void
    {
        [, $q] = $this->start(['provider' => 'mailchimp']);
        $this->comeBack(['error' => 'access_denied', 'state' => $q['state']])->assertRedirectContains('oauth_error=Access+was+not+approved');
        $this->assertSame(0, Integration::withoutGlobalScope('company')->count());
    }

    public function test_a_platform_that_rejects_the_code_is_explained_and_saves_nothing(): void
    {
        [, $q] = $this->start(['provider' => 'mailchimp']);
        Http::fake(['login.mailchimp.com/*' => Http::response(['error' => 'invalid_grant', 'error_description' => 'code expired'], 400)]);
        $this->comeBack(['code' => 'old', 'state' => $q['state']])->assertRedirectContains(urlencode('did not accept the sign-in'));
        $this->assertSame(0, Integration::withoutGlobalScope('company')->count());
    }

    public function test_shopify_asks_for_the_store_signs_the_callback_and_rejects_a_forged_one(): void
    {
        $this->start(['provider' => 'shopify'], 422); // which store?
        [$url, $q] = $this->start(['provider' => 'shopify', 'url' => 'my-shop']);
        $this->assertStringStartsWith('https://my-shop.myshopify.com/admin/oauth/authorize?', $url);
        $this->assertSame('read_customers', $q['scope']);

        $signed = function (array $query) {
            ksort($query);
            $pairs = [];
            foreach ($query as $k => $v) {
                $pairs[] = "$k=$v";
            }

            return $query + ['hmac' => hash_hmac('sha256', implode('&', $pairs), 'sh-secret')];
        };

        Http::fake(function (Request $r) {
            if (str_ends_with($r->url(), '/admin/oauth/access_token')) {
                return Http::response(['access_token' => 'shpat_new', 'scope' => 'read_customers']);
            }

            return str_contains($r->data()['query'] ?? '', 'shop {')
                ? Http::response(['data' => ['shop' => ['name' => 'My Shop']]])
                : Http::response(['data' => ['customers' => ['edges' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null]]]]);
        });

        $forged = $signed(['code' => 'c', 'shop' => 'my-shop.myshopify.com', 'state' => $q['state'], 'timestamp' => '1']);
        $forged['hmac'] = str_repeat('0', 64);
        $this->comeBack($forged)->assertRedirectContains('oauth_error=');
        $this->assertSame(0, Integration::withoutGlobalScope('company')->count());

        [, $q] = $this->start(['provider' => 'shopify', 'url' => 'my-shop']); // the failed attempt used its state up
        $this->comeBack($signed(['code' => 'c', 'shop' => 'my-shop.myshopify.com', 'state' => $q['state'], 'timestamp' => '1']))->assertRedirectContains('connected=My+Shop');
        $this->assertSame(['https://my-shop.myshopify.com', 'shpat_new'], [Integration::withoutGlobalScope('company')->sole()->store_url, Integration::withoutGlobalScope('company')->sole()->secrets['access_token']]);

        [, $q] = $this->start(['provider' => 'shopify', 'url' => 'other-shop']);
        $this->comeBack($signed(['code' => 'c', 'shop' => 'my-shop.myshopify.com', 'state' => $q['state'], 'timestamp' => '1']))->assertRedirectContains('different+Shopify+store');
    }

    public function test_klaviyo_uses_pkce_and_basic_client_authentication(): void
    {
        [$url, $q] = $this->start(['provider' => 'klaviyo']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame('accounts:read lists:read lists:write profiles:read profiles:write subscriptions:read subscriptions:write', $q['scope']); // read to import, write to send

        $sent = [];
        Http::fake(function (Request $r) use (&$sent) {
            $path = parse_url($r->url(), PHP_URL_PATH);
            if ($path === '/oauth/token') {
                $sent = ['auth' => $r->header('Authorization')[0] ?? '', 'data' => $r->data()];

                return Http::response(['access_token' => 'kl_at', 'refresh_token' => 'kl_rt', 'expires_in' => 3600, 'scope' => 'profiles:read accounts:read lists:read']);
            }

            return $path === '/api/accounts/'
                ? Http::response(['data' => [['attributes' => ['contact_information' => ['organization_name' => 'Acme Klaviyo']]]]])
                : Http::response(['data' => [], 'links' => ['next' => null]]);
        });
        $this->comeBack(['code' => 'kc', 'state' => $q['state']])->assertRedirectContains('connected=Acme+Klaviyo');

        $this->assertSame('Basic '.base64_encode('kl-id:kl-secret'), $sent['auth']);
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', $sent['data']['code_verifier'], true)), '+/', '-_'), '='), $q['code_challenge']); // the challenge matches the verifier
        $this->assertSame(['accounts:read', 'lists:read', 'profiles:read'], Integration::withoutGlobalScope('company')->sole()->settings['granted_scopes']); // what Klaviyo said it granted is kept, to show in Edit Configuration
        $this->assertSame(['kl_at', 'kl_rt', true], [Integration::withoutGlobalScope('company')->sole()->secrets['private_key'], Integration::withoutGlobalScope('company')->sole()->secrets['refresh_token'], Integration::withoutGlobalScope('company')->sole()->secrets['oauth']]);
    }

    public function test_an_expired_token_is_renewed_before_the_next_request(): void
    {
        $contactsAuth = [];
        $refreshed = 0;
        Http::fake(function (Request $r) use (&$contactsAuth, &$refreshed) {
            if (str_contains($r->url(), '/oauth/v1/token')) {
                $refreshed++;

                return Http::response(['access_token' => $r['grant_type'] === 'refresh_token' ? 'hs_new' : 'hs_old', 'refresh_token' => 'hs_rt', 'expires_in' => 1800]);
            }
            $contactsAuth[] = $r->header('Authorization')[0] ?? null;

            return Http::response(['results' => []]);
        });
        [, $q] = $this->start(['provider' => 'hubspot']);
        $this->comeBack(['code' => 'c', 'state' => $q['state']])->assertRedirectContains('connected=');
        $i = Integration::withoutGlobalScope('company')->sole();
        $this->assertSame('hs_old', $i->secrets['access_token']);

        $i->forceFill(['secrets' => ['expires_at' => time() - 10] + $i->secrets])->save(); // it has expired
        $contactsAuth = [];
        $this->postJson("/api/integrations/{$i->id}/check", [], $this->h)->assertOk();

        $this->assertSame(2, $refreshed); // once to connect, once to renew
        $this->assertSame(['Bearer hs_new'], array_unique($contactsAuth));
        $this->assertSame('hs_new', $i->fresh()->secrets['access_token']);
    }

    public function test_woocommerce_posts_its_keys_to_us_and_an_unknown_approval_is_refused(): void
    {
        [$url, $q] = $this->start(['provider' => 'woocommerce', 'url' => 'https://shop.test', 'name' => 'My shop']);
        $this->assertStringStartsWith('https://shop.test/wc-auth/v1/authorize?', $url);
        $this->assertSame('read', $q['scope']);
        $this->assertSame('http://app.test/api/oauth/integrations/woocommerce', $q['callback_url']);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/oauth/integrations/woocommerce', ['user_id' => 'wrong', 'consumer_key' => 'ck', 'consumer_secret' => 'cs'])->assertStatus(400);

        Http::fake([
            'shop.test/wp-json/wc/v3/customers*' => Http::response([['id' => 1, 'email' => 'w@example.com', 'first_name' => 'Wo', 'last_name' => 'O', 'billing' => ['phone' => '']]], 200, ['X-WP-TotalPages' => 1]),
            'shop.test/wp-json/' => Http::response(['name' => 'Woo Shop', 'namespaces' => ['wc/v3']]),
        ]);
        $this->postJson('/api/oauth/integrations/woocommerce', ['user_id' => $q['user_id'], 'consumer_key' => 'ck_1', 'consumer_secret' => 'cs_1'])->assertOk();

        $i = Integration::withoutGlobalScope('company')->sole();
        $this->assertSame(['My shop', 'ck_1', 'cs_1'], [$i->name, $i->secrets['consumer_key'], $i->secrets['consumer_secret']]);
        $this->assertSame(1, Person::withoutGlobalScopes()->where('email', 'w@example.com')->count());
        $this->postJson('/api/oauth/integrations/woocommerce', ['user_id' => $q['user_id'], 'consumer_key' => 'ck_1', 'consumer_secret' => 'cs_1'])->assertStatus(400); // used once
    }

    public function test_woocommerce_one_click_needs_this_app_to_be_reachable_over_https(): void
    {
        config(['services.outbound.allow_private' => false, 'app.url' => 'http://localhost:8000']);
        $this->start(['provider' => 'woocommerce', 'url' => 'https://shop.test'], 422);
    }

    public function test_wordpress_returns_a_new_application_password(): void
    {
        [$url, $q] = $this->start(['provider' => 'wordpress', 'url' => 'https://blog.test']);
        $this->assertStringStartsWith('https://blog.test/wp-admin/authorize-application.php?', $url);
        $this->assertSame('http://front.test/integrations?oauth_error=Access+was+not+approved.', $q['reject_url']);
        parse_str((string) parse_url($q['success_url'], PHP_URL_QUERY), $s);

        Http::fake([
            'blog.test/wp-json/wp/v2/users*' => Http::response([['id' => 1, 'email' => 'u@example.com', 'name' => 'User One', 'roles' => ['subscriber']]], 200, ['X-WP-TotalPages' => 1]),
            'blog.test/wp-json/' => Http::response(['name' => 'The Blog']),
        ]);
        $this->comeBack(['state' => $s['state'], 'site_url' => 'https://blog.test', 'user_login' => 'admin', 'password' => 'abcd efgh'])->assertRedirectContains('connected=The+Blog');

        $i = Integration::withoutGlobalScope('company')->sole();
        $this->assertSame(['admin', 'abcd efgh', 'https://blog.test'], [$i->secrets['username'], $i->secrets['app_password'], $i->store_url]);
    }

    public function test_the_approval_state_belongs_to_the_company_that_started_it(): void
    {
        $this->fakeMailchimp();
        [, $q] = $this->start(['provider' => 'mailchimp']);
        $company = Cache::get('integration_oauth:'.$q['state'])['company'];
        $this->comeBack(['code' => 'abc', 'state' => $q['state']]);

        $this->assertSame($company, Integration::withoutGlobalScope('company')->sole()->company_id);
    }
}
