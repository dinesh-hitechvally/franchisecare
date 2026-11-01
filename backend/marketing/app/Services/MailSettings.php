<?php

namespace App\Services;

use App\Models\Company;

/**
 * How a company sends email. Every service can be connected at once, each with its own saved login, but only ONE is
 * in use for sending (`active`; "default" = the platform's own mail server).
 *
 * Stored in company settings under `email`:
 *   active:      "default" | a connection key
 *   connections: { gmail: {host, port, encryption, username, password}, sendgrid: {...}, custom: {...}, google: {email, refresh_token}, ... }
 * Companies saved before this existed have one flat server (provider/host/…/preset/oauth); it is read as a single connection.
 */
class MailSettings
{
    /** SMTP services that take a server + login. "custom" is any other server. */
    public const SMTP = ['gmail', 'outlook', 'yahoo', 'zoho', 'sendgrid', 'mailgun', 'ses', 'brevo', 'postmark', 'mandrill', 'custom'];

    public static function keys(): array
    {
        return array_merge(self::SMTP, MailOAuth::PROVIDERS);
    }

    /** @return array<string, array> connection key => its saved details */
    public static function connections(Company $company): array
    {
        $e = $company->setting('email', []) ?: [];
        if (isset($e['connections']) && is_array($e['connections'])) {
            return $e['connections'];
        }

        // legacy single configuration
        $out = [];
        if (! empty($e['host'])) {
            $key = in_array($e['preset'] ?? 'custom', self::SMTP, true) ? ($e['preset'] ?? 'custom') : 'custom';
            $out[$key] = ['host' => $e['host'], 'port' => $e['port'] ?? 587, 'encryption' => $e['encryption'] ?? 'tls', 'username' => $e['username'] ?? null, 'password' => $e['password'] ?? null];
        }
        if (! empty($e['oauth']['refresh_token']) && in_array($e['oauth']['provider'] ?? '', MailOAuth::PROVIDERS, true)) {
            $out[$e['oauth']['provider']] = ['email' => $e['oauth']['email'] ?? null, 'refresh_token' => $e['oauth']['refresh_token']];
        }

        return $out;
    }

    /** The key of the service in use, or "default". */
    public static function activeKey(Company $company): string
    {
        $e = $company->setting('email', []) ?: [];
        $conns = self::connections($company);

        $key = $e['active'] ?? null;
        if ($key === null) { // legacy: provider + preset
            $p = $e['provider'] ?? 'default';
            $key = match (true) {
                $p === 'smtp' => in_array($e['preset'] ?? 'custom', self::SMTP, true) ? ($e['preset'] ?? 'custom') : 'custom',
                in_array($p, MailOAuth::PROVIDERS, true) => $p,
                default => 'default',
            };
        }

        return $key !== 'default' && isset($conns[$key]) ? $key : 'default';
    }

    /** The connection to send with: [key, type (smtp|oauth), conn], or null for the platform default. */
    public static function active(Company $company): ?array
    {
        $key = self::activeKey($company);
        if ($key === 'default') {
            return null;
        }

        return ['key' => $key, 'type' => in_array($key, MailOAuth::PROVIDERS, true) ? 'oauth' : 'smtp', 'conn' => self::connections($company)[$key]];
    }

    /**
     * Applies a change to the email settings (always in the new shape; a legacy config is converted on the first write).
     * $change receives [connections, active] and returns the new pair.
     */
    public static function update(Company $company, callable $change, array $columns = []): void
    {
        $settings = $company->settings ?? [];
        [$conns, $active] = $change(self::connections($company), self::activeKey($company));

        $settings['email'] = ['active' => isset($conns[$active]) ? $active : 'default', 'connections' => $conns];
        $company->forceFill(['settings' => $settings] + $columns)->save();
    }

    /** What the browser may see: which services are connected and how, never a password or token. */
    public static function payload(Company $company): array
    {
        $conns = self::connections($company);
        $out = [];
        foreach (self::keys() as $key) {
            $c = $conns[$key] ?? null;
            $out[$key] = $c === null ? ['connected' => false] : [
                'connected' => true,
                'host' => $c['host'] ?? null, 'port' => $c['port'] ?? null, 'encryption' => $c['encryption'] ?? null,
                'username' => $c['username'] ?? null, 'password_set' => ! empty($c['password']),
                'email' => $c['email'] ?? null,
            ];
        }

        return $out;
    }
}
