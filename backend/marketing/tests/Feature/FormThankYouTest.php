<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormThankYouTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    public function test_the_hosted_form_tells_the_visitor_clearly_that_it_was_submitted(): void
    {
        $h = $this->owner();
        $slug = $this->postJson('/api/forms', ['name' => 'Contact', 'type' => 'contact', 'success_message' => 'Thank you, we will reply soon.'], $h)->assertCreated()->json('slug');
        $this->app['auth']->forgetGuards();

        $page = $this->get("/f/$slug")->assertOk()->getContent();
        // the script that runs after sending: a thank-you panel, a line saying it worked, a way to send again, no double submissions
        $this->assertStringContainsString("className='fc-done'", $page);
        $this->assertStringContainsString('Your form has been submitted successfully.', $page);
        $this->assertStringContainsString('Send another response', $page);
        $this->assertStringContainsString('b.disabled=true', $page);
        $this->assertStringContainsString('We could not send your form', $page); // a lost connection is explained too
        // and the styles that make the panel and the problem message stand out
        $this->assertStringContainsString('.fc-done-msg', $page);
        $this->assertStringContainsString('.fc-msg.err', $page);

        // what the page script shows is the form's own message, plus where to go if the form asks for it
        $answer = $this->postJson("/api/public/forms/$slug", ['email' => 'visitor@x.com', 'first_name' => 'Vi'])->assertOk()->json();
        $this->assertSame('Thank you, we will reply soon.', $answer['message']);
        $this->assertArrayHasKey('redirect', $answer);
    }
}
