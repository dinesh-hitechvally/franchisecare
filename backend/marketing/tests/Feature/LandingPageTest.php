<?php

namespace Tests\Feature;

use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $company = 'Acme'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => $company, 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    protected function page(array $h, string $html, array $extra = []): array
    {
        return $this->postJson('/api/landing-pages', ['name' => 'P', 'html' => $html, 'published' => true] + $extra, $h)->assertCreated()->json();
    }

    public function test_form_appears_where_the_page_builder_put_it(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'F'], $h)->json('id');
        $p = $this->page($h, '<!doctype html><html><head><title>Landing page</title></head><body><section id="hero">HERO</section><section id="form"><h2>Join</h2><!--fc-form--></section><footer>FOOT</footer></body></html>', ['form_id' => $form]);

        $html = $this->get('/p/'.$p['slug'])->assertOk()->getContent();
        $hero = strpos($html, 'HERO');
        $formAt = strpos($html, 'id="fc-form"');
        $foot = strpos($html, 'FOOT');
        $this->assertTrue($hero < strpos($html, '<h2>Join</h2>') && strpos($html, '<h2>Join</h2>') < $formAt && $formAt < $foot, 'the form must sit inside the form section, before the footer');
        $this->assertSame(1, substr_count($html, 'id="fc-form"'));
        $this->assertStringNotContainsString('<!--fc-form-->', $html);
        $this->assertSame(1, Form::withoutGlobalScopes()->find($form)->views);
    }

    public function test_marker_is_removed_when_no_form_is_attached(): void
    {
        $h = $this->owner();
        $p = $this->page($h, '<html><head></head><body><div>A</div><!--fc-form--><div>B</div><!--fc-form--></body></html>');

        $html = $this->get('/p/'.$p['slug'])->assertOk()->getContent();
        $this->assertStringNotContainsString('fc-form', $html);
        $this->assertStringContainsString('<div>A</div><div>B</div>', $html);
    }

    public function test_pages_without_a_marker_still_get_the_form_at_the_end(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'F'], $h)->json('id');
        $p = $this->page($h, '<html><body><p>Older page</p></body></html>', ['form_id' => $form]);

        $html = $this->get('/p/'.$p['slug'])->assertOk()->getContent();
        $this->assertTrue(strpos($html, 'Older page') < strpos($html, 'id="fc-form"') && strpos($html, 'id="fc-form"') < strpos($html, '</body>'));
    }

    public function test_page_title_comes_from_the_publish_settings_and_is_escaped(): void
    {
        $h = $this->owner();
        $withTitle = $this->page($h, '<html><head><title>Landing page</title></head><body>x</body></html>', ['title' => 'My <b>Offer</b>']);
        $html = $this->get('/p/'.$withTitle['slug'])->getContent();
        $this->assertStringContainsString('<title>My &lt;b&gt;Offer&lt;/b&gt;</title>', $html);
        $this->assertStringNotContainsString('<title>Landing page</title>', $html);

        $noTag = $this->page($h, '<html><head></head><body>x</body></html>', ['title' => 'Injected']);
        $this->assertStringContainsString('<head><title>Injected</title>', $this->get('/p/'.$noTag['slug'])->getContent());

        $untitled = $this->page($h, '<html><head><title>Keep me</title></head><body>x</body></html>');
        $this->assertStringContainsString('<title>Keep me</title>', $this->get('/p/'.$untitled['slug'])->getContent());
    }

    public function test_company_name_in_a_page_is_escaped(): void
    {
        $h = $this->owner('Evil <script>alert(1)</script> & Co');
        $p = $this->page($h, '<html><body><h1>Welcome to {{company_name}}</h1></body></html>');

        $res = $this->get('/p/'.$p['slug'])->assertOk();
        $this->assertStringContainsString('Welcome to Evil &lt;script&gt;alert(1)&lt;/script&gt; &amp; Co', $res->getContent());
        $this->assertStringNotContainsString('<script>alert(1)', $res->getContent());
        $this->assertStringContainsString("script-src 'nonce-", $res->headers->get('Content-Security-Policy')); // and CSP blocks any injected script anyway
    }
    public function test_a_page_can_show_different_forms_and_the_same_form_more_than_once(): void
    {
        $h = $this->owner();
        $a = $this->postJson('/api/forms', ['name' => 'A', 'title' => 'Form A title'], $h)->json('id');
        $b = $this->postJson('/api/forms', ['name' => 'B', 'title' => 'Form B title', 'style' => ['accent' => '#ff0000']], $h)->json('id');
        $p = $this->page($h, "<html><head></head><body><!--fc-form:$a-->|<!--fc-form:$b-->|<!--fc-form:$a-->|<!--fc-form:0--></body></html>");

        $html = $this->get('/p/'.$p['slug'])->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'Form A title'));
        $this->assertSame(1, substr_count($html, 'Form B title'));
        $this->assertSame(3, substr_count($html, '<form '));
        foreach (['fc-form"', 'fc-form-2"', 'fc-form-3"'] as $id) {
            $this->assertSame(1, substr_count($html, 'id="'.$id), $id.' must be unique');
        }
        $this->assertStringContainsString('.fcs-2 .fc-form button{', $html); // each form's colours stay inside its own block
        $this->assertSame(1, Form::withoutGlobalScopes()->find($a)->views);
        $this->assertStringNotContainsString('<!--fc-form', $html);
    }
}
