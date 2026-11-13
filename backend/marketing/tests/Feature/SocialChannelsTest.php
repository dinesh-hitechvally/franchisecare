<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\SocialLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** WhatsApp, Messenger and X as campaign channels: connect an account, pick it in a campaign, and the messages go through it. */
class SocialChannelsTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    /** A fresh fake: a second Http::fake() would only add stubs after the first, which would still win. */
    protected function fake(array $stubs): void
    {
        Http::swap(new Factory);
        Http::fake($stubs);
    }

    protected function whatsapp(): array
    {
        $this->fake(['graph.facebook.com/*' => Http::response(['verified_name' => 'Acme', 'display_phone_number' => '+61 400 000 000'])]);

        return $this->postJson('/api/integrations', ['provider' => 'whatsapp', 'access_token' => 'EAAtoken', 'phone_number_id' => '109876543210987'], $this->h)->assertCreated()->json();
    }

    protected function campaign(array $extra = []): array
    {
        $r = $this->postJson('/api/campaigns', $extra + ['name' => 'Offer', 'type' => 'whatsapp', 'body' => 'Hi {{first_name}}, 20% off today'], $this->h)->assertSuccessful();
        $this->app['auth']->forgetGuards();

        return $r->json();
    }

    public function test_the_three_channels_are_listed_as_messaging_and_connecting_one_imports_nothing(): void
    {
        $list = collect($this->getJson('/api/integrations/providers', $this->h)->assertOk()->json());
        $this->assertSame(['whatsapp', 'messenger', 'x'], $list->where('category', 'messaging')->pluck('key')->values()->all());
        $this->assertSame('whatsapp', $list->firstWhere('key', 'whatsapp')['channel']);

        $i = $this->whatsapp();
        $this->assertSame('whatsapp', $i['channel']);
        $this->assertSame('Acme +61 400 000 000', $i['name']);
        $this->assertSame(0, \App\Models\IntegrationSync::withoutGlobalScopes()->count()); // nothing to import
    }

    public function test_a_whatsapp_campaign_needs_a_connected_account_and_sends_the_text_through_the_template(): void
    {
        $this->postJson('/api/campaigns', ['name' => 'Offer', 'type' => 'whatsapp', 'body' => 'Hi'], $this->h)->assertStatus(422); // nothing connected yet
        $this->app['auth']->forgetGuards();

        $account = $this->whatsapp();
        $this->app['auth']->forgetGuards();
        $c = $this->campaign(['social_template' => 'promo_one']); // the only connected account is used
        $this->assertSame($account['id'], $c['social_account_id']);

        Person::create(['first_name' => 'Ann', 'phone' => '+61 400 111 222']);
        Person::create(['first_name' => 'Bob', 'phone' => null, 'email' => 'bob@x.com']);   // no phone: left out
        Person::create(['first_name' => 'Cy', 'phone' => '+61400333444', 'is_subscribed' => false]); // unsubscribed: left out

        $this->fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        $this->postJson('/api/campaigns/'.$c['id'].'/send', [], $this->h)->assertSuccessful();

        $log = SocialLog::withoutGlobalScopes()->sole();
        $this->assertSame(['whatsapp', 'SENT', '61400111222', 'wamid.1'], [$log->platform, $log->status, $log->recipient, $log->provider_id]);
        $this->assertSame('Hi Ann, 20% off today', $log->body);
        Http::assertSent(function (Request $r) {
            return str_ends_with($r->url(), '/109876543210987/messages') && $r->hasHeader('Authorization', 'Bearer EAAtoken')
                && $r['to'] === '61400111222' && $r['type'] === 'template' && $r['template']['name'] === 'promo_one' && $r['template']['components'][0]['parameters'][0]['text'] === 'Hi Ann, 20% off today';
        });

        $show = $this->getJson('/api/campaigns/'.$c['id'], $this->h)->assertOk()->json();
        $this->assertSame([1, 1], [$show['sent_count'], $show['logs_count']]);
        $this->assertSame(1, $show['skipped_count']); // Bob has no phone; Cy unsubscribed is not counted as a message
    }

    public function test_messenger_goes_to_the_id_stored_on_the_person_and_a_refusal_is_a_failed_message(): void
    {
        $this->fake(['graph.facebook.com/*' => Http::response(['name' => 'Acme Page', 'id' => '1'])]);
        $account = $this->postJson('/api/integrations', ['provider' => 'messenger', 'access_token' => 'EAApage'], $this->h)->assertCreated()->json();
        $this->app['auth']->forgetGuards();

        $ann = $this->postJson('/api/people', ['first_name' => 'Ann', 'email' => 'ann@x.com', 'handles' => ['messenger' => ' 5551 ', 'x' => '']], $this->h)->assertSuccessful()->json();
        $this->assertSame(['messenger' => '5551'], Person::withoutGlobalScopes()->find($ann['id'])->handles);
        $this->app['auth']->forgetGuards();
        Person::create(['first_name' => 'Bob', 'email' => 'bob@x.com']); // no Messenger id

        $c = $this->campaign(['type' => 'messenger', 'social_account_id' => $account['id']]);
        $check = $this->getJson('/api/campaigns/'.$c['id'].'/check', $this->h)->assertOk()->json();
        $this->assertSame([2, 1], [$check['audience'], $check['reach']]);
        $this->app['auth']->forgetGuards();

        $this->fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'This message is sent outside of allowed window.']], 400)]);
        $this->postJson('/api/campaigns/'.$c['id'].'/send', [], $this->h)->assertSuccessful();

        $log = SocialLog::withoutGlobalScopes()->sole();
        $this->assertSame(['messenger', 'FAILED', '5551'], [$log->platform, $log->status, $log->recipient]);
        $this->assertStringContainsString('outside of allowed window', $log->error);
        Http::assertSent(fn (Request $r) => $r['recipient']['id'] === '5551' && $r['messaging_type'] === 'UPDATE');
    }

    public function test_a_disconnected_account_fails_the_message_clearly_and_the_history_filters_by_channel(): void
    {
        $account = $this->whatsapp();
        $this->app['auth']->forgetGuards();
        $c = $this->campaign();
        Person::create(['first_name' => 'Ann', 'phone' => '+61400111222']);
        $this->deleteJson('/api/integrations/'.$account['id'], [], $this->h)->assertNoContent();
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/campaigns/'.$c['id'].'/send', [], $this->h)->assertSuccessful();
        $this->assertStringContainsString('was disconnected', SocialLog::withoutGlobalScopes()->sole()->error);

        $this->app['auth']->forgetGuards();
        $rows = $this->getJson('/api/logs?channel=whatsapp', $this->h)->assertOk()->json('data');
        $this->assertSame(['whatsapp', 'FAILED'], [$rows[0]['channel'], $rows[0]['status']]);
        $this->assertCount(0, $this->getJson('/api/logs?channel=x', $this->h)->json('data'));
        $this->assertSame('whatsapp', $this->getJson('/api/logs/whatsapp/'.$rows[0]['id'], $this->h)->assertOk()->json('channel'));
    }

    public function test_a_test_message_goes_to_the_typed_number_and_is_kept_out_of_the_statistics(): void
    {
        $this->whatsapp();
        $this->app['auth']->forgetGuards();
        $c = $this->campaign();

        $this->fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.t']]])]);
        $this->postJson('/api/campaigns/'.$c['id'].'/test', ['to' => '+61 400 999 888'], $this->h)->assertOk();

        $log = SocialLog::withoutGlobalScopes()->sole();
        $this->assertSame(['test', 'SENT'], [$log->source, $log->status]);
        $this->assertStringStartsWith('[TEST]', $log->body);
        $this->assertSame(0, SocialLog::count()); // test sends are outside the statistics scope
        Http::assertSent(fn (Request $r) => $r['to'] === '61400999888' && $r['type'] === 'text');
    }
}
