<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\PeopleList;
use App\Models\Person;
use App\Models\PushDevice;
use App\Models\PushLog;
use App\Services\Push\WebPush;
use App\Services\PushSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Push notifications as the third channel: browsers (Web Push) and Android / iOS apps (Firebase). */
class PushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.outbound.allow_private' => true]); // no DNS lookups in tests
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
        $this->company = Company::first();
        Http::swap(new Factory);
        Cache::flush();
    }

    protected function live(): void
    {
        PushSettings::setMode($this->company->fresh(), 'live');
    }

    protected function person(string $email, array $more = []): int
    {
        return $this->postJson('/api/people', ['email' => $email, 'first_name' => 'Pat'] + $more, $this->h)->assertSuccessful()->json('id');
    }

    /** A browser subscription like the one a real browser produces: an endpoint and a P-256 public key and auth secret. */
    protected function browser(string $endpoint = 'https://push.test/send/abc'): array
    {
        return ['endpoint' => $endpoint, 'keys' => ['p256dh' => WebPush::generateKeys()[0], 'auth' => WebPush::b64(random_bytes(16))]];
    }

    protected function subscribe(array $sub, ?string $email = null)
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/push/subscribe', ['key' => PushSettings::siteKey($this->company->fresh()), 'subscription' => $sub, 'email' => $email]);
    }

    protected function campaign(array $extra = []): int
    {
        return $this->postJson('/api/campaigns', array_merge(['name' => 'Flash sale', 'type' => 'push', 'subject' => 'Hi {{first_name}}', 'body' => 'Sale ends tonight', 'push_url' => 'https://shop.test/sale'], $extra), $this->h)->assertSuccessful()->json('id');
    }

    protected function send(int $id)
    {
        return $this->postJson("/api/campaigns/$id/send", [], $this->h)->assertSuccessful();
    }

    // ----------------------------------------------------------------- the encryption (checked against the standard's own example)

    public function test_the_encryption_reproduces_the_published_example_of_the_web_push_standard(): void
    {
        // RFC 8291 section 5: fixed keys and salt, so the result must be byte for byte the document's
        $pem = WebPush::privatePem(WebPush::unb64('yfWPiYE-n46HLnH0KqZOF1fJJU3MYrct3AELtAQ-oRw'), WebPush::unb64('BP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A8'));
        $body = WebPush::encrypt('When I grow up, I want to be a watermelon', 'BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcxaOzi6-AYWXvTBHm4bjyPjs7Vd8pZGH6SRpkNtoIAiw4', 'BTBZMqHH6r4Tts7J_aSIgg', $pem, WebPush::unb64('DGv6ra1nlYgDCS1FRnbzlw'));

        $this->assertSame('DGv6ra1nlYgDCS1FRnbzlwAAEABBBP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A_yl95bQpu6cVPTpK4Mqgkf1CXztLVBSt2Ks3oZwbuwXPXLWyouBWLVWGNWQexSgSxsj_Qulcy4a-fN', WebPush::b64($body));
    }

    public function test_the_signed_header_is_a_valid_es256_token_for_the_push_service(): void
    {
        [$public, $private] = WebPush::generateKeys();
        $header = WebPush::vapidHeader('https://fcm.googleapis.com/fcm/send/abc', $public, $private, 'mailto:a@b.co');
        $this->assertMatchesRegularExpression('~^vapid t=([\w-]+)\.([\w-]+)\.([\w-]+), k='.preg_quote($public, '~').'$~', $header);

        preg_match('~t=([\w-]+)\.([\w-]+)\.([\w-]+),~', $header, $m);
        $this->assertSame('https://fcm.googleapis.com', json_decode(WebPush::unb64($m[2]), true)['aud']);
        $raw = WebPush::unb64($m[3]);
        $this->assertSame(64, strlen($raw)); // r and s, 32 bytes each

        // the signature verifies with the public key (re-wrapped into the DER form openssl checks)
        $int = fn (string $b) => "\x02".chr(strlen($b = ltrim($b, "\0")) + (ord($b[0]) > 127 ? 1 : 0)).(ord($b[0]) > 127 ? "\0" : '').$b;
        $body = $int(substr($raw, 0, 32)).$int(substr($raw, 32));
        $der = "\x30".chr(strlen($body)).$body;
        $pub = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode(hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').WebPush::unb64($public)), 64, "\n")."-----END PUBLIC KEY-----\n");
        $this->assertSame(1, openssl_verify("{$m[1]}.{$m[2]}", $der, $pub, OPENSSL_ALGO_SHA256));
    }

    // ----------------------------------------------------------------- website side

    public function test_the_website_script_service_worker_and_public_key_are_served(): void
    {
        $key = PushSettings::siteKey($this->company);
        $this->get('/api/push/sdk.js')->assertOk()->assertHeader('Content-Type', 'application/javascript; charset=utf-8')->assertSee('MatePush', false);
        $this->get('/api/push/sw.js')->assertOk()->assertSee("addEventListener('push'", false);
        $this->getJson('/api/push/config?key='.$key)->assertOk()->assertJsonPath('public_key', PushSettings::webKeys($this->company->fresh())[0]);
        $this->getJson('/api/push/config?key='.str_repeat('x', 40))->assertNotFound();
    }

    public function test_a_browser_registers_once_and_is_linked_to_the_person_with_that_email(): void
    {
        $person = $this->person('ann@example.com');
        $sub = $this->browser();

        $this->subscribe($sub)->assertOk()->assertJsonPath('linked', false); // nobody yet
        $this->subscribe($sub, 'ANN@example.com')->assertOk()->assertJsonPath('linked', true);
        $this->subscribe($sub)->assertOk()->assertJsonPath('linked', true); // a later visit does not unlink it

        $d = PushDevice::withoutGlobalScopes()->sole();
        $this->assertSame([$person, 'web', 'active'], [$d->person_id, $d->platform, $d->status]);
        $this->assertSame($sub['keys']['auth'], $d->keys['auth']);
    }

    public function test_only_real_browser_push_services_and_a_valid_key_are_accepted(): void
    {
        config(['services.outbound.allow_private' => false]);
        $this->subscribe($this->browser('https://169.254.169.254/latest/meta-data'))->assertStatus(422); // an internal address
        $this->subscribe($this->browser('http://fcm.googleapis.com/fcm/send/abc'))->assertStatus(422);   // not https
        $this->subscribe(['endpoint' => 'https://fcm.googleapis.com/x', 'keys' => ['p256dh' => 'a', 'auth' => 'b']])->assertStatus(422); // keys that are not a real browser key
        $this->assertSame(0, PushDevice::withoutGlobalScopes()->count());

        $this->postJson('/api/push/subscribe', ['key' => str_repeat('x', 40), 'subscription' => $this->browser()])->assertNotFound();
    }

    public function test_a_browser_can_unsubscribe(): void
    {
        $sub = $this->browser();
        $this->subscribe($sub);
        $this->postJson('/api/push/unsubscribe', ['key' => PushSettings::siteKey($this->company->fresh()), 'endpoint' => $sub['endpoint']])->assertOk();
        $this->assertSame('revoked', PushDevice::withoutGlobalScopes()->sole()->status);
    }

    // ----------------------------------------------------------------- sending to browsers

    public function test_a_push_campaign_sends_to_every_device_of_subscribed_people_and_people_without_a_device_are_not_in_its_audience(): void
    {
        $this->live();
        $a = $this->person('ann@example.com');
        $this->person('nodevice@example.com');
        $this->person('out@example.com', ['is_subscribed' => false]);
        $this->subscribe($this->browser('https://push.test/ann-laptop'), 'ann@example.com');
        $this->subscribe($this->browser('https://push.test/ann-phone'), 'ann@example.com'); // a second device of the same person
        $this->subscribe($this->browser('https://push.test/out'), 'out@example.com');

        $requests = [];
        Http::fake(function (Request $r) use (&$requests) {
            $requests[] = $r;

            return Http::response('', 201);
        });
        $id = $this->campaign();
        $this->send($id);

        $this->assertCount(2, $requests); // two devices of Ann; the unsubscribed person gets nothing
        $r = $requests[0];
        $this->assertSame(['aes128gcm', 'application/octet-stream'], [$r->header('Content-Encoding')[0], $r->header('Content-Type')[0]]);
        $this->assertStringStartsWith('vapid t=', $r->header('Authorization')[0]);
        $this->assertGreaterThan(100, strlen($r->body())); // encrypted: salt, record size, key, ciphertext

        $logs = PushLog::withoutGlobalScopes()->where('campaign_id', $id)->get();
        $this->assertSame(['SENT', 'SENT'], $logs->pluck('status')->all());
        $this->assertSame(['Hi Pat'], $logs->pluck('subject')->unique()->values()->all()); // {{first_name}} filled in
        $this->assertSame(['webpush'], $logs->pluck('service')->unique()->values()->all());
        $this->assertSame([$a], $logs->pluck('person_id')->unique()->values()->all());

        $c = Campaign::first()->fresh();
        $this->assertSame('SENT', $c->status);
        $this->assertSame([2, 0], [(int) $c->audience_total, (int) $c->audience_skipped]); // Ann and the unsubscribed person; the person with no device is not part of "people with a device"
        $row = $this->getJson("/api/campaigns/$id", $this->h)->json();
        $this->assertSame([2, 2, 0], [$row['logs_count'], $row['sent_count'], $row['failed_count']]);
        $this->assertSame(0, $row['skipped_count']);
    }

    public function test_in_test_mode_nothing_is_sent_and_the_messages_are_logged_only(): void
    {
        $this->person('ann@example.com');
        $this->subscribe($this->browser(), 'ann@example.com');
        Http::fake();
        $id = $this->campaign();
        $this->send($id);

        Http::assertNothingSent();
        $this->assertSame(['LOGGED', 'log'], [PushLog::withoutGlobalScopes()->sole()->status, PushLog::withoutGlobalScopes()->sole()->service]);
    }

    public function test_a_device_the_service_says_is_gone_fails_with_a_reason_and_is_not_used_again(): void
    {
        $this->live();
        $this->person('ann@example.com');
        $this->subscribe($this->browser(), 'ann@example.com');
        Http::fake(['*' => Http::response('', 410)]);
        $id = $this->campaign();
        $this->send($id);

        $log = PushLog::withoutGlobalScopes()->sole();
        $this->assertSame('FAILED', $log->status);
        $this->assertStringContainsString('turned notifications off', $log->error);
        $this->assertSame('revoked', PushDevice::withoutGlobalScopes()->sole()->status);
        $this->assertSame('FAILED', Campaign::first()->status); // everything failed

        $this->postJson('/api/campaigns', [], $this->h); // a second campaign has nobody to reach
        $second = $this->campaign(['name' => 'Again']);
        $this->send($second);
        $this->assertSame(0, PushLog::withoutGlobalScopes()->where('campaign_id', $second)->count());
    }

    public function test_a_tap_is_counted_and_goes_on_to_the_real_address(): void
    {
        $this->live();
        $this->person('ann@example.com');
        $this->subscribe($this->browser(), 'ann@example.com');
        Http::fake(['*' => Http::response('', 201)]);
        $id = $this->campaign();
        $this->send($id);
        $log = PushLog::withoutGlobalScopes()->sole();

        $this->app['auth']->forgetGuards();
        $this->get('/api/push/c/'.$log->token)->assertRedirect('https://shop.test/sale');
        $this->get('/api/push/c/'.$log->token)->assertRedirect('https://shop.test/sale');
        $this->get('/api/push/c/'.str_repeat('z', 40))->assertNotFound();
        $this->assertSame(2, $log->fresh()->clicks);

        $stats = $this->getJson("/api/reports/campaigns/$id", $this->h)->assertOk()->json('stats');
        $this->assertSame([1, 1, 2], [$stats['sent'], $stats['clickers'], $stats['clicks']]);
    }

    // ----------------------------------------------------------------- Android / iOS through Firebase

    protected function serviceAccount(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA] + array_intersect_key(WebPush::keyOptions(), ['config' => 1]));
        openssl_pkey_export($key, $pem, null, array_intersect_key(WebPush::keyOptions(), ['config' => 1]));

        return json_encode(['type' => 'service_account', 'project_id' => 'demo-proj', 'client_email' => 'push@demo-proj.iam.gserviceaccount.com', 'private_key' => $pem, 'token_uri' => 'https://evil.test/token']);
    }

    public function test_an_app_registers_its_token_and_receives_the_campaign_through_firebase(): void
    {
        $this->live();
        $this->person('ann@example.com');
        $calls = [];
        Http::fake(function (Request $r) use (&$calls) {
            $calls[] = [parse_url($r->url(), PHP_URL_HOST).parse_url($r->url(), PHP_URL_PATH), $r->header('Authorization')[0] ?? null, $r->data()];

            return str_contains($r->url(), 'oauth2.googleapis.com')
                ? Http::response(['access_token' => 'ya29.token', 'expires_in' => 3600])
                : Http::response(['name' => 'projects/demo-proj/messages/0:123']);
        });

        $this->putJson('/api/push/fcm', ['service_account' => $this->serviceAccount()], $this->h)->assertOk()->assertJsonPath('fcm.project_id', 'demo-proj');
        $this->assertSame('oauth2.googleapis.com/token', $calls[0][0]); // always Google's address, never the file's token_uri

        $this->postJson('/api/v1/push/devices', ['token' => 'fcm-token-1', 'platform' => 'android', 'email' => 'ann@example.com', 'label' => 'Pixel 8'], $this->h)->assertCreated()->assertJsonPath('linked', true);
        $this->postJson('/api/v1/push/devices', ['token' => 'fcm-token-1', 'platform' => 'android', 'email' => 'ann@example.com'], $this->h)->assertCreated(); // same device: no copy
        $this->assertSame(1, PushDevice::withoutGlobalScopes()->count());

        $calls = [];
        $id = $this->campaign();
        $this->send($id);

        $send = collect($calls)->first(fn ($c) => str_contains($c[0], 'messages:send'));
        $this->assertSame(['fcm.googleapis.com/v1/projects/demo-proj/messages:send', 'Bearer ya29.token'], [$send[0], $send[1]]);
        $this->assertSame(['fcm-token-1', 'Hi Pat', 'Sale ends tonight'], [$send[2]['message']['token'], $send[2]['message']['notification']['title'], $send[2]['message']['notification']['body']]);
        $log = PushLog::withoutGlobalScopes()->sole();
        $this->assertSame(['SENT', 'fcm', 'Pixel 8'], [$log->status, $log->service, $log->recipient]);
    }

    public function test_an_unregistered_app_token_is_revoked_and_firebase_must_be_connected_to_send(): void
    {
        $this->live();
        $this->person('ann@example.com');
        $this->postJson('/api/v1/push/devices', ['token' => 'fcm-token-1', 'platform' => 'ios', 'email' => 'ann@example.com'], $this->h)->assertCreated();

        $id = $this->campaign();
        $this->send($id); // Firebase is not connected yet
        $this->assertStringContainsString('Connect Firebase', PushLog::withoutGlobalScopes()->sole()->error);

        Http::fake(function (Request $r) {
            return str_contains($r->url(), 'oauth2.googleapis.com') ? Http::response(['access_token' => 't']) : Http::response(['error' => ['status' => 'NOT_FOUND', 'message' => 'Requested entity was not found.', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404);
        });
        $this->putJson('/api/push/fcm', ['service_account' => $this->serviceAccount()], $this->h)->assertOk();
        $this->send($this->campaign(['name' => 'Second']));
        $this->assertSame('revoked', PushDevice::withoutGlobalScopes()->sole()->status);
    }

    public function test_a_wrong_firebase_file_is_refused_and_a_removed_token_stops_receiving(): void
    {
        $this->putJson('/api/push/fcm', ['service_account' => '{"hello":1}'], $this->h)->assertStatus(422);
        $this->putJson('/api/push/fcm', ['service_account' => 'not json'], $this->h)->assertStatus(422);

        $this->postJson('/api/v1/push/devices', ['token' => 'tok', 'platform' => 'android'], $this->h)->assertCreated()->assertJsonPath('linked', false);
        $this->deleteJson('/api/v1/push/devices', ['token' => 'tok'], $this->h)->assertNoContent();
        $this->assertSame('revoked', PushDevice::withoutGlobalScopes()->sole()->status);
    }

    // ----------------------------------------------------------------- the campaign form

    public function test_a_push_campaign_needs_a_title_and_a_real_link_and_a_short_message(): void
    {
        $base = ['name' => 'X', 'type' => 'push', 'body' => 'Hello'];
        $this->postJson('/api/campaigns', $base, $this->h)->assertStatus(422)->assertJsonValidationErrors('subject');
        $this->postJson('/api/campaigns', $base + ['subject' => 'T', 'push_url' => 'javascript:alert(1)'], $this->h)->assertStatus(422)->assertJsonValidationErrors('push_url');
        $this->postJson('/api/campaigns', ['body' => str_repeat('a', 501)] + $base + ['subject' => 'T'], $this->h)->assertStatus(422);
        $ok = $this->postJson('/api/campaigns', $base + ['subject' => 'T', 'push_url' => 'https://a.test/x', 'push_image' => 'https://a.test/i.png'], $this->h)->assertCreated();
        $this->assertSame('https://a.test/x', $ok->json('push_url'));

        // the link and picture belong to push only
        $sms = $this->postJson('/api/campaigns', ['name' => 'S', 'type' => 'sms', 'body' => 'Hi', 'push_url' => 'https://a.test/x'], $this->h)->assertCreated();
        $this->assertNull($sms->json('push_url'));
    }

    public function test_the_check_counts_people_with_a_device_and_warns_about_what_is_missing(): void
    {
        $this->person('ann@example.com');
        $this->person('nodevice@example.com');
        $this->subscribe($this->browser(), 'ann@example.com');
        $id = $this->campaign(['push_url' => null, 'subject' => str_repeat('T', 60)]);

        $check = $this->getJson("/api/campaigns/$id/check", $this->h)->assertOk()->json();
        $this->assertSame([1, 1, 0], [$check['audience'], $check['reach'], $check['skipped']]); // the audience is the people with a device: only Ann
        $warnings = implode(' | ', $check['warnings']);
        $this->assertStringContainsString('title is long', $warnings);
        $this->assertStringContainsString('No link is set', $warnings);
        $this->assertStringContainsString('test mode', $warnings);
    }

    public function test_the_preview_and_a_test_notification_work(): void
    {
        $this->postJson('/api/campaigns/preview', ['type' => 'push', 'subject' => 'Hello {{first_name}}', 'body' => 'Hi {{company_name}}'], $this->h)->assertOk()->assertJsonPath('title', 'Hello Jane')->assertJsonPath('text', 'Hi Acme');

        $this->person('ann@example.com');
        $id = $this->campaign();
        $this->postJson("/api/campaigns/$id/test", ['to' => 'ann@example.com'], $this->h)->assertStatus(422); // no device yet

        $this->subscribe($this->browser(), 'ann@example.com');
        $this->live();
        Http::fake(['*' => Http::response('', 201)]);
        $this->postJson("/api/campaigns/$id/test", ['to' => 'ann@example.com'], $this->h)->assertOk();
        $log = PushLog::withoutGlobalScopes()->withoutGlobalScope('real')->sole();
        $this->assertSame(['test', 'SENT'], [$log->source, $log->status]);
        $this->assertStringStartsWith('[TEST]', $log->subject);
        $this->assertSame(0, PushLog::count()); // test sends never count in statistics
    }

    // ----------------------------------------------------------------- reports, logs and settings

    public function test_the_report_and_the_message_log_include_push(): void
    {
        $this->live();
        $this->person('ann@example.com');
        $this->person('nodevice@example.com');
        $this->subscribe($this->browser(), 'ann@example.com');
        Http::fake(['*' => Http::response('', 201)]);
        $id = $this->campaign();
        $this->send($id);

        $deliveries = $this->getJson("/api/reports/campaigns/$id/deliveries", $this->h)->assertOk()->json('data');
        $this->assertSame(['SENT'], array_column($deliveries, 'status')); // only the person with a device matches "all people with a device"

        $logs = $this->getJson('/api/logs?channel=push', $this->h)->assertOk()->json('data');
        $this->assertSame([['push', 'SENT', 'Hi Pat']], array_map(fn ($r) => [$r['channel'], $r['status'], $r['subject']], $logs));
        $this->getJson('/api/logs/push/'.$logs[0]['id'], $this->h)->assertOk()->assertJsonPath('channel', 'push');
        $this->assertCount(1, $this->getJson('/api/logs', $this->h)->json('data')); // all channels together
    }

    public function test_the_settings_show_the_keys_never_a_secret_and_can_switch_mode_and_rotate_keys(): void
    {
        $s = $this->getJson('/api/push/settings', $this->h)->assertOk()->json();
        $this->assertSame('log', $s['mode']);
        $this->assertSame(40, strlen($s['site_key']));
        $this->assertSame(65, strlen(WebPush::unb64($s['public_key'])));
        $this->assertStringNotContainsString('PRIVATE KEY', json_encode($s));
        $this->assertFalse($s['fcm']['connected']);

        $this->putJson('/api/push/settings', ['mode' => 'live'], $this->h)->assertOk()->assertJsonPath('mode', 'live');
        $this->putJson('/api/push/settings', ['mode' => 'bogus'], $this->h)->assertStatus(422);

        $this->subscribe($this->browser(), null);
        $this->postJson('/api/push/web-keys/rotate', [], $this->h)->assertOk();
        $new = $this->getJson('/api/push/settings', $this->h)->json();
        $this->assertNotSame($s['public_key'], $new['public_key']);
        $this->assertSame(0, $new['devices']['total']); // browsers using the old key must subscribe again
    }

    public function test_the_device_list_hides_tokens_and_can_be_filtered_and_cleaned_up(): void
    {
        $this->person('ann@example.com');
        $this->subscribe($this->browser('https://push.test/one'), 'ann@example.com');
        $this->subscribe($this->browser('https://push.test/two'));
        $this->postJson('/api/v1/push/devices', ['token' => 'secret-token', 'platform' => 'android'], $this->h)->assertCreated();

        $all = $this->getJson('/api/push/devices', $this->h)->assertOk()->json('data');
        $this->assertCount(3, $all);
        $this->assertStringNotContainsString('secret-token', json_encode($all));
        $this->assertStringNotContainsString('push.test', json_encode($all));
        $this->assertCount(1, $this->getJson('/api/push/devices?linked=yes', $this->h)->json('data'));
        $this->assertCount(1, $this->getJson('/api/push/devices?platform=android', $this->h)->json('data'));
        $this->assertCount(1, $this->getJson('/api/push/devices?search=ann%40example', $this->h)->json('data'));

        $this->deleteJson('/api/push/devices/'.$all[0]['id'], [], $this->h)->assertNoContent();
        $this->assertCount(2, $this->getJson('/api/push/devices', $this->h)->json('data'));
    }

    public function test_the_service_worker_is_served_inline_for_pages_and_forms_and_there_is_no_hosted_sign_up_page(): void
    {
        $key = PushSettings::siteKey($this->company);
        $worker = $this->get('/api/push/worker.js')->assertOk();
        $worker->assertSee("addEventListener('push'", false);
        $this->assertNull($worker->headers->get('Content-Disposition')); // shown to the browser, not downloaded

        $this->get("/api/push/join/$key")->assertNotFound(); // the sign-up link was removed: forms and page blocks do this now
        $this->assertArrayNotHasKey('join_url', $this->getJson('/api/push/settings', $this->h)->json());
    }

    public function test_the_scripts_read_the_response_envelope_the_server_really_sends(): void
    {
        // successful JSON is wrapped as { success, data }: the page and the website script must unwrap it, or no browser can ever register
        $key = PushSettings::siteKey($this->company);
        $raw = json_decode($this->get('/api/push/config?key='.$key)->getContent(), true);
        $this->assertTrue($raw['success']);
        $this->assertArrayHasKey('public_key', $raw['data']);

        $this->get('/api/push/sdk.js')->assertSee('function unwrap(j)', false)->assertSee('.then(unwrap)', false);
    }

    public function test_a_test_notification_can_be_sent_to_one_registered_device(): void
    {
        $this->person('ann@example.com');
        $this->subscribe($this->browser('https://push.test/one'), 'ann@example.com');
        $this->subscribe($this->browser('https://push.test/two'), 'ann@example.com');
        $first = PushDevice::withoutGlobalScopes()->orderBy('id')->first();

        // test mode: written to the log, nothing sent
        Http::fake();
        $this->postJson("/api/push/devices/{$first->id}/test", [], $this->h)->assertOk();
        Http::assertNothingSent();
        $this->assertSame('LOGGED', PushLog::withoutGlobalScopes()->withoutGlobalScope('real')->sole()->status);

        // live: only that device gets it
        $this->live();
        $this->app['auth']->forgetGuards(); // the signed-in user is read again, with the new mode
        $requests = [];
        Http::fake(function (Request $r) use (&$requests) {
            $requests[] = $r->url();

            return Http::response('', 201);
        });
        $this->postJson("/api/push/devices/{$first->id}/test", [], $this->h)->assertOk()->assertJsonPath('status', 'SENT');
        $this->assertSame(['https://push.test/one'], $requests);
        $log = PushLog::withoutGlobalScopes()->withoutGlobalScope('real')->latest('id')->first();
        $this->assertSame(['test', 'SENT', 'Test notification', $first->id], [$log->source, $log->status, $log->subject, $log->push_device_id]);
        $this->assertSame(0, PushLog::count()); // a test never counts in statistics
    }

    public function test_a_test_to_a_gone_or_turned_off_device_explains_why(): void
    {
        $this->live();
        $this->subscribe($this->browser('https://push.test/one'));
        $device = PushDevice::withoutGlobalScopes()->sole();

        Http::fake(['*' => Http::response('', 410)]);
        $this->postJson("/api/push/devices/{$device->id}/test", [], $this->h)->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'switched off'));
        $this->assertSame('revoked', $device->fresh()->status);
        $this->assertSame('FAILED', PushLog::withoutGlobalScopes()->withoutGlobalScope('real')->sole()->status);

        $this->postJson("/api/push/devices/{$device->id}/test", [], $this->h)->assertStatus(422); // already off: nothing is sent
    }

    // ----------------------------------------------------------------- device history

    protected function historyOf(int $deviceId): array
    {
        return DB::table('push_devices_history')->where('id', $deviceId)->orderBy('history_id')->get()
            ->map(fn ($r) => [$r->change_action, json_decode((string) $r->changed_fields, true) ?: [], $r->change_source])->all();
    }

    public function test_every_change_to_a_device_is_written_to_its_history(): void
    {
        $this->person('ann@example.com');
        $sub = $this->browser('https://push.test/one');

        $this->subscribe($sub);                          // registers, nobody yet
        $id = PushDevice::withoutGlobalScopes()->sole()->id;
        $this->subscribe($sub);                          // a later visit only moves "last seen": not a change worth recording
        $this->subscribe($sub, 'ann@example.com');       // now linked to Ann
        $this->postJson('/api/push/unsubscribe', ['key' => PushSettings::siteKey($this->company->fresh()), 'endpoint' => $sub['endpoint']])->assertOk(); // turned off
        $this->subscribe($sub, 'ann@example.com');       // turned on again by registering again

        $this->assertSame([
            ['CREATED', [], 'PUBLIC'],
            ['UPDATED', ['person_id'], 'PUBLIC'],
            ['UPDATED', ['status'], 'PUBLIC'],
            ['UPDATED', ['status'], 'PUBLIC'],
        ], $this->historyOf($id));

        $this->app['auth']->forgetGuards();
        $this->deleteJson("/api/push/devices/$id", [], $this->h)->assertNoContent();
        $this->assertSame(['DELETED', [], 'DASHBOARD'], array_slice($this->historyOf($id)[4], 0, 3));
    }

    public function test_the_history_names_the_device_and_the_person_and_never_stores_the_token_or_keys(): void
    {
        $this->person('ann@example.com');
        $this->subscribe($this->browser('https://push.test/secret-endpoint'), 'ann@example.com');
        $id = PushDevice::withoutGlobalScopes()->sole()->id;

        $row = DB::table('push_devices_history')->where('id', $id)->first();
        $this->assertSame('Browser · ann@example.com', $row->record_label); // no browser name in the test request, so the platform's own
        $this->assertSame('••••••', $row->token);
        $this->assertSame('••••••', $row->keys);
        $this->assertStringNotContainsString('secret-endpoint', json_encode($row));
    }

    public function test_the_device_history_is_readable_for_one_device_and_for_all(): void
    {
        $this->person('ann@example.com');
        $this->subscribe($this->browser('https://push.test/one'), 'ann@example.com');
        $this->subscribe($this->browser('https://push.test/two'));
        $first = PushDevice::withoutGlobalScopes()->orderBy('id')->first();
        $this->deleteJson('/api/push/devices/'.PushDevice::withoutGlobalScopes()->orderBy('id')->skip(1)->first()->id, [], $this->h)->assertNoContent();

        $one = $this->getJson("/api/history/push_device/{$first->id}", $this->h)->assertOk()->json();
        $this->assertSame(['created'], array_column($one, 'change_action'));

        $all = $this->getJson('/api/history?type=push_device&all=1', $this->h)->assertOk()->json('data');
        $this->assertSame(['deleted', 'created', 'created'], array_column($all, 'change_action'));
        $this->assertContains('push_device', $this->getJson('/api/history/types', $this->h)->json());
    }

    public function test_turning_notifications_off_by_the_service_and_rotating_keys_are_recorded_for_every_device(): void
    {
        $this->live();
        $this->subscribe($this->browser('https://push.test/one'));
        $this->subscribe($this->browser('https://push.test/two'));
        $this->person('ann@example.com');
        $this->subscribe($this->browser('https://push.test/three'), 'ann@example.com');

        Http::fake(['*' => Http::response('', 410)]); // the service says the third one is gone
        $three = PushDevice::withoutGlobalScopes()->orderBy('id')->get()->last();
        $this->postJson("/api/push/devices/{$three->id}/test", [], $this->h)->assertStatus(422);
        $this->assertSame(['UPDATED', ['status'], 'DASHBOARD'], $this->historyOf($three->id)[1]);

        $this->postJson('/api/push/web-keys/rotate', [], $this->h)->assertOk();
        foreach (PushDevice::withoutGlobalScopes()->orderBy('id')->get()->take(2) as $d) {
            $this->assertSame(['UPDATED', ['status'], 'DASHBOARD'], $this->historyOf($d->id)[1], 'device '.$d->id); // each one, not just a bulk change
        }
    }

    // ----------------------------------------------------------------- audience: people with a device, or all devices

    public function test_the_people_list_can_show_only_people_who_have_a_device(): void
    {
        $this->person('ann@example.com');
        $this->person('nodevice@example.com');
        $this->subscribe($this->browser('https://push.test/one'), 'ann@example.com');
        $this->subscribe($this->browser('https://push.test/two'), 'ann@example.com');
        $this->subscribe($this->browser('https://push.test/three')); // not linked to anybody

        $all = $this->getJson('/api/people', $this->h)->json('data');
        $this->assertGreaterThanOrEqual(2, count($all));

        $with = $this->getJson('/api/people?has_device=1', $this->h)->assertOk()->json('data');
        $this->assertSame(['ann@example.com'], array_column($with, 'email'));
        $this->assertSame(2, $with[0]['devices_count']);
        $this->assertSame(1, $this->getJson('/api/people?has_device=1&status=subscribed', $this->h)->json('total'));
    }

    public function test_the_audience_counts_for_push_are_people_with_a_device_and_all_devices(): void
    {
        $list = $this->postJson('/api/lists', ['name' => 'VIPs'], $this->h)->json('id');
        $ann = $this->person('ann@example.com', ['list_ids' => [$list]]);
        $this->person('nodevice@example.com', ['list_ids' => [$list]]);
        $this->subscribe($this->browser('https://push.test/one'), 'ann@example.com');
        $this->subscribe($this->browser('https://push.test/two'));

        $a = $this->getJson('/api/push/audience', $this->h)->assertOk()->json();
        $this->assertSame([2, 1], [$a['devices'], $a['people']]);   // two devices; one person has any
        $this->assertSame(1, $a['lists'][$list]);                    // the list has two people, one with a device
    }

    public function test_a_campaign_to_all_devices_reaches_linked_and_unlinked_devices_but_not_unsubscribed_people(): void
    {
        $this->live();
        $this->person('ann@example.com');
        $this->person('out@example.com', ['is_subscribed' => false]);
        $this->subscribe($this->browser('https://push.test/ann'), 'ann@example.com');
        $this->subscribe($this->browser('https://push.test/anon'));                  // a visitor nobody knows
        $this->subscribe($this->browser('https://push.test/out'), 'out@example.com'); // opted out of marketing

        $requests = [];
        Http::fake(function (Request $r) use (&$requests) {
            $requests[] = $r->url();

            return Http::response('', 201);
        });
        $id = $this->campaign(['push_all_devices' => true]);

        $check = $this->getJson("/api/campaigns/$id/check", $this->h)->assertOk()->json();
        $this->assertSame([3, 2, 1, 0, true], [$check['audience'], $check['reach'], $check['unsubscribed'], $check['skipped'], $check['devices']]);

        $this->send($id);
        sort($requests);
        $this->assertSame(['https://push.test/ann', 'https://push.test/anon'], $requests);
        $this->assertSame([null], PushLog::withoutGlobalScopes()->whereNull('person_id')->pluck('person_id')->unique()->values()->all()); // the unlinked device has no person
        $this->assertSame(['SENT', 'SENT'], PushLog::withoutGlobalScopes()->pluck('status')->all());

        $c = Campaign::first()->fresh();
        $this->assertSame([3, 0], [(int) $c->audience_total, (int) $c->audience_skipped]);
        $deliveries = $this->getJson("/api/reports/campaigns/$id/deliveries", $this->h)->assertOk()->json('data');
        $this->assertSame(['SENT', 'SENT'], array_column($deliveries, 'status')); // nobody is "skipped" for lacking a device
    }

    public function test_choosing_all_devices_drops_any_audience_and_only_push_can_use_it(): void
    {
        $list = $this->postJson('/api/lists', ['name' => 'VIPs'], $this->h)->json('id');
        $id = $this->campaign(['people_list_id' => $list, 'push_all_devices' => true]);
        $row = $this->getJson("/api/campaigns/$id", $this->h)->json();
        $this->assertSame([true, null], [$row['push_all_devices'], $row['people_list_id']]);

        $sms = $this->postJson('/api/campaigns', ['name' => 'S', 'type' => 'sms', 'body' => 'Hi', 'push_all_devices' => true], $this->h)->assertCreated();
        $this->assertFalse($sms->json('push_all_devices'));

        // switching it off again goes back to an audience of people
        $this->putJson("/api/campaigns/$id", ['name' => 'Flash sale', 'type' => 'push', 'subject' => 'T', 'body' => 'B', 'push_all_devices' => false, 'people_list_id' => $list], $this->h)->assertOk();
        $this->assertSame([false, $list], [Campaign::first()->push_all_devices, Campaign::first()->people_list_id]);
    }

    // ----------------------------------------------------------------- the page where notifications were turned on

    public function test_the_page_a_browser_registered_from_is_kept_shown_and_searchable(): void
    {
        $key = PushSettings::siteKey($this->company->fresh());
        $reg = fn (string $endpoint, ?string $page) => $this->postJson('/api/push/subscribe', ['key' => $key, 'subscription' => $this->browser($endpoint), 'page_url' => $page])->assertOk();

        $reg('https://push.test/one', 'https://shop.test/blog/post-1?utm=x#comments');
        $reg('https://push.test/two', 'javascript:alert(1)'); // not an address: ignored
        $reg('https://push.test/three', null);

        $d = PushDevice::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertSame(['https://shop.test/blog/post-1?utm=x', null, null], $d->pluck('page_url')->all()); // no #fragment, nothing but http(s)

        $list = $this->getJson('/api/push/devices', $this->h)->assertOk()->json('data');
        $this->assertSame('https://shop.test/blog/post-1?utm=x', collect($list)->firstWhere('id', $d[0]->id)['page_url']);
        $this->assertCount(1, $this->getJson('/api/push/devices?search=blog%2Fpost-1', $this->h)->json('data'));
    }

    public function test_a_later_visit_does_not_replace_the_page_where_it_was_first_turned_on(): void
    {
        $key = PushSettings::siteKey($this->company->fresh());
        $sub = $this->browser('https://push.test/one');
        $this->postJson('/api/push/subscribe', ['key' => $key, 'subscription' => $sub, 'page_url' => 'https://shop.test/first'])->assertOk();
        $this->postJson('/api/push/subscribe', ['key' => $key, 'subscription' => $sub, 'page_url' => 'https://shop.test/second'])->assertOk();
        $this->assertSame('https://shop.test/first', PushDevice::withoutGlobalScopes()->sole()->page_url);

        // a device that was registered before pages were recorded learns it the next time
        PushDevice::withoutGlobalScopes()->update(['page_url' => null]);
        $this->postJson('/api/push/subscribe', ['key' => $key, 'subscription' => $sub, 'page_url' => 'https://shop.test/second'])->assertOk();
        $this->assertSame('https://shop.test/second', PushDevice::withoutGlobalScopes()->sole()->page_url);
    }

    public function test_the_scripts_send_the_page_address(): void
    {
        $this->assertStringContainsString("page_url: location.href.split('#')[0]", $this->get('/api/push/sdk.js')->getContent());
        $slug = $this->postJson('/api/forms', ['name' => 'F', 'type' => 'other', 'save_person' => false, 'fields' => [['type' => 'push', 'label' => 'Notify me']]], $this->h)->json('slug');
        $this->app['auth']->forgetGuards();
        $this->get("/f/$slug")->assertSee("page_url:location.href.split('#')[0]", false);
    }

    // ----------------------------------------------------------------- one device row, however often notifications are turned off and on

    protected function register(array $sub, ?string $uid, ?string $page = 'https://shop.test/p')
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/push/subscribe', ['key' => PushSettings::siteKey($this->company->fresh()), 'subscription' => $sub, 'page_url' => $page, 'device_uid' => $uid])->assertOk();
    }

    protected function turnOff(array $sub, ?string $uid)
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/push/unsubscribe', ['key' => PushSettings::siteKey($this->company->fresh()), 'endpoint' => $sub['endpoint'], 'device_uid' => $uid])->assertOk();
    }

    public function test_turning_notifications_on_and_off_again_keeps_one_device_row_with_the_whole_story_in_its_history(): void
    {
        $this->person('ann@example.com');
        $uid = 'b7e1c0de-1111-4222-8333-444455556666';

        // a browser gets a NEW push address each time it subscribes again, but it is the same browser (same id)
        $first = $this->browser('https://push.test/address-1');
        $second = $this->browser('https://push.test/address-2');
        $third = $this->browser('https://push.test/address-3');

        $this->register($first, $uid);                  // on
        $this->turnOff($first, $uid);                   // off
        $this->register($second, $uid);                 // on again (new address)
        $this->turnOff($second, $uid);                  // off again
        $this->register($third, $uid);                  // on a third time

        $this->assertSame(1, PushDevice::withoutGlobalScopes()->count()); // ONE row, not one per subscription
        $d = PushDevice::withoutGlobalScopes()->sole();
        $this->assertSame(['active', $uid, 'https://shop.test/p'], [$d->status, $d->device_uid, $d->page_url]);
        $this->assertSame('https://push.test/address-3', $d->token); // it holds the address that works now

        $history = $this->getJson("/api/history/push_device/{$d->id}", $this->h)->assertOk()->json();
        $actions = array_map(fn ($h) => $h['change_action'].(isset($h['changes']['status']) ? ':'.$h['changes']['status']['new'] : ''), array_reverse($history));
        $this->assertSame(['created:active', 'updated:revoked', 'updated:active', 'updated:revoked', 'updated:active'], $actions); // every turn off and on, in order
    }

    public function test_the_list_shows_one_row_for_the_browser_and_it_is_turned_off_or_on_with_it(): void
    {
        $uid = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';
        $one = $this->browser('https://push.test/one');
        $two = $this->browser('https://push.test/two');
        $this->register($one, $uid);
        $this->turnOff($one, $uid);
        $row = $this->getJson('/api/push/devices', $this->h)->json('data');
        $this->assertSame([1, 'revoked'], [count($row), $row[0]['status']]);

        $this->register($two, $uid);
        $row = $this->getJson('/api/push/devices', $this->h)->json('data');
        $this->assertSame([1, 'active'], [count($row), $row[0]['status']]);
    }

    public function test_a_browser_registered_before_it_had_an_id_is_adopted_not_duplicated(): void
    {
        $sub = $this->browser('https://push.test/old');
        $this->register($sub, null);                       // an older page: no id yet
        $this->register($sub, 'cccccccc-1111-4222-8333-dddddddddddd'); // the same address, now with an id
        $this->assertSame(1, PushDevice::withoutGlobalScopes()->count());
        $this->assertSame('cccccccc-1111-4222-8333-dddddddddddd', PushDevice::withoutGlobalScopes()->sole()->device_uid);

        // the same browser had meanwhile also been saved under its NEW address (without an id): the two become one
        $uid = 'cccccccc-1111-4222-8333-dddddddddddd';
        $newer = $this->browser('https://push.test/newer');
        $this->register($newer, null);
        $this->assertSame(2, PushDevice::withoutGlobalScopes()->count());
        $this->register($newer, $uid);
        $this->assertSame(1, PushDevice::withoutGlobalScopes()->count());
        $this->assertSame('https://push.test/newer', PushDevice::withoutGlobalScopes()->sole()->token);
    }

    public function test_two_different_browsers_stay_two_devices_and_a_bad_id_is_ignored(): void
    {
        $this->register($this->browser('https://push.test/a'), 'dddddddd-1111-4222-8333-aaaaaaaaaaaa');
        $this->register($this->browser('https://push.test/b'), 'dddddddd-1111-4222-8333-bbbbbbbbbbbb');
        $this->assertSame(2, PushDevice::withoutGlobalScopes()->count());

        $this->register($this->browser('https://push.test/c'), 'bad id!'); // not a valid id: treated as having none
        $this->assertNull(PushDevice::withoutGlobalScopes()->orderByDesc('id')->first()->device_uid);
    }

    public function test_an_app_keeps_one_device_when_its_firebase_token_changes(): void
    {
        $a = $this->postJson('/api/v1/push/devices', ['token' => 'fcm-token-old', 'platform' => 'android', 'device_id' => 'install-1234abcd'], $this->h)->assertCreated()->json('id');
        $b = $this->postJson('/api/v1/push/devices', ['token' => 'fcm-token-new', 'platform' => 'android', 'device_id' => 'install-1234abcd'], $this->h)->assertCreated()->json('id');
        $this->assertSame($a, $b);
        $this->assertSame([1, 'fcm-token-new'], [PushDevice::withoutGlobalScopes()->count(), PushDevice::withoutGlobalScopes()->sole()->token]);
    }

    public function test_the_scripts_send_the_browsers_own_id(): void
    {
        $this->assertStringContainsString("localStorage.getItem('fcpush-uid')", $this->get('/api/push/sdk.js')->getContent());
        $this->assertStringContainsString('device_uid: uid()', $this->get('/api/push/sdk.js')->getContent());
    }

    public function test_with_a_list_the_people_in_it_without_a_device_are_skipped(): void
    {
        $this->live();
        $ann = $this->person('ann@example.com');
        $bob = $this->person('nodevice@example.com');
        $this->person('outside@example.com'); // not in the list: not part of this audience at all
        $this->subscribe($this->browser(), 'ann@example.com');
        $list = PeopleList::create(['name' => 'VIPs']);
        Person::withoutGlobalScopes()->whereIn('id', [$ann, $bob])->get()->each(fn ($p) => $p->lists()->attach($list->id));
        Http::fake(['*' => Http::response('', 201)]);

        $id = $this->campaign(['people_list_id' => $list->id]);
        $this->send($id);

        $c = Campaign::first()->fresh();
        $this->assertSame([2, 1], [(int) $c->audience_total, (int) $c->audience_skipped]); // Bob is in the list but has no device
        $deliveries = $this->getJson("/api/reports/campaigns/$id/deliveries", $this->h)->assertOk()->json('data');
        $this->assertSame(['SENT', 'SKIPPED'], array_column($deliveries, 'status'));
    }
}
