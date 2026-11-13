<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Showing a template without sending it: settings variables are real, person variables are samples, missing ones are called out. */
class TemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme Pty', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    public function test_the_variable_list_says_where_each_value_comes_from(): void
    {
        $this->postJson('/api/person-fields', ['label' => 'Plan', 'type' => 'dropdown', 'options' => ['Gold', 'Silver']], $this->h)->assertSuccessful();
        $this->app['auth']->forgetGuards();

        $tags = collect($this->getJson('/api/merge-tags', $this->h)->assertOk()->json())->keyBy('key');
        $this->assertSame(['setting', 'Acme Pty', true], [$tags['company_name']['kind'], $tags['company_name']['value'], $tags['company_name']['set']]);
        $this->assertSame(['setting', false], [$tags['company_phone']['kind'], $tags['company_phone']['set']]); // nothing in Settings yet
        $this->assertSame(['dynamic', 'Alex'], [$tags['first_name']['kind'], $tags['first_name']['value']]);
        $this->assertSame(['custom', 'Gold'], [$tags['plan']['kind'], $tags['plan']['value']]);               // a sample taken from the field's own options
    }

    public function test_a_preview_shows_real_settings_sample_people_and_notes_for_what_is_missing(): void
    {
        $body = '<p>Hi {{first_name}} from {{company_name}}. Plan: {{ plan }}. Call {{company_phone}}. {{no_such_thing}}</p>';
        $this->postJson('/api/person-fields', ['label' => 'Plan', 'type' => 'text'], $this->h)->assertSuccessful();
        $this->app['auth']->forgetGuards();

        $html = $this->postJson('/api/campaigns/preview', ['type' => 'email', 'subject' => 'Hello {{first_name}} {{nope}}', 'body' => $body], $this->h)->assertOk()->json();
        $this->assertStringContainsString('Hi Jane from Acme Pty.', $html['html']);                     // person: sample, company: the real name
        $this->assertStringContainsString('Plan: Sample plan.', $html['html']);                         // custom field: a sample of its type
        $this->assertStringContainsString('[company_phone: not set in Settings yet]', $html['html']);   // a setting that is still empty
        $this->assertStringContainsString('[no_such_thing: this variable does not exist]', $html['html']);
        $this->assertStringContainsString('[nope: this variable does not exist]', $html['subject']);

        $this->app['auth']->forgetGuards();
        $sms = $this->postJson('/api/campaigns/preview', ['type' => 'sms', 'body' => 'Hi {{first_name}}, {{company_name}} {{missing_one}}'], $this->h)->assertOk()->json();
        $this->assertSame('Hi Jane, Acme Pty [missing_one: this variable does not exist]', $sms['text']);
    }
}
