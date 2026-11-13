<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Person;
use App\Services\FormEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The one-script form embed: forms.js once on a website, and <div class="mate-form" data-form-id="12"> wherever a form should appear. */
class FormEmbedTest extends TestCase
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

    protected function form(array $extra = [], ?array $fields = null): array
    {
        $fields ??= [['type' => 'first_name', 'label' => 'First name'], ['type' => 'email', 'label' => 'Email', 'required' => true]];
        $r = $this->postJson('/api/forms', array_merge(['name' => 'News', 'type' => 'newsletter', 'save_person' => true, 'button_text' => 'Join now', 'fields' => $fields], $extra), $this->h)->assertCreated();
        $this->app['auth']->forgetGuards();

        return $r->json();
    }

    protected function key(): string
    {
        return FormEmbed::key(Company::first()->fresh());
    }

    public function test_the_script_is_served_and_the_share_data_carries_its_address(): void
    {
        $js = $this->get('/api/embed/forms.js')->assertOk();
        $this->assertStringContainsString('javascript', $js->headers->get('Content-Type'));
        $js->assertSee('mate-form', false)->assertSee('data-form-id', false)->assertSee('/api/public/forms/', false);

        $form = $this->form();
        $listed = collect($this->getJson('/api/forms', $this->h)->assertOk()->json())->firstWhere('id', $form['id']);
        $this->assertSame(url('/api/embed/forms.js').'?c='.$this->key(), $listed['embed_script']);
        $this->assertSame(40, strlen($this->key()));
    }

    public function test_a_form_is_found_by_its_id_and_the_company_key_and_comes_with_its_own_styles(): void
    {
        $form = $this->form(['style' => ['accent' => '#ff0066']]);
        $d = $this->getJson('/api/embed/forms/'.$this->key().'/'.$form['id'])->assertOk()->json();

        $this->assertSame([$form['id'], $form['slug']], [$d['id'], $d['slug']]);
        $this->assertStringContainsString('<form', $d['html']);
        $this->assertStringContainsString('Join now', $d['html']);
        $this->assertStringContainsString('name="email"', $d['html']);
        $this->assertStringNotContainsString('<script', $d['html']);                      // nothing to run inside the markup: the embed script does the sending
        $this->assertStringContainsString('.fcs-e'.$form['id'].' .fc-form', $d['css']);   // styles are scoped to this form so they cannot leak into the website
        $this->assertStringContainsString('#ff0066', $d['css']);
        $this->assertSame(1, Form::withoutGlobalScopes()->find($form['id'])->views);        // a showing counts as a view
    }

    public function test_the_key_and_the_id_must_belong_together_and_the_form_must_be_active(): void
    {
        $form = $this->form();
        $this->getJson('/api/embed/forms/'.str_repeat('x', 40).'/'.$form['id'])->assertNotFound(); // unknown key
        $this->getJson('/api/embed/forms/short/'.$form['id'])->assertNotFound();

        // another company's key does not open this company's form
        $this->app['auth']->forgetGuards();
        $other = $this->postJson('/api/register', ['company_name' => 'Other', 'name' => 'B', 'email' => 'b@x.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertOk();
        $otherKey = FormEmbed::key(Company::where('name', 'Other')->first());
        $this->getJson('/api/embed/forms/'.$otherKey.'/'.$form['id'])->assertNotFound();

        // a paused form does not show
        Form::withoutGlobalScopes()->whereKey($form['id'])->update(['active' => false]);
        $this->getJson('/api/embed/forms/'.$this->key().'/'.$form['id'])->assertNotFound();
    }

    public function test_the_push_tick_box_is_left_out_because_a_foreign_website_cannot_use_it(): void
    {
        $form = $this->form([], [['type' => 'email', 'label' => 'Email', 'required' => true], ['type' => 'push', 'label' => 'Notify me']]);
        $this->assertStringContainsString('data-push', $this->get('/f/'.$form['slug'])->getContent()); // on the hosted page it is there

        $d = $this->getJson('/api/embed/forms/'.$this->key().'/'.$form['id'])->assertOk()->json();
        $this->assertStringNotContainsString('Notify me', $d['html']);
        $this->assertStringNotContainsString('data-push', $d['html']);
    }

    public function test_an_embedded_form_sends_its_answers_to_the_same_endpoint_and_works_from_another_website(): void
    {
        $form = $this->form();
        $d = $this->getJson('/api/embed/forms/'.$this->key().'/'.$form['id'])->json();

        // the embed script posts what the visitor typed to /api/public/forms/{slug}, like the hosted page
        $this->postJson('/api/public/forms/'.$d['slug'], ['first_name' => 'Eve', 'email' => 'eve@example.com'])->assertOk();
        $this->assertSame('eve@example.com', Person::withoutGlobalScopes()->where('email', 'eve@example.com')->value('email'));
        $this->assertSame(1, FormSubmission::withoutGlobalScopes()->count());

        // another website may read the form and send to it (cross-origin)
        $headers = ['Origin' => 'https://customer-site.example', 'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'content-type'];
        $this->call('OPTIONS', '/api/public/forms/'.$d['slug'], [], [], [], $this->transformHeadersToServerVars($headers))->assertHeader('Access-Control-Allow-Origin', '*');
        $this->get('/api/embed/forms/'.$this->key().'/'.$form['id'], ['Origin' => 'https://customer-site.example'])->assertHeader('Access-Control-Allow-Origin', '*');
    }
}
