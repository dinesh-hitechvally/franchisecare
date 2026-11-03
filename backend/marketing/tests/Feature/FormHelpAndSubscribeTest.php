<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Services\FormRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormHelpAndSubscribeTest extends TestCase
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

    protected function slug(array $body): string
    {
        $slug = $this->postJson('/api/forms', $body + ['name' => 'F'], $this->h)->assertCreated()->json('slug');
        $this->app['auth']->forgetGuards();

        return $slug;
    }

    public function test_short_help_stays_under_the_field_and_long_help_becomes_an_icon_with_the_text_on_hover(): void
    {
        $long = str_repeat('Tell us a little more. ', 4); // well over the limit
        $this->assertGreaterThan(FormRenderer::HELP_LONG, mb_strlen($long));
        $slug = $this->slug(['fields' => [
            ['type' => 'email', 'label' => 'Email', 'help' => 'We never share it.'],
            ['type' => 'text', 'label' => 'Why', 'help' => $long],
            ['type' => 'checkbox', 'label' => 'Agree', 'help' => $long],
        ]]);

        $html = $this->get("/f/$slug")->assertOk()->getContent();
        $this->assertStringContainsString('<small class="fc-help">We never share it.</small>', $html);
        $this->assertSame(2, substr_count($html, 'class="fc-i"')); // the text field and the checkbox
        $this->assertSame(2, substr_count($html, '<span class="fc-ib">'.trim($long))); // the full text sits in the hover box
        $this->assertStringNotContainsString('<small class="fc-help">'.trim($long), $html); // and not under the field
        $this->assertStringContainsString('class="fc-chkrow"', $html); // beside the box, so a click on the icon does not tick it
    }

    public function test_help_exactly_at_the_limit_is_still_shown_as_text(): void
    {
        $text = str_repeat('a', FormRenderer::HELP_LONG);
        $slug = $this->slug(['fields' => [['type' => 'email', 'label' => 'Email', 'help' => $text]]]);

        $html = $this->get("/f/$slug")->getContent();
        $this->assertStringContainsString('<small class="fc-help">'.$text.'</small>', $html);
        $this->assertStringNotContainsString('class="fc-i"', $html);
    }

    public function test_the_subscribe_box_decides_even_when_the_form_default_is_not_subscribed(): void
    {
        // a form that by default does not subscribe (an "other" form), but has the box
        $slug = $this->slug(['type' => 'other', 'subscribe' => false, 'save_person' => true, 'fields' => [
            ['type' => 'email', 'label' => 'Email', 'required' => true],
            ['type' => 'subscribe', 'label' => 'Subscribe me to the newsletter', 'required' => false],
        ]]);
        $this->postJson("/api/public/forms/$slug", ['email' => 'yes@x.com', 'subscribe' => true])->assertOk();
        $this->postJson("/api/public/forms/$slug", ['email' => 'no@x.com'])->assertOk();
        $this->assertTrue((bool) Person::withoutGlobalScopes()->where('email', 'yes@x.com')->value('is_subscribed'));
        $this->assertFalse((bool) Person::withoutGlobalScopes()->where('email', 'no@x.com')->value('is_subscribed'));
    }

    public function test_without_a_box_the_form_default_applies(): void
    {
        $news = $this->slug(['type' => 'newsletter', 'subscribe' => true, 'save_person' => true, 'fields' => [['type' => 'email', 'label' => 'Email', 'required' => true]]]);
        $other = $this->slug(['type' => 'other', 'subscribe' => false, 'save_person' => true, 'fields' => [['type' => 'email', 'label' => 'Email', 'required' => true]]]);
        $this->postJson("/api/public/forms/$news", ['email' => 'a@x.com'])->assertOk();
        $this->postJson("/api/public/forms/$other", ['email' => 'b@x.com'])->assertOk();

        $this->assertTrue((bool) Person::withoutGlobalScopes()->where('email', 'a@x.com')->value('is_subscribed'));
        $this->assertFalse((bool) Person::withoutGlobalScopes()->where('email', 'b@x.com')->value('is_subscribed'));
    }
}
