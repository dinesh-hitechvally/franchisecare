<?php

namespace App\Services\Push;

use App\Models\Company;
use App\Models\Person;
use App\Models\PushDevice;
use Illuminate\Support\Str;

/** Registering the browsers and apps that can receive push, and linking each to the person it belongs to. */
class Devices
{
    /** A short readable name for a browser, from its user-agent: "Chrome on Windows". */
    public static function label(?string $userAgent): ?string
    {
        $ua = (string) $userAgent;
        if ($ua === '') {
            return null;
        }
        $browser = match (true) {
            (bool) preg_match('~Edg(e|A|iOS)?/~', $ua) => 'Edge',
            (bool) preg_match('~OPR/|Opera~', $ua) => 'Opera',
            (bool) preg_match('~Firefox/|FxiOS~', $ua) => 'Firefox',
            (bool) preg_match('~Chrome/|CriOS~', $ua) => 'Chrome',
            (bool) preg_match('~Safari/~', $ua) => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            (bool) preg_match('~Android~', $ua) => 'Android',
            (bool) preg_match('~iPhone|iPad|iPod~', $ua) => 'iOS',
            (bool) preg_match('~Windows~', $ua) => 'Windows',
            (bool) preg_match('~Mac OS X|Macintosh~', $ua) => 'Mac',
            (bool) preg_match('~Linux|X11~', $ua) => 'Linux',
            default => null,
        };

        return $browser.($os ? ' on '.$os : '');
    }

    /** The id a browser or app made for itself: letters, digits and dashes only. */
    public static function cleanUid(?string $uid): ?string
    {
        $uid = trim((string) $uid);

        return preg_match('/^[A-Za-z0-9_-]{8,64}$/', $uid) ? $uid : null;
    }

    /** A page address without its #fragment, only http(s), at most 1000 characters. */
    public static function cleanUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || ! preg_match('~^https?://~i', $url)) {
            return null;
        }

        return mb_substr(explode('#', $url)[0], 0, 1000);
    }

    /** The person an email, phone number or id refers to in this company, or null. */
    public static function person(Company $company, array $who): ?Person
    {
        $query = Person::withoutGlobalScope('company')->where('company_id', $company->id);
        if (! empty($who['person_id'])) {
            return $query->whereKey((int) $who['person_id'])->first();
        }
        if (! empty($who['email'])) {
            return $query->where('email', Str::lower(trim((string) $who['email'])))->first();
        }
        if (! empty($who['phone'])) {
            return $query->where('phone', trim((string) $who['phone']))->first();
        }

        return null;
    }

    /**
     * Adds a device, or refreshes it when the same token is already known (a device that had turned notifications off becomes active again).
     * A known person is never replaced by "nobody".
     *
     * @param  array{email?: ?string, phone?: ?string, person_id?: ?int}  $who
     */
    public static function register(Company $company, string $platform, string $token, ?array $keys, ?string $label, array $who = [], ?string $pageUrl = null, ?string $uid = null): PushDevice
    {
        if ($platform === 'web') {
            WebPush::assertEndpoint($token); // a browser subscription points at its push service: nothing else is accepted
            $public = WebPush::unb64((string) ($keys['p256dh'] ?? ''));
            if (strlen($public) !== 65 || ord($public[0]) !== 4 || strlen(WebPush::unb64((string) ($keys['auth'] ?? ''))) < 16) {
                throw new PushException('The browser subscription has invalid keys.');
            }
        }
        $person = self::person($company, $who);

        // One row per browser or app installation. A browser that turned notifications off and on again has a NEW push address, but the same id of its own:
        // it is found by that id and keeps its row (the new address replaces the old one, and the history shows every change).
        $uid = self::cleanUid($uid);
        $hash = PushDevice::hashOf($token);
        $base = fn () => PushDevice::withoutGlobalScope('company')->where('company_id', $company->id);
        $byUid = $uid ? $base()->where('device_uid', $uid)->first() : null;
        $byToken = $base()->where('token_hash', $hash)->first();
        if ($byUid && $byToken && $byUid->id !== $byToken->id) {
            $byToken->delete(); // the same browser, registered earlier under this address before it had an id: one row, not two
            $byToken = null;
        }
        $device = $byUid ?? $byToken ?? new PushDevice;
        $device->forceFill([
            'company_id' => $company->id, 'platform' => $platform, 'token_hash' => $hash, 'device_uid' => $uid ?: $device->device_uid, 'token' => $token, 'keys' => $platform === 'web' ? ['p256dh' => $keys['p256dh'], 'auth' => $keys['auth']] : null,
            'label' => $label ? mb_substr($label, 0, 120) : ($device->label ?: ucfirst($platform)), 'status' => 'active', 'last_seen_at' => now(),
            'person_id' => $person?->id ?? $device->person_id,
            'page_url' => $device->page_url ?: self::cleanUrl($pageUrl), // where it was first turned on: a later visit to another page does not replace it
        ])->save();

        return $device;
    }
}
