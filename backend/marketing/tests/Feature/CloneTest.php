<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\LandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloneTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $email = 'o@x.com', string $co = 'Acme'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => $co, 'name' => 'Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    public function test_a_page_is_cloned_unpublished_with_its_own_address(): void
    {
        $h = $this->owner();
        $page = $this->postJson('/api/landing-pages', ['name' => 'Spring Sale', 'title' => 'Big sale', 'html' => '<p>x</p>', 'design' => ['kind' => 'page', 'blocks' => []], 'published' => true], $h)->assertCreated()->json();
        LandingPage::withoutGlobalScopes()->whereKey($page['id'])->update(['views' => 40]);

        $copy = $this->postJson('/api/landing-pages/'.$page['id'].'/duplicate', [], $h)->assertCreated()->json();
        $this->assertSame(['Copy of Spring Sale', 'Big sale', false, 0], [$copy['name'], $copy['title'], (bool) $copy['published'], $copy['views']]);
        $this->assertSame('copy-of-spring-sale', $copy['slug']);
        $this->assertNotSame($page['slug'], $copy['slug']);
        $this->assertSame(['kind' => 'page', 'blocks' => []], $copy['design']);
        $this->assertSame(2, LandingPage::count());

        // cloning the clone again still finds a free address
        $this->assertNotSame($copy['slug'], $this->postJson('/api/landing-pages/'.$copy['id'].'/duplicate', [], $h)->assertCreated()->json('slug'));
    }

    public function test_a_form_is_cloned_with_its_fields_but_none_of_its_numbers(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'Newsletter', 'type' => 'newsletter', 'subscribe' => true, 'save_person' => true, 'button_text' => 'Join'], $h)->assertCreated()->json();
        Form::withoutGlobalScopes()->whereKey($form['id'])->update(['views' => 10, 'submissions' => 3]);

        $copy = $this->postJson('/api/forms/'.$form['id'].'/duplicate', [], $h)->assertCreated()->json();
        $this->assertSame(['Copy of Newsletter', 'Join', 0, 0], [$copy['name'], $copy['button_text'], $copy['views'], $copy['submissions']]);
        $this->assertNotSame($form['slug'], $copy['slug']);
        $this->assertSame($form['fields'], $copy['fields']);
        $this->assertSame(2, Form::count());
    }

    public function test_another_company_cannot_clone_it(): void
    {
        $h = $this->owner();
        $page = $this->postJson('/api/landing-pages', ['name' => 'Mine', 'html' => '<p>x</p>'], $h)->assertCreated()->json('id');
        $form = $this->postJson('/api/forms', ['name' => 'Mine', 'type' => 'contact'], $h)->assertCreated()->json('id');

        $other = $this->owner('b@x.com', 'Other');
        $this->postJson("/api/landing-pages/$page/duplicate", [], $other)->assertNotFound();
        $this->postJson("/api/forms/$form/duplicate", [], $other)->assertNotFound();
    }
}
