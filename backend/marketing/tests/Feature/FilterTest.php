<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterTest extends TestCase
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

    public function test_the_form_inbox_can_be_searched_and_filtered(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'F', 'type' => 'contact', 'save_person' => false], $h)->json();
        $this->postJson('/api/public/forms/'.$form['slug'], ['email' => 'ann@x.com', 'first_name' => 'Ann'])->assertOk();
        $this->postJson('/api/public/forms/'.$form['slug'], ['email' => 'bob@x.com', 'first_name' => 'Bob'])->assertOk();

        $this->assertSame(2, $this->getJson("/api/forms/{$form['id']}/submissions", $h)->json('total'));
        $this->assertSame(1, $this->getJson("/api/forms/{$form['id']}/submissions?search=bob", $h)->json('total'));
        $this->assertSame(0, $this->getJson("/api/forms/{$form['id']}/submissions?search=nobody", $h)->json('total'));
        $this->assertSame(2, $this->getJson("/api/forms/{$form['id']}/submissions?person=no", $h)->json('total'));
        $this->assertSame(0, $this->getJson("/api/forms/{$form['id']}/submissions?person=yes", $h)->json('total'));
        $this->assertSame(0, $this->getJson("/api/forms/{$form['id']}/submissions?from=2999-01-01", $h)->json('total'));
    }

    public function test_campaign_reports_can_be_searched_and_filtered(): void
    {
        $h = $this->owner();
        $this->postJson('/api/people', ['email' => 'a@x.com'], $h)->assertCreated();
        foreach (['Spring sale', 'Welcome'] as $name) {
            $id = $this->postJson('/api/campaigns', ['name' => $name, 'type' => 'email', 'subject' => 'S', 'body' => '<p>x</p>'], $h)->json('id');
            $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();
        }

        $this->assertSame(2, $this->getJson('/api/reports/campaigns', $h)->json('total'));
        $this->assertSame(['Spring sale'], collect($this->getJson('/api/reports/campaigns?search=spring', $h)->json('data'))->pluck('name')->all());
        $this->assertSame(0, $this->getJson('/api/reports/campaigns?status=failed', $h)->json('total'));
        $this->assertSame(0, $this->getJson('/api/reports/campaigns?type=sms', $h)->json('total'));
        $this->assertSame(0, $this->getJson('/api/reports/campaigns?from=2999-01-01', $h)->json('total'));
    }
}
