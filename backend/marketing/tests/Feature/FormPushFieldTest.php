<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FormSubmission;
use App\Models\PushDevice;
use App\Services\Push\WebPush;
use App\Services\PushSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The "Push notifications" form field: a tick box that turns notifications on in the visitor's browser and links it to the person. */
class FormPushFieldTest extends TestCase
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
    }

    protected function form(array $extra = [], array $fields = null): string
    {
        $fields ??= [['type' => 'email', 'label' => 'Email', 'required' => true], ['type' => 'push', 'label' => 'Send me notifications on this device']];
        $slug = $this->postJson('/api/forms', array_merge(['name' => 'News', 'type' => 'newsletter', 'save_person' => true, 'fields' => $fields], $extra), $this->h)->assertCreated()->json('slug');
        $this->app['auth']->forgetGuards();

        return $slug;
    }

    public function test_the_hosted_form_shows_the_tick_box_and_carries_what_the_browser_needs(): void
    {
        $slug = $this->form();
        $page = $this->get("/f/$slug")->assertOk();
        $key = Company::first()->fresh()->push_key;
        $this->assertNotNull($key); // created when the form is first shown
        $page->assertSee('data-push="1"', false)->assertSee('name="push"', false)->assertSee('Send me notifications on this device', false);
        $page->assertSee("PKEY='".$key."'", false);
        $page->assertSee('/api/push/worker.js', false)->assertSee('enablePush(', false);
        $this->assertStringContainsString("worker-src 'self'", $page->headers->get('Content-Security-Policy')); // browsers refuse the service worker otherwise
    }

    public function test_a_form_without_the_field_carries_no_push_script_or_key(): void
    {
        $slug = $this->form([], [['type' => 'email', 'label' => 'Email', 'required' => true]]);
        $page = $this->get("/f/$slug")->assertOk();
        $page->assertSee("PKEY=''", false)->assertDontSee('data-push="1"', false);
    }

    public function test_submitting_records_the_answer_and_the_browser_can_then_be_linked_by_the_same_email(): void
    {
        $slug = $this->form();
        $this->postJson("/api/public/forms/$slug", ['email' => 'ann@example.com', 'push' => true])->assertOk();

        $sub = FormSubmission::withoutGlobalScopes()->sole();
        $this->assertSame('Yes', collect($sub->data)->firstWhere('type', 'push')['value']);
        $this->assertSame(0, PushDevice::withoutGlobalScopes()->count()); // the page registers the browser itself, after the answer is saved

        // what the page does next: it registers the browser under the email that was just submitted
        $company = Company::first();
        $this->postJson('/api/push/subscribe', [
            'key' => PushSettings::siteKey($company), 'email' => 'ann@example.com',
            'subscription' => ['endpoint' => 'https://push.test/x', 'keys' => ['p256dh' => WebPush::generateKeys()[0], 'auth' => WebPush::b64(random_bytes(16))]],
        ])->assertOk()->assertJsonPath('linked', true); // the person was created by this very submission
        $this->assertSame('ann@example.com', PushDevice::withoutGlobalScopes()->sole()->person->email);
    }

    public function test_an_unticked_box_is_recorded_as_no_and_a_required_one_must_be_ticked(): void
    {
        $slug = $this->form();
        $this->postJson("/api/public/forms/$slug", ['email' => 'a@example.com', 'push' => false])->assertOk();
        $this->assertSame('No', collect(FormSubmission::withoutGlobalScopes()->sole()->data)->firstWhere('type', 'push')['value']);

        $this->app['auth']->forgetGuards();
        $required = $this->form(['name' => 'Must'], [['type' => 'email', 'label' => 'Email', 'required' => true], ['type' => 'push', 'label' => 'Notify me', 'required' => true]]);
        $this->postJson("/api/public/forms/$required", ['email' => 'b@example.com', 'push' => false])->assertStatus(422);
        $this->postJson("/api/public/forms/$required", ['email' => 'b@example.com', 'push' => true])->assertOk();
    }

    public function test_the_field_can_be_added_once_and_needs_no_email_or_mobile_field(): void
    {
        $two = [['type' => 'email', 'label' => 'Email'], ['type' => 'push', 'label' => 'A'], ['type' => 'push', 'label' => 'B']];
        $this->postJson('/api/forms', ['name' => 'X', 'type' => 'newsletter', 'save_person' => true, 'fields' => $two], $this->h)->assertStatus(422);

        // no email is needed to register a device: the browser simply joins the devices (an "all devices" campaign reaches it)
        $alone = $this->postJson('/api/forms', ['name' => 'Y', 'type' => 'other', 'save_person' => false, 'fields' => [['type' => 'first_name', 'label' => 'Name'], ['type' => 'push', 'label' => 'A']]], $this->h)->assertCreated();
        $pushField = collect($alone->json('fields'))->firstWhere('type', 'push');
        $this->assertSame(['push', false], [$pushField['key'], $pushField['to_person']]); // never saved on the person
    }

    public function test_a_browser_registered_without_any_email_becomes_an_unlinked_device(): void
    {
        $slug = $this->form(['save_person' => false, 'type' => 'other'], [['type' => 'first_name', 'label' => 'Name'], ['type' => 'push', 'label' => 'Notify me']]);
        $this->postJson("/api/public/forms/$slug", ['first_name' => 'Pat', 'push' => true])->assertOk();

        $this->postJson('/api/push/subscribe', [
            'key' => PushSettings::siteKey(Company::first()),
            'subscription' => ['endpoint' => 'https://push.test/x', 'keys' => ['p256dh' => WebPush::generateKeys()[0], 'auth' => WebPush::b64(random_bytes(16))]],
        ])->assertOk()->assertJsonPath('linked', false);
        $this->assertNull(PushDevice::withoutGlobalScopes()->sole()->person_id);
    }

    public function test_a_custom_field_cannot_take_the_name_push(): void
    {
        $r = $this->postJson('/api/forms', ['name' => 'K', 'type' => 'other', 'save_person' => true, 'fields' => [['type' => 'email', 'label' => 'Email'], ['type' => 'text', 'label' => 'Push']]], $this->h)->assertCreated();
        $keys = array_column($r->json('fields'), 'key');
        $this->assertNotContains('push', $keys); // it would collide with the tick box
        $this->assertContains('person_push', $keys);
    }
}
