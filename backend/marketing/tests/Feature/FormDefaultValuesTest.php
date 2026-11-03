<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormDefaultValuesTest extends TestCase
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

    public function test_default_values_are_saved_and_the_hosted_form_opens_filled_in(): void
    {
        $fields = [
            ['type' => 'email', 'label' => 'Email', 'default' => 'hello@x.com'],
            ['type' => 'textarea', 'label' => 'Note', 'default' => 'Please call me'],
            ['type' => 'dropdown', 'label' => 'Plan', 'options' => ['Free', 'Pro'], 'default' => 'Pro'],
            ['type' => 'radio', 'label' => 'Size', 'options' => ['S', 'M'], 'default' => 'M'],
            ['type' => 'checkbox', 'label' => 'Keep me posted', 'default' => 'yes'],
            ['type' => 'subscribe', 'label' => 'Subscribe me to the newsletter', 'default' => 'yes'],
        ];
        $form = $this->postJson('/api/forms', ['name' => 'Defaults', 'fields' => $fields], $this->h)->assertCreated();
        $this->assertSame(['hello@x.com', 'Please call me', 'Pro', 'M', 'yes', 'yes'], collect($form->json('fields'))->pluck('default')->all());

        $html = $this->get('/f/'.$form->json('slug'))->assertOk()->getContent();
        $this->assertStringContainsString('value="hello@x.com"', $html);
        $this->assertStringContainsString('>Please call me</textarea>', $html);
        $this->assertStringContainsString('<option value="Pro" selected>', $html);
        $this->assertStringNotContainsString('<option value="Free" selected>', $html);
        $this->assertStringContainsString('value="M" checked', $html);
        $this->assertSame(2, substr_count($html, 'type="checkbox"')); // both are ticked
        $this->assertSame(2, preg_match_all('/type="checkbox"[^>]* checked/', $html));
    }

    public function test_a_default_must_be_one_of_the_choices_and_blank_means_none(): void
    {
        $fields = [
            ['type' => 'email', 'label' => 'Email'],
            ['type' => 'dropdown', 'label' => 'Plan', 'options' => ['Free', 'Pro'], 'default' => 'Gold'],
            ['type' => 'heading', 'label' => 'About you', 'default' => 'ignored'],
        ];
        $form = $this->postJson('/api/forms', ['name' => 'Odd', 'fields' => $fields], $this->h)->assertCreated();
        $this->assertSame(['', '', ''], collect($form->json('fields'))->pluck('default')->all());
        $this->assertStringNotContainsString(' selected', $this->get('/f/'.$form->json('slug'))->getContent());
    }
}
