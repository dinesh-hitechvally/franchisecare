<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\CompanyMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class MailOAuthTest extends TestCase
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

    protected function configureGoogle(): void
    {
        config(['services.mail_oauth.google.client_id' => 'gid', 'services.mail_oauth.google.client_secret' => 'gsecret']);
    }

    protected function state(int $company, string $provider = 'google', ?int $exp = null): string
    {
        return Crypt::encryptString(json_encode(['company' => $company, 'provider' => $provider, 'exp' => $exp ?? now()->addMinutes(5)->timestamp]));
    }

    public function test_the_connect_link_needs_the_platform_oauth_app(): void
    {
        $h = $this->owner();
        $this->getJson('/api/oauth/mail/google/start', $h)->assertStatus(422);
        $this->getJson('/api/oauth/mail/nope/start', $h)->assertNotFound();

        $this->configureGoogle();
        $url = $this->getJson('/api/oauth/mail/google/start', $h)->assertOk()->json('url');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('gid', $q['client_id']);
        $this->assertSame(url('/api/oauth/mail/google/callback'), $q['redirect_uri']);
        $this->assertStringContainsString('https://mail.google.com/', $q['scope']);
        $this->assertSame('offline', $q['access_type']);
        $this->assertNotEmpty($q['state']);

        $this->getJson('/api/config/email', $h)->assertJsonPath('oauth.google.configured', true)->assertJsonPath('connections.google.connected', false)->assertJsonPath('oauth.microsoft.configured', false);
    }

    public function test_approving_access_stores_the_connection_and_switches_sending_to_it(): void
    {
        $h = $this->owner();
        $this->configureGoogle();
        $company = Company::first();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'at-1', 'refresh_token' => 'rt-1', 'expires_in' => 3600]),
            'openidconnect.googleapis.com/*' => Http::response(['email' => 'boss@gmail.test']),
        ]);

        $this->get('/api/oauth/mail/google/callback?code=abc&state='.urlencode($this->state($company->id)))
            ->assertRedirect(config('services.mail_oauth.frontend_url').'/settings/email?connected=google');

        $payload = $this->getJson('/api/config/email', $h)->assertOk();
        $payload->assertJsonPath('active', 'google')->assertJsonPath('connections.google.connected', true)->assertJsonPath('connections.google.email', 'boss@gmail.test')->assertJsonPath('from_email', 'boss@gmail.test');
        $this->assertStringNotContainsString('rt-1', $payload->getContent()); // the token never goes back to the browser

        // saving other settings keeps the connection
        $this->putJson('/api/config/email', ['active' => 'google', 'from_name' => 'Acme'], $h)->assertOk()->assertJsonPath('connections.google.connected', true);
        // and the next send logs in with a fresh access token
        Cache::flush();
        CompanyMailer::for($company->fresh());
        $cfg = collect(config('mail.mailers'))->first(fn ($m) => ($m['transport'] ?? '') === 'xoauth2');
        $this->assertSame(['smtp.gmail.com', 'boss@gmail.test', 'at-1'], [$cfg['host'], $cfg['username'], $cfg['password']]);
        $this->assertInstanceOf(EsmtpTransport::class, app('mail.manager')->mailer(collect(config('mail.mailers'))->filter(fn ($m) => ($m['transport'] ?? '') === 'xoauth2')->keys()->first())->getSymfonyTransport());

        // disconnect returns to the platform's mail server
        $this->deleteJson('/api/config/email/connections/google', [], $h)->assertOk();
        $this->getJson('/api/config/email', $h)->assertJsonPath('active', 'default')->assertJsonPath('connections.google.connected', false);
    }

    public function test_bad_or_expired_links_and_refusals_come_back_as_errors_not_connections(): void
    {
        $h = $this->owner();
        $this->configureGoogle();
        $id = Company::first()->id;

        $this->get('/api/oauth/mail/google/callback?code=abc&state=garbage')->assertRedirectContains('oauth_error=');
        $this->get('/api/oauth/mail/google/callback?code=abc&state='.urlencode($this->state($id, 'google', now()->subMinute()->timestamp)))->assertRedirectContains('oauth_error=');
        $this->get('/api/oauth/mail/google/callback?error=access_denied&state='.urlencode($this->state($id)))->assertRedirectContains('oauth_error=');

        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant', 'error_description' => 'Bad code'], 400)]);
        $this->get('/api/oauth/mail/google/callback?code=abc&state='.urlencode($this->state($id)))->assertRedirectContains('oauth_error=Bad');

        $this->getJson('/api/config/email', $h)->assertJsonPath('connections.google.connected', false)->assertJsonPath('active', 'default');
        $this->putJson('/api/config/email', ['active' => 'google'], $h)->assertStatus(422); // can't pick it without connecting
    }
}
