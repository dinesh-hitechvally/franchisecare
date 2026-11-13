<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PushDevice;
use App\Services\Push\WebPush;
use Illuminate\Support\Str;

/**
 * How a company sends push notifications. Stored under company settings `push` (the whole settings blob is encrypted):
 *   mode: "log" (default: nothing leaves the server, like Log only for email/SMS) | "live"
 *   web: { public, private }   VAPID keys for browsers, created on first use
 *   fcm: { project_id, client_email, private_key }   Firebase service account for Android / iOS apps (optional)
 */
class PushSettings
{
    protected static function all(Company $company): array
    {
        return (array) ($company->setting('push', []) ?: []);
    }

    protected static function save(Company $company, array $push): void
    {
        $settings = $company->settings ?? [];
        $settings['push'] = $push;
        $company->forceFill(['settings' => $settings])->save();
    }

    public static function mode(Company $company): string
    {
        return (self::all($company)['mode'] ?? 'log') === 'live' ? 'live' : 'log';
    }

    public static function setMode(Company $company, string $mode): void
    {
        self::save($company, ['mode' => $mode === 'live' ? 'live' : 'log'] + self::all($company));
    }

    /** The public id used by the website snippet and the mobile registration (a column, so it can be looked up); created on first use. */
    public static function siteKey(Company $company): string
    {
        if (! $company->push_key) {
            $company->forceFill(['push_key' => Str::random(40)])->save();
        }

        return $company->push_key;
    }

    /** @return array{0: string, 1: string} VAPID [public key, private key PEM]; created on first use */
    public static function webKeys(Company $company, bool $rotate = false): array
    {
        $push = self::all($company);
        if ($rotate || empty($push['web']['public']) || empty($push['web']['private'])) {
            [$public, $private] = WebPush::generateKeys();
            $push['web'] = ['public' => $public, 'private' => $private];
            self::save($company, $push);
            if ($rotate) { // browsers subscribed with the old key cannot receive messages signed with the new one
                PushDevice::withoutGlobalScope('company')->where('company_id', $company->id)->where('platform', 'WEB')->where('status', 'ACTIVE')->get()->each(fn (PushDevice $d) => $d->update(['status' => 'revoked'])); // one by one, so each is written to the device history
            }
        }

        return [$push['web']['public'], $push['web']['private']];
    }

    /** The Firebase service account, or null. */
    public static function fcm(Company $company): ?array
    {
        $f = self::all($company)['fcm'] ?? null;

        return $f && ! empty($f['private_key']) ? $f : null;
    }

    public static function setFcm(Company $company, ?array $account): void
    {
        $push = self::all($company);
        $account ? $push['fcm'] = $account : $push = array_diff_key($push, ['fcm' => 1]);
        self::save($company, $push);
    }

    /** The company a site key belongs to. */
    public static function companyForKey(?string $key): ?Company
    {
        if (! $key || strlen($key) !== 40) {
            return null;
        }

        return Company::where('push_key', $key)->first();
    }

    /** What the dashboard may see: never a private key or token. */
    public static function payload(Company $company): array
    {
        $fcm = self::fcm($company);
        $count = fn (?string $platform = null) => PushDevice::withoutGlobalScope('company')->where('company_id', $company->id)->where('status', 'ACTIVE')->when($platform, fn ($q) => $q->where('platform', strtoupper($platform)))->count();

        return [
            'mode' => self::mode($company), 'site_key' => self::siteKey($company), 'public_key' => self::webKeys($company)[0],
            'fcm' => $fcm ? ['connected' => true, 'project_id' => $fcm['project_id'], 'client_email' => $fcm['client_email']] : ['connected' => false],
            'devices' => ['total' => $count(), 'web' => $count('web'), 'android' => $count('android'), 'ios' => $count('ios')],
        ];
    }
}
