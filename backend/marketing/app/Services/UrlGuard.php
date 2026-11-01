<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * SSRF guard for user-supplied targets (webhook URLs, SMTP hosts):
 * never a private, loopback or link-local address unless OUTBOUND_ALLOW_PRIVATE=true (local development).
 */
class UrlGuard
{
    public static function assertPublic(string $url): void
    {
        $parts = parse_url($url);
        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            throw new InvalidArgumentException('URL must start with http:// or https://');
        }

        self::assertPublicHost($parts['host']);
    }

    public static function assertPublicHost(string $host): void
    {
        if (config('services.outbound.allow_private')) {
            return;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (! $ips) {
            throw new InvalidArgumentException('Could not resolve the host name.');
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('Private or local addresses are not allowed.');
            }
        }
    }
}
