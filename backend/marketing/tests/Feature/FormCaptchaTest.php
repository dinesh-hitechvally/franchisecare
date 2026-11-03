<?php

namespace Tests\Feature;

use App\Models\FormSubmission;
use App\Services\Captcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class FormCaptchaTest extends TestCase
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

    protected function slug(): string
    {
        $fields = [['type' => 'email', 'label' => 'Email', 'required' => true], ['type' => 'captcha', 'label' => 'Prove you are human']];
        $slug = $this->postJson('/api/forms', ['name' => 'Guarded', 'type' => 'other', 'save_person' => true, 'fields' => $fields], $this->h)->assertCreated()->json('slug');
        $this->app['auth']->forgetGuards();

        return $slug;
    }

    /** The question and token a visitor's browser got, and the right answer to it. */
    protected function challenge(string $slug): array
    {
        $html = $this->get("/f/$slug")->assertOk()->getContent();
        preg_match('/class="fc-q">(\d+) ([+−]) (\d+) = \?</u', $html, $q);
        preg_match('/name="captcha_token" value="([^"]+)"/', $html, $t);
        $this->assertNotEmpty($q, 'the sum is shown');

        return ['token' => html_entity_decode($t[1]), 'answer' => $q[2] === '+' ? $q[1] + $q[3] : $q[1] - $q[3]];
    }

    public function test_the_hosted_form_shows_a_sum_and_only_the_right_answer_gets_through(): void
    {
        $slug = $this->slug();
        $c = $this->challenge($slug);
        $body = ['email' => 'ann@x.com'];

        $this->postJson("/api/public/forms/$slug", $body)->assertStatus(422)->assertJsonPath('errors.captcha.0', 'Please answer the small sum to show you are human.');
        $this->postJson("/api/public/forms/$slug", $body + ['captcha' => $c['answer'] + 1, 'captcha_token' => $c['token']])->assertStatus(422)->assertJsonPath('errors.captcha.0', 'That answer is not right. Please try again.');
        $this->postJson("/api/public/forms/$slug", $body + ['captcha' => (string) $c['answer'], 'captcha_token' => $c['token']])->assertOk();

        // it works once: the same solved sum cannot be replayed
        $this->postJson("/api/public/forms/$slug", ['email' => 'bob@x.com', 'captcha' => (string) $c['answer'], 'captcha_token' => $c['token']])->assertStatus(422)
            ->assertJsonPath('errors.captcha.0', 'The check has expired. Please reload the page and try again.');
    }

    public function test_a_fixed_form_can_be_resent_with_the_same_sum_and_a_token_only_allows_a_few_wrong_tries(): void
    {
        $slug = $this->slug();
        $c = $this->challenge($slug);

        // another field was wrong: the sum was fine, so it stays usable
        $this->postJson("/api/public/forms/$slug", ['email' => 'not-an-email', 'captcha' => (string) $c['answer'], 'captcha_token' => $c['token']])->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson("/api/public/forms/$slug", ['email' => 'ann@x.com', 'captcha' => (string) $c['answer'], 'captcha_token' => $c['token']])->assertOk();

        $d = $this->challenge($slug);
        for ($i = 0; $i < Captcha::MAX_WRONG; $i++) {
            $this->postJson("/api/public/forms/$slug", ['email' => 'x@x.com', 'captcha' => 9999, 'captcha_token' => $d['token']])->assertStatus(422);
        }
        // guessing is over: even the right answer no longer works on that token
        $this->postJson("/api/public/forms/$slug", ['email' => 'x@x.com', 'captcha' => (string) $d['answer'], 'captcha_token' => $d['token']])->assertStatus(422)
            ->assertJsonPath('errors.captcha.0', 'The check has expired. Please reload the page and try again.');
    }

    public function test_tampered_and_expired_tokens_are_refused(): void
    {
        $slug = $this->slug();
        $expired = Crypt::encryptString(json_encode(['a' => 5, 'exp' => time() - 10, 'n' => 'x']));

        foreach (['garbage', $expired, ''] as $token) {
            $this->postJson("/api/public/forms/$slug", ['email' => 'ann@x.com', 'captcha' => 5, 'captcha_token' => $token])->assertStatus(422)->assertJsonValidationErrors('captcha');
        }
        $this->assertSame(0, FormSubmission::withoutGlobalScopes()->count());
    }

    public function test_the_captcha_is_never_kept_with_the_answers_or_on_the_person_and_is_always_required(): void
    {
        $slug = $this->slug();
        $c = $this->challenge($slug);
        $this->postJson("/api/public/forms/$slug", ['email' => 'ann@x.com', 'captcha' => (string) $c['answer'], 'captcha_token' => $c['token']])->assertOk();

        $sub = FormSubmission::withoutGlobalScopes()->first();
        $this->assertSame(['Email'], array_column($sub->data, 'label')); // no captcha line in the inbox

        // saved as optional, it is still required; only one per form
        $form = $this->postJson('/api/forms', ['name' => 'X', 'fields' => [['type' => 'email', 'label' => 'E'], ['type' => 'captcha', 'label' => 'Human?', 'required' => false]]], $this->h)->assertCreated();
        $this->assertTrue($form->json('fields.1.required'));
        $this->assertSame('captcha', $form->json('fields.1.key'));
        $this->postJson('/api/forms', ['name' => 'Y', 'fields' => [['type' => 'captcha', 'label' => 'A'], ['type' => 'captcha', 'label' => 'B']]], $this->h)->assertStatus(422);
    }
}
