<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormFieldRulesTest extends TestCase
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

    protected function form(array $fields)
    {
        $r = $this->postJson('/api/forms', ['name' => 'Rules', 'type' => 'other', 'save_person' => false, 'fields' => $fields], $this->h);
        $this->app['auth']->forgetGuards();

        return $r;
    }

    protected function submit(string $slug, array $body)
    {
        return $this->postJson("/api/public/forms/$slug", $body);
    }

    public function test_length_pattern_and_custom_message_are_enforced(): void
    {
        $f = $this->form([
            ['type' => 'text', 'label' => 'Code', 'rules' => ['min_length' => 3, 'max_length' => 5, 'pattern' => '^[A-Z]+$', 'message' => 'Use 3 to 5 capital letters.']],
        ])->assertCreated();
        $key = $f->json('fields.0.key');
        $slug = $f->json('slug');
        $this->assertSame(['min_length' => 3, 'max_length' => 5, 'pattern' => '^[A-Z]+$', 'message' => 'Use 3 to 5 capital letters.'], $f->json('fields.0.rules'));

        $this->submit($slug, [$key => 'AB'])->assertStatus(422)->assertJsonPath('errors.'.$key.'.0', 'Use 3 to 5 capital letters.');
        $this->submit($slug, [$key => 'ABCDEF'])->assertStatus(422);
        $this->submit($slug, [$key => 'abc'])->assertStatus(422)->assertJsonPath('errors.'.$key.'.0', 'Use 3 to 5 capital letters.'); // wrong format
        $this->submit($slug, [$key => 'ABCD'])->assertOk();
    }

    public function test_number_and_date_ranges_and_allowed_email_domains(): void
    {
        $f = $this->form([
            ['type' => 'email', 'label' => 'Work email', 'rules' => ['allowed_domains' => '@Acme.com, acme.org']],
            ['type' => 'number', 'label' => 'Guests', 'rules' => ['min' => 1, 'max' => 10]],
            ['type' => 'date', 'label' => 'Day', 'rules' => ['min' => '2030-01-01', 'max' => '2030-12-31']],
        ])->assertCreated();
        [$email, $guests, $day] = array_column($f->json('fields'), 'key');
        $slug = $f->json('slug');
        $this->assertSame(['acme.com', 'acme.org'], $f->json('fields.0.rules.allowed_domains'));

        $ok = [$email => 'me@acme.com', $guests => 4, $day => '2030-06-01'];
        $this->submit($slug, $ok)->assertOk();
        $this->submit($slug, [$email => 'me@gmail.com'] + $ok)->assertStatus(422)->assertJsonPath('errors.'.$email.'.0', 'The work email must be an address at acme.com, acme.org.');
        $this->submit($slug, [$guests => 0] + $ok)->assertStatus(422)->assertJsonValidationErrors($guests);
        $this->submit($slug, [$guests => 11] + $ok)->assertStatus(422)->assertJsonValidationErrors($guests);
        $this->submit($slug, [$day => '2029-12-31'] + $ok)->assertStatus(422)->assertJsonValidationErrors($day);
        $this->submit($slug, [$day => '2031-01-01'] + $ok)->assertStatus(422)->assertJsonValidationErrors($day);
    }

    public function test_the_hosted_form_passes_the_limits_to_the_browser(): void
    {
        $slug = $this->form([
            ['type' => 'text', 'label' => 'Code', 'rules' => ['min_length' => 3, 'max_length' => 5]],
            ['type' => 'number', 'label' => 'Guests', 'rules' => ['min' => 1, 'max' => 10]],
        ])->assertCreated()->json('slug');

        $html = $this->get("/f/$slug")->assertOk()->getContent();
        $this->assertStringContainsString('minlength="3" maxlength="5"', $html);
        $this->assertStringContainsString('min="1" max="10"', $html);
    }

    public function test_rules_that_make_no_sense_are_refused_or_dropped(): void
    {
        $this->form([['type' => 'text', 'label' => 'A', 'rules' => ['min_length' => 9, 'max_length' => 3]]])->assertStatus(422);
        $this->form([['type' => 'number', 'label' => 'B', 'rules' => ['min' => 10, 'max' => 1]]])->assertStatus(422);
        $this->form([['type' => 'text', 'label' => 'C', 'rules' => ['pattern' => '([a-z']]])->assertStatus(422);

        // a rule that does not apply to the type is dropped, headings carry none
        $f = $this->form([
            ['type' => 'dropdown', 'label' => 'Plan', 'options' => ['Free'], 'rules' => ['min_length' => 3, 'pattern' => 'x']],
            ['type' => 'heading', 'label' => 'About', 'rules' => ['min_length' => 3]],
        ])->assertCreated();
        $this->assertSame([[], []], [$f->json('fields.0.rules'), $f->json('fields.1.rules')]);
    }

    public function test_a_ready_made_format_works_on_email_and_phone_fields(): void
    {
        $f = $this->form([
            ['type' => 'email', 'label' => 'Email', 'rules' => ['pattern' => '^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$', 'message' => 'Enter a valid email address.']],
            ['type' => 'phone', 'label' => 'Mobile', 'rules' => ['pattern' => '^\+[1-9][0-9]{7,14}$', 'message' => 'Enter the number with country code.']],
        ])->assertCreated();
        $this->assertArrayHasKey('pattern', $f->json('fields.0.rules'));
        $slug = $f->json('slug');

        $this->submit($slug, ['email' => 'ann@acme.com', 'phone' => '+61400000000'])->assertOk();
        $this->submit($slug, ['email' => 'ann@acme.com', 'phone' => '0400000000'])->assertStatus(422)->assertJsonPath('errors.phone.0', 'Enter the number with country code.');
        $this->submit($slug, ['email' => 'ann@acme', 'phone' => '+61400000000'])->assertStatus(422)->assertJsonValidationErrors('email');
    }
}
