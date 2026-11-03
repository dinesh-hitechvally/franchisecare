<?php

namespace Tests\Feature;

use App\Http\Controllers\ConfigController;
use App\Models\Company;
use App\Services\CompanyMailer;
use App\Services\MailSettings;
use App\Models\Person;
use App\Services\MessageRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $email = 'o@x.com'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Olivia', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_company_profile_keeps_phone_website_and_footer_and_they_become_merge_tags(): void
    {
        $h = $this->owner();
        $res = $this->putJson('/api/settings', [
            'name' => 'Acme Co', 'address' => '1 Main St',
            'profile' => ['phone' => '+61 2 1234 5678', 'website' => 'https://acme.test'],
        ], $h)->assertOk();
        $this->assertSame('https://acme.test', $res->json('profile.website'));
        $this->getJson('/api/settings', $h)->assertJsonPath('profile.phone', '+61 2 1234 5678');
        $this->putJson('/api/settings', ['name' => 'Acme Co', 'profile' => ['website' => 'javascript:alert(1)']], $h)->assertStatus(422);

        $company = \App\Models\Company::first();
        $vars = (new MessageRenderer)->vars($company, new Person(['first_name' => 'Ann']), 'email');
        $this->assertSame('+61 2 1234 5678', $vars['company_phone']);
        $this->assertSame('https://acme.test', $vars['company_website']);
        [, $html] = (new MessageRenderer)->renderEmail('s', '<p>Hi</p>', $company, new Person(['first_name' => 'Ann', 'email' => 'a@x.com']));
            }

    public function test_a_person_can_update_their_own_account_but_needs_the_password_to_change_how_they_sign_in(): void
    {
        $h = $this->owner();

        $this->putJson('/api/account', ['name' => 'Olivia B', 'email' => 'o@x.com'], $h)->assertOk()->assertJsonPath('user.name', 'Olivia B');

        $this->putJson('/api/account', ['name' => 'Olivia B', 'email' => 'new@x.com'], $h)->assertStatus(422)->assertJsonValidationErrors('current_password');
        $this->putJson('/api/account', ['name' => 'Olivia B', 'email' => 'new@x.com', 'current_password' => 'wrong-pass'], $h)->assertStatus(422);
        $this->putJson('/api/account', ['name' => 'Olivia B', 'email' => 'new@x.com', 'current_password' => 'password123'], $h)->assertOk()->assertJsonPath('user.email', 'new@x.com');

        $this->putJson('/api/account', ['name' => 'Olivia B', 'email' => 'new@x.com', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass', 'current_password' => 'password123'], $h)->assertOk();
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => 'new@x.com', 'password' => 'brand-new-pass'])->assertOk();
        $this->postJson('/api/login', ['email' => 'new@x.com', 'password' => 'password123'])->assertStatus(422);
    }

    public function test_an_email_already_used_by_someone_else_is_refused(): void
    {
        $this->owner('first@x.com');
        $h = $this->owner('second@x.com');
        $this->putJson('/api/account', ['name' => 'Two', 'email' => 'first@x.com', 'current_password' => 'password123'], $h)->assertStatus(422)->assertJsonValidationErrors('email');
    }
    public function test_full_company_profile_feeds_the_email_footer_address_and_the_account_keeps_phone_and_title(): void
    {
        $h = $this->owner();
        $this->putJson('/api/settings', ['name' => 'Acme', 'address' => '1 Main St', 'profile' => [
            'legal_name' => 'Acme Pty Ltd', 'tax_id' => 'ABN 12 345', 'industry' => 'Retail', 'support_email' => 'hello@acme.test',
            'city' => 'Sydney', 'state' => 'NSW', 'postcode' => '2000', 'country' => 'Australia',
        ]], $h)->assertOk()->assertJsonPath('profile.city', 'Sydney');
        $this->putJson('/api/settings', ['name' => 'Acme', 'profile' => ['support_email' => 'not-an-email']], $h)->assertStatus(422);

        $company = Company::first();
        [, $html] = (new MessageRenderer)->renderEmail('s', '<p>Hi</p>', $company, new Person(['email' => 'a@x.com']));
        foreach (['1 Main St, Sydney NSW 2000, Australia', 'Acme Pty Ltd', 'ABN 12 345'] as $bit) {
            $this->assertStringContainsString($bit, $html);
        }
        $this->assertSame('hello@acme.test', (new MessageRenderer)->vars($company, null, 'email')['company_email']);

        $this->putJson('/api/account', ['name' => 'Olivia', 'email' => 'o@x.com', 'phone' => '+61 400 000 000', 'job_title' => 'Owner'], $h)
            ->assertOk()->assertJsonPath('user.job_title', 'Owner')->assertJsonPath('user.phone', '+61 400 000 000');
        $this->getJson('/api/me', $h)->assertJsonPath('user.phone', '+61 400 000 000');
    }
    public function test_every_service_can_be_connected_but_only_one_is_in_use(): void
    {
        $h = $this->owner();
        $login = ['host' => '8.8.8.8', 'port' => 587, 'encryption' => 'tls', 'username' => 'u', 'password' => 'p'];

        $this->getJson('/api/config/email', $h)->assertJsonPath('active', 'default')->assertJsonPath('connections.sendgrid.connected', false);
        $this->putJson('/api/config/email/connections/sendgrid', $login, $h)->assertOk()->assertJsonPath('connections.sendgrid.connected', true)->assertJsonPath('active', 'default'); // saving does not switch
        $this->putJson('/api/config/email/connections/gmail', $login + ['activate' => true], $h)->assertOk()->assertJsonPath('active', 'gmail');

        // both stay connected; choosing one replaces the other as the one in use
        $r = $this->putJson('/api/config/email', ['active' => 'sendgrid'], $h)->assertOk()->assertJsonPath('active', 'sendgrid');
        $r->assertJsonPath('connections.gmail.connected', true)->assertJsonPath('connections.sendgrid.connected', true);
        $this->assertStringNotContainsString('"password":"p"', $r->getContent());

        // only a connected service can be chosen, and only the known ones exist
        $this->putJson('/api/config/email', ['active' => 'mailgun'], $h)->assertStatus(422);
        $this->putJson('/api/config/email', ['active' => 'bogus'], $h)->assertStatus(422)->assertJsonValidationErrors('active');

        // sending really uses the active one
        $company = Company::first();
        $this->assertSame('sendgrid', MailSettings::activeKey($company));
        CompanyMailer::for($company);
        $this->assertSame('8.8.8.8', collect(config('mail.mailers'))->first(fn ($m) => str_starts_with((string) ($m['host'] ?? ''), '8.8'))['host']);

        // disconnecting the one in use falls back to the default; the others stay connected
        $this->deleteJson('/api/config/email/connections/sendgrid', [], $h)->assertOk()->assertJsonPath('active', 'default')->assertJsonPath('connections.gmail.connected', true);
        $this->deleteJson('/api/config/email/connections/nope', [], $h)->assertNotFound();
    }

    public function test_a_company_saved_with_the_old_single_server_still_works(): void
    {
        $h = $this->owner();
        $company = Company::first();
        $company->update(['settings' => ['email' => ['provider' => 'smtp', 'preset' => 'sendgrid', 'host' => 'smtp.sendgrid.net', 'port' => 587, 'username' => 'apikey', 'password' => 'k', 'encryption' => 'tls']]]);

        $this->getJson('/api/config/email', $h)->assertJsonPath('active', 'sendgrid')->assertJsonPath('connections.sendgrid.connected', true)->assertJsonPath('connections.sendgrid.username', 'apikey');
        // the first change converts it to the new shape without losing the login
        $this->putJson('/api/config/email/connections/gmail', ['host' => '8.8.8.8', 'username' => 'g', 'password' => 'p'], $h)->assertOk();
        $this->assertSame('k', MailSettings::connections(Company::first())['sendgrid']['password']);
        $this->assertSame('sendgrid', MailSettings::activeKey(Company::first()));
    }

    public function test_smtp_service_is_suggested_from_the_from_address(): void
    {
        $h = $this->owner();
        $this->getJson('/api/config/email/suggest?email=me@gmail.com', $h)->assertOk()->assertJsonPath('preset', 'gmail');
        $this->getJson('/api/config/email/suggest?email=me@HOTMAIL.com', $h)->assertOk()->assertJsonPath('preset', 'outlook');
        $this->getJson('/api/config/email/suggest?email=me@yahoo.com', $h)->assertOk()->assertJsonPath('preset', 'yahoo');
        $this->getJson('/api/config/email/suggest?email=nonsense', $h)->assertStatus(422);
    }
    public function test_a_failed_test_email_explains_what_to_do(): void
    {
        $h = $this->owner();
        $method = new \ReflectionMethod(ConfigController::class, 'explainMailError');
        $explain = fn (string $err, array $cfg = []) => $method->invoke(new ConfigController, $err, $cfg);

        $gmail = $explain('Failed to authenticate on SMTP server with username "x@gmail.com" ... code "535", with message "535-5.7.8 Username and Password not accepted."', ['host' => 'smtp.gmail.com']);
        $this->assertStringContainsString('App password', $gmail);
        $this->assertStringContainsString('rejected the username or password', $explain('Expected response code "235" but got code "535"', ['host' => 'smtp.example.com']));
        $this->assertStringContainsString('Could not reach the mail server', $explain('Connection could not be established with host "smtp.example.com:587"'));
        $this->assertNull($explain('Something unrelated'));

        // the server's own mail program (Default): Mailpit not running, or the program failing
        config(['mail.server_command' => 'D:/laragon/bin/mailpit/1.22.3/mailpit.exe sendmail']);
        $this->assertStringContainsString('Mailpit is not running', $explain('Process failed with exit code 1: dial tcp [::1]:1025: connectex: actively refused'));
        config(['mail.server_command' => '/usr/sbin/sendmail -t -i']);
        $this->assertStringContainsString("server's own mail program", $explain('Process failed with exit code 75: could not deliver'));
    }
}
