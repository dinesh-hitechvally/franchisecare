<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * "Sign in with Google / Microsoft" for sending email: the company approves access once, we keep a refresh token
 * (encrypted, inside the company settings) and log in to the provider's SMTP server with a short-lived access token (XOAUTH2).
 * The platform needs one OAuth app per provider; without its client id/secret the provider is simply shown as unavailable.
 */
class MailOAuth
{
    public const PROVIDERS = ['google', 'microsoft'];

    public static function configured(string $provider): bool
    {
        return filled(config("services.mail_oauth.$provider.client_id")) && filled(config("services.mail_oauth.$provider.client_secret"));
    }

    public static function redirectUri(string $provider): string
    {
        return url("/api/oauth/mail/$provider/callback");
    }

    /** Provider-specific endpoints, scopes and SMTP host. */
    public static function spec(string $provider): array
    {
        $tenant = config('services.mail_oauth.microsoft.tenant', 'common');

        return match ($provider) {
            'google' => [
                'authorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token' => 'https://oauth2.googleapis.com/token',
                'scope' => 'openid email https://mail.google.com/',
                'extra' => ['access_type' => 'offline', 'prompt' => 'consent'],
                'smtp_host' => 'smtp.gmail.com',
            ],
            'microsoft' => [
                'authorize' => "https://login.microsoftonline.com/$tenant/oauth2/v2.0/authorize",
                'token' => "https://login.microsoftonline.com/$tenant/oauth2/v2.0/token",
                'scope' => 'openid email offline_access https://outlook.office.com/SMTP.Send',
                'extra' => ['prompt' => 'select_account'],
                'smtp_host' => 'smtp.office365.com',
            ],
        };
    }

    public static function authorizeUrl(string $provider, string $state): string
    {
        $spec = self::spec($provider);

        return $spec['authorize'].'?'.http_build_query([
            'client_id' => config("services.mail_oauth.$provider.client_id"),
            'redirect_uri' => self::redirectUri($provider),
            'response_type' => 'code',
            'scope' => $spec['scope'],
            'state' => $state,
        ] + $spec['extra']);
    }

    /** Trades the one-time code for tokens and works out which mailbox was connected. @return array{email:string, refresh_token:string} */
    public static function exchange(string $provider, string $code): array
    {
        $res = Http::asForm()->timeout(15)->post(self::spec($provider)['token'], [
            'client_id' => config("services.mail_oauth.$provider.client_id"),
            'client_secret' => config("services.mail_oauth.$provider.client_secret"),
            'code' => $code,
            'redirect_uri' => self::redirectUri($provider),
            'grant_type' => 'authorization_code',
        ]);
        $j = $res->json() ?? [];
        if (! $res->successful() || empty($j['refresh_token'])) {
            throw new RuntimeException($j['error_description'] ?? $j['error'] ?? 'The provider did not return the access we need. Try again and approve every permission.');
        }

        $email = self::emailFrom($provider, $j);
        if (! $email) {
            throw new RuntimeException('Could not read the email address of the account you connected.');
        }

        return ['email' => $email, 'refresh_token' => $j['refresh_token']];
    }

    protected static function emailFrom(string $provider, array $tokens): ?string
    {
        if ($provider === 'google' && ! empty($tokens['access_token'])) {
            $me = Http::withToken($tokens['access_token'])->timeout(15)->get('https://openidconnect.googleapis.com/v1/userinfo')->json();

            return $me['email'] ?? null;
        }
        // Microsoft (and a fallback for Google): the email is a claim inside the id_token
        $parts = explode('.', (string) ($tokens['id_token'] ?? ''));
        $claims = isset($parts[1]) ? json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) : null;

        return $claims['email'] ?? $claims['preferred_username'] ?? null;
    }

    /** A valid access token for the company's connected mailbox (cached until shortly before it expires). */
    public static function accessToken(Company $company, string $provider): string
    {
        $oauth = MailSettings::connections($company)[$provider] ?? [];
        if (empty($oauth['refresh_token'])) {
            throw new RuntimeException('Email is not connected. Connect your account in Settings → Email sending.');
        }

        return Cache::remember("mail-oauth:{$company->id}:".md5($oauth['refresh_token']), 3000, function () use ($provider, $oauth) {
            $res = Http::asForm()->timeout(15)->post(self::spec($provider)['token'], [
                'client_id' => config("services.mail_oauth.$provider.client_id"),
                'client_secret' => config("services.mail_oauth.$provider.client_secret"),
                'refresh_token' => $oauth['refresh_token'],
                'grant_type' => 'refresh_token',
            ]);
            $j = $res->json() ?? [];
            if (! $res->successful() || empty($j['access_token'])) {
                throw new RuntimeException('The email connection expired or was revoked. Connect your account again in Settings → Email sending.');
            }

            return $j['access_token'];
        });
    }

    public static function smtpHost(string $provider): string
    {
        return self::spec($provider)['smtp_host'];
    }
}
