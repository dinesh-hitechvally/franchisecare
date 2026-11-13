<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PushDevice;
use App\Services\Push\WebPush;
use App\Services\PushSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The "Push notifications" block of the page builder: a card with a button that registers the visitor's browser as a device. */
class PagePushBlockTest extends TestCase
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

    protected function page(string $body): string
    {
        $html = '<!doctype html><html><head><title>T</title></head><body>'.$body.'</body></html>';
        $slug = $this->postJson('/api/landing-pages', ['name' => 'Promo', 'html' => $html, 'published' => true], $this->h)->assertSuccessful()->json('slug');
        $this->app['auth']->forgetGuards();

        return $slug;
    }

    protected const CARD = '<div class="fc-push" data-on="You are in!"><button type="button" class="fc-push-btn">Turn on</button><p class="fc-push-msg"></p></div>';

    public function test_a_published_page_with_the_block_gets_the_script_with_the_company_key_and_a_nonce(): void
    {
        $slug = $this->page(self::CARD.'<!--fc-push-->');
        $res = $this->get("/p/$slug")->assertOk();
        $key = Company::first()->fresh()->push_key;

        $this->assertNotNull($key);
        $res->assertSee("var KEY='$key'", false)->assertSee('/api/push/worker.js', false)->assertSee('/api/push/subscribe', false)->assertSee('requestPermission()', false);
        $res->assertDontSee('<!--fc-push-->', false);
        preg_match("/script-src 'nonce-([^']+)'/", $res->headers->get('Content-Security-Policy'), $m);
        $res->assertSee('<script nonce="'.$m[1].'">', false); // the page's own policy lets it run
        $this->assertStringContainsString("worker-src 'self'", $res->headers->get('Content-Security-Policy'));
        $res->assertDontSee('email', false) ; // no email is asked for or sent
    }

    public function test_several_blocks_share_one_script_and_a_page_without_the_block_has_none(): void
    {
        $slug = $this->page(self::CARD.'<!--fc-push-->'.self::CARD.'<!--fc-push-->');
        $this->assertSame(1, substr_count($this->get("/p/$slug")->getContent(), "var KEY='"));

        $plain = $this->page('<h1>Hello</h1>');
        $this->get("/p/$plain")->assertOk()->assertDontSee("var KEY='", false);
    }

    public function test_the_script_registers_the_browser_without_an_email(): void
    {
        $script = $this->get('/p/'.$this->page(self::CARD.'<!--fc-push-->'))->getContent();
        preg_match('~body: ?JSON\.stringify\(\{([^}]*)\}\)~', $script, $m);
        $this->assertSame("key:KEY,subscription:sub.toJSON(),page_url:location.href.split('#')[0],device_uid:uid()", $m[1]); // the key, the subscription, the page and the browser's own id: no email
    }

    public function test_the_notification_stays_where_it_is_placed_and_explains_what_it_cannot_do(): void
    {
        $script = $this->get('/p/'.$this->page(self::CARD.'<!--fc-push-->'))->getContent();
        $this->assertStringNotContainsString("showSoon", $script);                          // nothing slides in or floats: the box is part of the page
        $this->assertStringNotContainsString("data-pos", $script);
        $this->assertStringContainsString("Notification.permission==='denied'", $script);   // blocked notifications are explained in the box
        $this->assertStringContainsString("This browser cannot receive notifications.", $script);
        $this->assertStringContainsString("localStorage.setItem(LATER", $script);          // closing a push notification is remembered for a week
        $this->assertStringContainsString("if(dismissed()){hide();return}", $script);
    }

    public function test_normal_notifications_get_the_same_script_and_can_hide_themselves(): void
    {
        $note = '<div class="pn fc-note" data-pos="br" data-secs="5"><button type="button" class="pn-x">x</button></div>';
        $script = $this->get('/p/'.$this->page($note.'<!--fc-push-->'))->getContent();

        $this->assertStringContainsString(".fc-push,.fc-note", $script);       // one script wires both kinds
        $this->assertStringContainsString("getAttribute('data-secs')", $script); // a normal notification can hide itself
        $this->assertStringContainsString("querySelector('.pn-x')", $script);    // and has a close button
    }

    public function test_a_visitor_can_turn_notifications_off_again_from_the_notification(): void
    {
        $script = $this->get('/p/'.$this->page(self::CARD.'<!--fc-push-->'))->getContent();
        $this->assertStringContainsString("'.fc-push-off'", $script);                   // the turn-off button
        $this->assertStringContainsString("/api/push/unsubscribe", $script);            // the server switches the device off (and records it in the device history)
        $this->assertStringContainsString("sub.unsubscribe()", $script);                // and the browser forgets the subscription
        $this->assertStringContainsString("show(btn,!isOn);show(off,isOn)", $script);   // allow and turn-off swap places
        $this->assertStringContainsString("'.fc-close-btn'", $script);                  // a button whose action is "close"
    }

    public function test_turning_off_from_the_page_marks_the_device_as_turned_off(): void
    {
        $company = Company::first();
        $key = PushSettings::siteKey($company);
        $sub = ['endpoint' => 'https://push.test/x', 'keys' => ['p256dh' => WebPush::generateKeys()[0], 'auth' => WebPush::b64(random_bytes(16))]];
        config(['services.outbound.allow_private' => true]);
        $this->postJson('/api/push/subscribe', ['key' => $key, 'subscription' => $sub, 'page_url' => 'https://shop.test/p'])->assertOk();
        $this->postJson('/api/push/unsubscribe', ['key' => $key, 'endpoint' => $sub['endpoint']])->assertOk();
        $this->assertSame('revoked', PushDevice::withoutGlobalScopes()->sole()->status);

        // allowing again on the same page turns the same device on again
        $this->postJson('/api/push/subscribe', ['key' => $key, 'subscription' => $sub, 'page_url' => 'https://shop.test/p'])->assertOk();
        $this->assertSame(['active', 1], [PushDevice::withoutGlobalScopes()->sole()->status, PushDevice::withoutGlobalScopes()->count()]);
    }
}
