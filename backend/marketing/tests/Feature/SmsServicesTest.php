<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\Sms\LogDriver;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsProviders;
use App\Services\SmsSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_every_service_can_be_connected_but_only_one_is_in_use(): void
    {
        $h = $this->owner();
        $this->getJson('/api/config/sms', $h)->assertOk()->assertJsonPath('active', 'log')->assertJsonPath('connections.twilio.connected', false)->assertJsonPath('providers.vonage.name', 'Vonage');

        $twilio = ['account_sid' => 'AC1', 'auth_token' => 'tok', 'from' => '+15550001111'];
        $this->putJson('/api/config/sms/connections/twilio', $twilio, $h)->assertOk()->assertJsonPath('connections.twilio.connected', true)->assertJsonPath('active', 'log'); // saving does not switch
        $r = $this->putJson('/api/config/sms/connections/vonage', ['api_key' => 'k', 'api_secret' => 's', 'from' => 'Acme', 'activate' => true], $h)->assertOk()->assertJsonPath('active', 'vonage');
        $r->assertJsonPath('connections.twilio.account_sid', 'AC1')->assertJsonPath('connections.twilio.auth_token_set', true);
        $this->assertStringNotContainsString('"tok"', $r->getContent());
        $this->assertStringNotContainsString('"s"', str_replace('"s":', '', $r->getContent())); // the vonage secret is not echoed back

        $this->putJson('/api/config/sms', ['active' => 'twilio'], $h)->assertOk()->assertJsonPath('active', 'twilio')->assertJsonPath('connections.vonage.connected', true);
        $this->putJson('/api/config/sms', ['active' => 'telnyx'], $h)->assertStatus(422); // not connected
        $this->putJson('/api/config/sms', ['active' => 'bogus'], $h)->assertStatus(422)->assertJsonValidationErrors('active');

        // a blank secret keeps the stored one; a missing required field is refused
        $this->putJson('/api/config/sms/connections/twilio', ['account_sid' => 'AC2', 'auth_token' => '', 'from' => '+15550001111'], $h)->assertOk()->assertJsonPath('connections.twilio.account_sid', 'AC2')->assertJsonPath('connections.twilio.auth_token_set', true);
        $this->assertSame('tok', SmsSettings::connections(Company::first())['twilio']['auth_token']);
        $this->putJson('/api/config/sms/connections/vonage', ['api_key' => 'k'], $h)->assertStatus(422);
        $this->putJson('/api/config/sms/connections/nope', [], $h)->assertNotFound();

        // disconnecting the one in use falls back to test mode
        $this->deleteJson('/api/config/sms/connections/twilio', [], $h)->assertOk()->assertJsonPath('active', 'log')->assertJsonPath('connections.vonage.connected', true);
    }

    public function test_the_service_in_use_is_the_one_that_sends(): void
    {
        $h = $this->owner();
        $company = Company::first();
        $this->assertInstanceOf(LogDriver::class, app(SmsManager::class)->forCompany($company)); // test mode by default

        Http::fake(['rest.nexmo.com/*' => Http::response(['messages' => [['status' => '0', 'message-id' => 'abc']]])]);
        $this->putJson('/api/config/sms/connections/vonage', ['api_key' => 'k', 'api_secret' => 's', 'from' => 'Acme', 'activate' => true], $h)->assertOk();
        $this->postJson('/api/config/sms/test', ['to' => '+61400000000'], $h)->assertOk();
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'rest.nexmo.com') && $r['to'] === '61400000000' && $r['from'] === 'Acme');
    }

    public function test_each_driver_sends_the_request_its_provider_expects(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM1']),
            'rest.nexmo.com/*' => Http::response(['messages' => [['status' => '0', 'message-id' => 'V1']]]),
            'api.plivo.com/*' => Http::response(['message_uuid' => ['P1']]),
            'api.telnyx.com/*' => Http::response(['data' => ['id' => 'T1']]),
            'rest.clicksend.com/*' => Http::response(['data' => ['messages' => [['status' => 'SUCCESS', 'message_id' => 'C1']]]]),
            'rest.messagebird.com/*' => Http::response(['id' => 'M1']),
            'eu.sms.api.sinch.com/*' => Http::response(['id' => 'S1']),
        ]);
        $conns = [
            'twilio' => ['account_sid' => 'AC1', 'auth_token' => 't', 'from' => '+1555'],
            'vonage' => ['api_key' => 'k', 'api_secret' => 's', 'from' => 'Acme'],
            'plivo' => ['auth_id' => 'PID', 'auth_token' => 't', 'from' => '+1555'],
            'telnyx' => ['api_key' => 'k', 'from' => '+1555'],
            'clicksend' => ['username' => 'u', 'api_key' => 'k', 'from' => 'Acme'],
            'messagebird' => ['access_key' => 'ak', 'from' => 'Acme'],
            'sinch' => ['service_plan_id' => 'plan1', 'api_token' => 'tok', 'region' => 'eu', 'from' => '+1555'],
        ];
        $ids = [];
        foreach ($conns as $key => $c) {
            $ids[$key] = SmsProviders::driver($key, $c)->send('+61400000000', 'Hello');
        }
        $this->assertSame(['twilio' => 'SM1', 'vonage' => 'V1', 'plivo' => 'P1', 'telnyx' => 'T1', 'clicksend' => 'C1', 'messagebird' => 'M1', 'sinch' => 'S1'], $ids);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'api.plivo.com/v1/Account/PID/Message/') && $r['dst'] === '61400000000' && $r['src'] === '+1555' && $r['text'] === 'Hello');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'api.telnyx.com/v2/messages') && $r->hasHeader('Authorization', 'Bearer k') && $r['to'] === '+61400000000');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'rest.clicksend.com/v3/sms/send') && $r['messages'][0]['body'] === 'Hello');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'rest.messagebird.com/messages') && $r->hasHeader('Authorization', 'AccessKey ak') && $r['originator'] === 'Acme');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'eu.sms.api.sinch.com/xms/v1/plan1/batches') && $r['to'] === ['+61400000000']);
    }

    public function test_a_provider_error_is_reported_with_its_own_message(): void
    {
        Http::fake(['rest.nexmo.com/*' => Http::response(['messages' => [['status' => '2', 'error-text' => 'Missing api_key']]])]);
        $this->expectExceptionMessage('Missing api_key');
        SmsProviders::driver('vonage', ['api_key' => '', 'api_secret' => 's', 'from' => 'A'])->send('+1', 'x');
    }

    public function test_a_company_saved_with_the_old_twilio_settings_still_works(): void
    {
        $h = $this->owner();
        Company::first()->update(['settings' => ['sms' => ['provider' => 'twilio', 'twilio_sid' => 'AC9', 'twilio_token' => 'tk', 'from' => '+1555']]]);

        $this->getJson('/api/config/sms', $h)->assertJsonPath('active', 'twilio')->assertJsonPath('connections.twilio.account_sid', 'AC9')->assertJsonPath('connections.twilio.auth_token_set', true);
        $this->putJson('/api/config/sms/connections/telnyx', ['api_key' => 'k', 'from' => '+1555'], $h)->assertOk(); // first change converts it
        $this->assertSame('tk', SmsSettings::connections(Company::first())['twilio']['auth_token']);
        $this->assertSame('twilio', SmsSettings::activeKey(Company::first()));
    }
}
