<?php

namespace Tests\Feature;

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormSubscribeFieldTest extends TestCase
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

    protected function slug(bool $required): string
    {
        $fields = [
            ['type' => 'email', 'label' => 'Email', 'required' => true],
            ['type' => 'subscribe', 'label' => 'Subscribe me to the newsletter', 'required' => $required],
        ];
        $slug = $this->postJson('/api/forms', ['name' => 'News', 'type' => 'newsletter', 'subscribe' => true, 'save_person' => true, 'fields' => $fields], $this->h)->assertCreated()->json('slug');
        $this->app['auth']->forgetGuards();

        return $slug;
    }

    public function test_only_people_who_tick_the_box_are_subscribed(): void
    {
        $slug = $this->slug(false);

        $this->postJson("/api/public/forms/$slug", ['email' => 'yes@x.com', 'subscribe' => true])->assertOk();
        $this->postJson("/api/public/forms/$slug", ['email' => 'no@x.com'])->assertOk();

        $this->assertTrue((bool) Person::withoutGlobalScopes()->where('email', 'yes@x.com')->value('is_subscribed'));
        $this->assertFalse((bool) Person::withoutGlobalScopes()->where('email', 'no@x.com')->value('is_subscribed'));
    }

    public function test_a_required_box_must_be_ticked_and_a_returning_opt_out_is_never_re_subscribed(): void
    {
        $slug = $this->slug(true);
        $this->postJson("/api/public/forms/$slug", ['email' => 'a@x.com'])->assertStatus(422);

        $this->postJson("/api/public/forms/$slug", ['email' => 'a@x.com', 'subscribe' => true])->assertOk();
        Person::withoutGlobalScopes()->where('email', 'a@x.com')->update(['is_subscribed' => false]);
        $this->postJson("/api/public/forms/$slug", ['email' => 'a@x.com', 'subscribe' => true])->assertOk();
        $this->assertFalse((bool) Person::withoutGlobalScopes()->where('email', 'a@x.com')->value('is_subscribed'));
    }

    public function test_the_box_can_only_be_added_once_and_shows_on_the_hosted_form(): void
    {
        $fields = [['type' => 'email', 'label' => 'Email'], ['type' => 'subscribe', 'label' => 'A'], ['type' => 'subscribe', 'label' => 'B']];
        $this->postJson('/api/forms', ['name' => 'Twice', 'fields' => $fields], $this->h)->assertStatus(422);

        $slug = $this->slug(true);
        $this->get("/f/$slug")->assertOk()->assertSee('name="subscribe"', false)->assertSee('Subscribe me to the newsletter');
    }
}
