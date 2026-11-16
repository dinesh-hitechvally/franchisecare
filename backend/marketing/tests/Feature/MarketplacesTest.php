<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\IntegrationSync;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The marketplaces: Etsy, eBay, Amazon, Walmart, Mercado Libre and Daraz. Each answer is faked in the platform's documented shape. */
class MarketplacesTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    /** @var array<int, Request> every request made to a marketplace, in order */
    protected array $calls = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.outbound.allow_private' => true, 'app.url' => 'http://app.test', 'services.mail_oauth.frontend_url' => 'http://front.test']);
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
        Http::swap(new Factory);
    }

    /** @param  callable(Request, string, array): mixed  $answer  the reply for a request: (request, path, query) */
    protected function fake(callable $answer): void
    {
        $this->calls = [];
        Http::fake(function (Request $r) use ($answer) {
            $this->calls[] = $r;
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);

            return $answer($r, (string) parse_url($r->url(), PHP_URL_PATH), $q) ?? Http::response([], 404);
        });
    }

    protected function connect(array $body, int $status = 201)
    {
        return $this->postJson('/api/integrations', $body, $this->h)->assertStatus($status);
    }

    protected function person(string $by, string $value): Person
    {
        return Person::withoutGlobalScopes()->where($by, $value)->sole();
    }

    /** The first request whose path ends with $suffix. */
    protected function requestTo(string $suffix, ?string $method = null): Request
    {
        foreach ($this->calls as $r) {
            if (str_ends_with((string) parse_url($r->url(), PHP_URL_PATH), $suffix) && (! $method || $r->method() === $method)) {
                return $r;
            }
        }
        $this->fail("No request to $suffix was made.");
    }

    public function test_the_marketplaces_are_their_own_group_in_the_catalogue(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key');

        foreach (['etsy', 'ebay', 'amazon', 'walmart', 'mercadolibre', 'daraz'] as $key) {
            $this->assertSame('marketplace', $list[$key]['category'], $key);
            $this->assertStringContainsString('Nobody is subscribed', str_replace('nobody is subscribed', 'Nobody is subscribed', $list[$key]['consent_note']), $key); // marketplace buyers never join a list by themselves
        }
        $this->assertSame('oauth', $list['etsy']['auto_connect']['mode']);
        $this->assertSame('oauth', $list['ebay']['auto_connect']['mode']);
        $this->assertFalse($list['ebay']['auto_connect']['available']); // no client id, secret and RuName on this server yet
        $this->assertNull($list['amazon']['auto_connect']); // the others are connected with the credentials the person pastes
        $this->assertSame(['EBAY_CLIENT_ID', 'EBAY_CLIENT_SECRET', 'EBAY_RUNAME'], array_column($list['ebay']['auto_connect']['env'], 'name'));
    }

    public function test_etsy_makes_a_token_from_the_refresh_token_and_reads_the_receipts(): void
    {
        $this->fake(fn ($r, $path, $q) => match (true) {
            $path === '/v3/public/oauth/token' => Http::response(['access_token' => 'etsy-at', 'refresh_token' => 'rt2', 'expires_in' => 3600]),
            $path === '/v3/application/users/me' => Http::response(['user_id' => 1, 'shop_id' => 77]),
            $path === '/v3/application/shops/77' => Http::response(['shop_name' => 'Pretty Things']),
            $path === '/v3/application/shops/77/receipts' => Http::response(['count' => 1, 'results' => [['receipt_id' => 9, 'buyer_user_id' => 55, 'buyer_email' => 'Bea@Example.com', 'name' => 'Bea Buyer']]]),
            default => null,
        });
        $this->connect(['provider' => 'etsy', 'api_key' => 'keystr:shared', 'refresh_token' => 'rt1']);

        $bea = $this->person('email', 'bea@example.com');
        $this->assertSame(['Bea', 'Buyer', 'etsy'], [$bea->first_name, $bea->last_name, $bea->source]);
        $this->assertFalse($bea->is_subscribed); // a marketplace buyer has not agreed to marketing
        parse_str($this->requestTo('/oauth/token', 'POST')->body(), $form);
        $this->assertSame(['refresh_token', 'keystr', 'rt1'], [$form['grant_type'], $form['client_id'], $form['refresh_token']]);
        $receipts = $this->requestTo('/receipts');
        $this->assertSame(['keystr:shared', 'Bearer etsy-at'], [$receipts->header('x-api-key')[0], $receipts->header('Authorization')[0]]);
        $this->assertSame('rt2', Integration::withoutGlobalScopes()->sole()->secrets['refresh_token']); // Etsy hands out a new one each time: the new one is kept
    }

    public function test_ebay_signs_the_token_request_and_reads_the_buyers_of_the_orders(): void
    {
        $this->fake(fn ($r, $path, $q) => match (true) {
            $path === '/identity/v1/oauth2/token' => Http::response(['access_token' => 'ebay-at', 'expires_in' => 7200]),
            $path === '/sell/fulfillment/v1/order' => Http::response(['total' => 2, 'orders' => [
                ['orderId' => '1-1', 'buyer' => ['username' => 'bidder1', 'buyerRegistrationAddress' => ['fullName' => 'Cy Bidder', 'email' => 'cy@example.com', 'primaryPhone' => ['phoneNumber' => '+61400111222']]]],
                ['orderId' => '2-2', 'buyer' => ['username' => 'bidder2'], 'fulfillmentStartInstructions' => [['shippingStep' => ['shipTo' => ['fullName' => 'Di Shipto', 'email' => 'di@example.com']]]]],
            ]]),
            default => null,
        });
        $this->connect(['provider' => 'ebay', 'client_id' => 'app-id', 'client_secret' => 'cert-id', 'refresh_token' => 'v^1.1#abc']);

        $this->assertSame('Basic '.base64_encode('app-id:cert-id'), $this->requestTo('/oauth2/token', 'POST')->header('Authorization')[0]);
        $orders = collect($this->calls)->last(fn ($r) => str_ends_with((string) parse_url($r->url(), PHP_URL_PATH), '/fulfillment/v1/order')); // the import (the first request is only the connection test)
        $this->assertSame('Bearer ebay-at', $orders->header('Authorization')[0]);
        $this->assertStringStartsWith('creationdate:[', $this->q($orders)['filter']);
        $this->assertSame(['Cy', 'Bidder', '+61400111222'], [$this->person('email', 'cy@example.com')->first_name, $this->person('email', 'cy@example.com')->last_name, $this->person('email', 'cy@example.com')->phone]);
        $this->assertSame('Di', $this->person('email', 'di@example.com')->first_name); // the shipping contact is used when the buyer has no registration address
    }

    public function test_ebay_one_click_sends_the_runame_where_other_platforms_send_the_callback(): void
    {
        config(['services.integrations.ebay' => ['client_id' => 'eb-id', 'client_secret' => 'eb-secret', 'ru_name' => 'My_Company-App-PRD-123']]);
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key');
        $this->assertTrue($list['ebay']['auto_connect']['available']);

        $url = $this->postJson('/api/integrations/oauth/start', ['provider' => 'ebay'], $this->h)->assertOk()->json('url');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('auth.ebay.com', parse_url($url, PHP_URL_HOST));
        $this->assertSame(['eb-id', 'My_Company-App-PRD-123'], [$q['client_id'], $q['redirect_uri']]);
        $this->assertStringContainsString('sell.fulfillment.readonly', $q['scope']);

        config(['services.integrations.ebay.ru_name' => null]);
        $this->assertFalse(collect($this->getJson('/api/integrations/providers', $this->h)->json())->keyBy('key')['ebay']['auto_connect']['available']); // not ready without a RuName
    }

    public function test_amazon_reads_orders_with_a_restricted_token_and_follows_the_next_token(): void
    {
        $this->fake(fn ($r, $path, $q) => match (true) {
            $path === '/auth/o2/token' => Http::response(['access_token' => 'lwa-at', 'expires_in' => 3600]),
            $path === '/sellers/v1/marketplaceParticipations' => Http::response(['payload' => []]),
            $path === '/tokens/2021-03-01/restrictedDataToken' => Http::response(['restrictedDataToken' => 'rdt-1']),
            $path === '/orders/v0/orders' && isset($q['NextToken']) => Http::response(['payload' => ['Orders' => [
                ['AmazonOrderId' => 'A-2', 'ShippingAddress' => ['Name' => 'Eve Phone', 'Phone' => '+61400999888']],
            ]]]),
            $path === '/orders/v0/orders' => Http::response(['payload' => ['NextToken' => 'page2', 'Orders' => [
                ['AmazonOrderId' => 'A-1', 'BuyerInfo' => ['BuyerEmail' => 'abc123@marketplace.amazon.com', 'BuyerName' => 'Flo Buyer']],
                ['AmazonOrderId' => 'A-0'], // no buyer details shared: nobody to import
            ]]]),
            default => null,
        });
        $this->connect(['provider' => 'amazon', 'client_id' => 'amzn1.app', 'client_secret' => 'sec', 'refresh_token' => 'Atzr|1', 'region' => 'na', 'marketplace_ids' => 'ATVPDKIKX0DER']);

        $this->assertSame('Flo', $this->person('email', 'abc123@marketplace.amazon.com')->first_name);
        $this->assertSame('Eve', $this->person('phone', '+61400999888')->first_name); // a buyer with only a phone number is kept
        $orders = collect($this->calls)->filter(fn ($r) => str_ends_with($r->url(), '') && str_contains($r->url(), '/orders/v0/orders'))->values();
        $this->assertCount(2, $orders);
        $this->assertSame('rdt-1', $orders[0]->header('x-amz-access-token')[0]); // the restricted token, not the plain one
        $this->assertSame('sellingpartnerapi-na.amazon.com', parse_url($orders[0]->url(), PHP_URL_HOST));
        $this->assertSame('ATVPDKIKX0DER', $this->q($orders[0])['MarketplaceIds']);
        $this->assertSame(['NextToken' => 'page2'], $this->q($orders[1]));
        $this->assertSame(2, Person::withoutGlobalScopes()->count());
    }

    public function test_amazon_explains_it_when_the_app_is_not_approved_for_buyer_details(): void
    {
        $this->fake(fn ($r, $path, $q) => match (true) {
            $path === '/auth/o2/token' => Http::response(['access_token' => 'lwa-at', 'expires_in' => 3600]),
            $path === '/sellers/v1/marketplaceParticipations' => Http::response(['payload' => []]),
            $path === '/tokens/2021-03-01/restrictedDataToken' => Http::response(['errors' => [['code' => 'Unauthorized']]], 403),
            default => null,
        });
        $this->connect(['provider' => 'amazon', 'client_id' => 'amzn1.app', 'client_secret' => 'sec', 'refresh_token' => 'Atzr|1', 'region' => 'eu', 'marketplace_ids' => 'A1F83G8C2ARO7P']);

        $sync = IntegrationSync::withoutGlobalScope('company')->sole();
        $this->assertSame('failed', $sync->status);
        $this->assertStringContainsString('restricted role', $sync->error);
    }

    public function test_walmart_gets_a_token_with_the_client_credentials_and_follows_the_cursor(): void
    {
        $this->fake(fn ($r, $path, $q) => match (true) {
            $path === '/v3/token' => Http::response(['access_token' => 'wm-at', 'expires_in' => 900]),
            $path === '/v3/orders' && isset($q['soIndex']) => Http::response(['list' => ['meta' => [], 'elements' => ['order' => [
                ['purchaseOrderId' => 'P2', 'customerEmailId' => 'gus@relay.walmart.com', 'shippingInfo' => ['phone' => '5551230000', 'postalAddress' => ['name' => 'Gus Second']]],
            ]]]]),
            $path === '/v3/orders' => Http::response(['list' => ['meta' => ['nextCursor' => '?limit=200&soIndex=5&poIndex=1'], 'elements' => ['order' => [
                ['purchaseOrderId' => 'P1', 'customerEmailId' => 'hal@relay.walmart.com', 'shippingInfo' => ['phone' => '5551110000', 'postalAddress' => ['name' => 'Hal First']]],
            ]]]]),
            default => null,
        });
        $this->connect(['provider' => 'walmart', 'client_id' => 'wm-client', 'client_secret' => 'wm-secret']);

        $token = $this->requestTo('/v3/token', 'POST');
        $this->assertSame('Basic '.base64_encode('wm-client:wm-secret'), $token->header('Authorization')[0]);
        $this->assertNotEmpty($token->header('WM_QOS.CORRELATION_ID'));
        $orders = $this->requestTo('/v3/orders');
        $this->assertSame('wm-at', $orders->header('WM_SEC.ACCESS_TOKEN')[0]);
        $this->assertSame(['Hal', 'First', '5551110000'], [$this->person('email', 'hal@relay.walmart.com')->first_name, $this->person('email', 'hal@relay.walmart.com')->last_name, $this->person('email', 'hal@relay.walmart.com')->phone]);
        $this->assertSame('Gus', $this->person('email', 'gus@relay.walmart.com')->first_name); // the second page, from the cursor
    }

    public function test_mercado_libre_keeps_the_new_refresh_token_it_is_given(): void
    {
        $this->fake(fn ($r, $path, $q) => match (true) {
            $path === '/oauth/token' => Http::response(['access_token' => 'ml-at', 'refresh_token' => 'TG-new', 'expires_in' => 21600]),
            $path === '/users/me' => Http::response(['id' => 321, 'nickname' => 'MITIENDA']),
            $path === '/orders/search' => Http::response(['paging' => ['total' => 1, 'offset' => 0, 'limit' => 50], 'results' => [
                ['id' => 1001, 'buyer' => ['id' => 9, 'first_name' => 'Ivo', 'last_name' => 'Comprador', 'email' => 'ivo@example.com', 'phone' => ['area_code' => '11', 'number' => '5555-1234']]],
            ]]),
            default => null,
        });
        $this->connect(['provider' => 'mercadolibre', 'client_id' => '1234567890', 'client_secret' => 'ml-secret', 'refresh_token' => 'TG-old']);

        $ivo = $this->person('email', 'ivo@example.com');
        $this->assertSame(['Ivo', 'Comprador', '11 5555-1234'], [$ivo->first_name, $ivo->last_name, $ivo->phone]);
        $this->assertSame('321', $this->q($this->requestTo('/orders/search'))['seller']);
        $this->assertSame('Bearer ml-at', $this->requestTo('/orders/search')->header('Authorization')[0]);
        $this->assertSame('TG-new', Integration::withoutGlobalScopes()->sole()->secrets['refresh_token']); // the old one works only once
    }

    public function test_daraz_signs_every_request_and_imports_buyers_by_phone(): void
    {
        $this->fake(fn ($r, $path, $q) => match (true) {
            $path === '/rest/seller/get' => Http::response(['code' => '0', 'data' => ['name' => 'Kathmandu Kiosk']]),
            $path === '/rest/orders/get' => Http::response(['code' => '0', 'data' => ['count' => 1, 'orders' => [
                ['order_id' => 5001, 'customer_first_name' => 'Jiya', 'customer_last_name' => 'Sharma', 'address_billing' => ['phone' => '+9779812345678']],
            ]]]),
            default => null,
        });
        $this->connect(['provider' => 'daraz', 'country' => 'np', 'app_key' => '100200', 'app_secret' => 'secret123', 'access_token' => '50000abc']);

        $jiya = $this->person('phone', '+9779812345678');
        $this->assertSame(['Jiya', 'Sharma', null], [$jiya->first_name, $jiya->last_name, $jiya->email]); // Daraz gives no email
        $orders = $this->requestTo('/orders/get');
        $this->assertSame('api.daraz.com.np', parse_url($orders->url(), PHP_URL_HOST));
        $q = $this->q($orders);
        $sign = $q['sign'];
        unset($q['sign']);
        ksort($q);
        $text = '/orders/get';
        foreach ($q as $k => $v) {
            $text .= $k.$v;
        }
        $this->assertSame(strtoupper(hash_hmac('sha256', $text, 'secret123')), $sign); // the signature Daraz recomputes
        $this->assertSame(['100200', '50000abc', 'sha256'], [$q['app_key'], $q['access_token'], $q['sign_method']]);
    }

    public function test_daraz_refusing_the_token_is_reported_and_nothing_is_saved(): void
    {
        $this->fake(fn ($r, $path, $q) => $path === '/rest/seller/get' ? Http::response(['code' => 'IllegalAccessToken', 'message' => 'The specified access token is invalid or expired']) : null);

        $r = $this->connect(['provider' => 'daraz', 'country' => 'pk', 'app_key' => '1', 'app_secret' => 's', 'access_token' => 'bad'], 422);
        $this->assertStringContainsString('Daraz refused', $r->json('message'));
        $this->assertSame(0, Integration::withoutGlobalScopes()->count());
    }

    protected function q(Request $r): array
    {
        parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);

        return $q;
    }
}
