<?php

namespace App\Services;

use App\Models\Company;
use App\Services\Sms\SmsProviders;

/**
 * How a company sends SMS. Like email: every service can be connected at once, each with its own saved login, and only
 * one is in use (`active`; "log" = Log only, nothing is sent). Stored under company settings `sms`:
 *   active: "log" | service key,  connections: { twilio: {account_sid, auth_token, from}, vonage: {...}, ... }
 * Companies saved with the older Twilio-only settings (provider / twilio_sid / twilio_token / from) are read as one connection.
 */
class SmsSettings
{
    /** @return array<string, array> service key => saved login */
    public static function connections(Company $company): array
    {
        $s = $company->setting('sms', []) ?: [];
        if (isset($s['connections']) && is_array($s['connections'])) {
            return $s['connections'];
        }
        if (! empty($s['twilio_sid']) || ! empty($s['twilio_token'])) { // legacy
            return ['twilio' => ['account_sid' => $s['twilio_sid'] ?? null, 'auth_token' => $s['twilio_token'] ?? null, 'from' => $s['from'] ?? null]];
        }

        return [];
    }

    public static function activeKey(Company $company): string
    {
        $s = $company->setting('sms', []) ?: [];
        $key = $s['active'] ?? (($s['provider'] ?? 'log') === 'twilio' ? 'twilio' : 'log');

        return $key !== 'log' && isset(self::connections($company)[$key]) ? $key : 'log';
    }

    /** [key, conn] of the service in use, or null for Log only. */
    public static function active(Company $company): ?array
    {
        $key = self::activeKey($company);

        return $key === 'log' ? null : ['key' => $key, 'conn' => self::connections($company)[$key]];
    }

    /** $change receives [connections, active] and returns the new pair. */
    public static function update(Company $company, callable $change): void
    {
        $settings = $company->settings ?? [];
        [$conns, $active] = $change(self::connections($company), self::activeKey($company));

        $settings['sms'] = ['active' => isset($conns[$active]) ? $active : 'log', 'connections' => $conns];
        $company->forceFill(['settings' => $settings])->save();
    }

    /** What the browser may see: which services are connected and their non-secret fields; secrets only as "set". */
    public static function payload(Company $company): array
    {
        $conns = self::connections($company);
        $out = [];
        foreach (SmsProviders::SPECS as $key => $spec) {
            $c = $conns[$key] ?? null;
            if ($c === null) {
                $out[$key] = ['connected' => false];

                continue;
            }
            $row = ['connected' => true];
            foreach ($spec['fields'] as $f) {
                if (! empty($f['secret'])) {
                    $row[$f['key'].'_set'] = ! empty($c[$f['key']]);
                } else {
                    $row[$f['key']] = $c[$f['key']] ?? null;
                }
            }
            $out[$key] = $row;
        }

        return $out;
    }
}
