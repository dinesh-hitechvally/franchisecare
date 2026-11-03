<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageSlugTest extends TestCase
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

    protected function page(array $h, array $over = [])
    {
        return $this->postJson('/api/landing-pages', $over + ['name' => 'Spring Sale 2026', 'html' => '<p>x</p>', 'published' => true], $h);
    }

    public function test_an_empty_slug_is_made_from_the_page_name_and_kept_unique(): void
    {
        $h = $this->owner();
        $a = $this->page($h)->assertCreated()->json();
        $this->assertSame('spring-sale-2026', $a['slug']);
        $this->assertStringEndsWith('/p/spring-sale-2026', $a['url']);

        $b = $this->page($h)->assertCreated()->json('slug'); // same name again
        $this->assertMatchesRegularExpression('/^spring-sale-2026-[a-z0-9]{4}$/', $b);

        $this->assertSame('page', substr($this->page($h, ['name' => '!!!'])->json('slug'), 0, 4)); // nothing usable in the name
    }

    public function test_a_typed_slug_is_cleaned_and_must_be_free(): void
    {
        $h = $this->owner();
        $this->assertSame('big-offer-now', $this->page($h, ['slug' => 'Big Offer  Now'])->assertCreated()->json('slug'));
        $this->page($h, ['name' => 'Other', 'slug' => 'big-offer-now'])->assertStatus(422)->assertJsonPath('message', 'That address is already used by another page. Choose a different one.');

        // addresses are public and have no company in them, so another company cannot take it either
        $other = $this->owner('b@x.com', 'Other Co');
        $this->page($other, ['slug' => 'big-offer-now'])->assertStatus(422);
        $this->page($other, ['slug' => 'big offer!'])->assertStatus(422); // not an address at all
    }

    public function test_editing_keeps_the_address_unless_a_new_one_is_typed(): void
    {
        $h = $this->owner();
        $page = $this->page($h, ['slug' => 'summer'])->assertCreated()->json();

        $same = $this->putJson('/api/landing-pages/'.$page['id'], ['name' => 'Renamed', 'html' => '<p>y</p>'], $h)->assertOk()->json();
        $this->assertSame('summer', $same['slug']); // empty = unchanged, the page's links keep working

        $this->putJson('/api/landing-pages/'.$page['id'], ['name' => 'Renamed', 'html' => '<p>y</p>', 'slug' => 'summer'], $h)->assertOk()->assertJsonPath('slug', 'summer');
        $this->putJson('/api/landing-pages/'.$page['id'], ['name' => 'Renamed', 'html' => '<p>y</p>', 'slug' => 'winter'], $h)->assertOk()->assertJsonPath('slug', 'winter');

        $this->get('/p/winter')->assertOk();
        $this->get('/p/summer')->assertNotFound();
    }
}
