<?php

namespace App\Services\Push;

use App\Services\UrlGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Browser push with the open Web Push standard, without any third-party account: the message is encrypted for the browser
 * (RFC 8291, aes128gcm) and the request is signed with the company's own VAPID key (RFC 8292). Only PHP's openssl is needed.
 */
class WebPush
{
    /** The push services browsers use. An endpoint is typed by whoever subscribes, so anything else is refused (it could point at an internal server). */
    protected const SERVICES = '~(^|\.)(googleapis\.com|push\.services\.mozilla\.com|mozaws\.net|push\.apple\.com|notify\.windows\.com)$~i';

    public static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    public static function unb64(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/').str_repeat('=', (4 - strlen($s) % 4) % 4));
    }

    /** Options for creating EC keys. Some Windows PHP builds have no OpenSSL configuration file and then cannot create any key: a minimal one is used instead. */
    public static function keyOptions(): array
    {
        static $options;
        if ($options !== null) {
            return $options;
        }
        $base = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        $drain = function () {
            while (openssl_error_string() !== false) {
            }
        };
        if (openssl_pkey_new($base)) {
            return $options = $base;
        }
        $drain();
        foreach ([getenv('OPENSSL_CONF') ?: null, dirname(PHP_BINARY).'/extras/ssl/openssl.cnf', dirname(__DIR__, 3).'/config/openssl-minimal.cnf'] as $path) {
            if ($path && is_file($path) && openssl_pkey_new($base + ['config' => $path])) {
                return $options = $base + ['config' => $path];
            }
            $drain();
        }

        return $options = $base;
    }

    /** A new VAPID key pair: [public key (base64url, what the browser needs), private key (PEM)]. */
    public static function generateKeys(): array
    {
        $key = openssl_pkey_new(self::keyOptions());
        if (! $key) {
            throw new PushException('Could not create the push keys (is the PHP openssl extension enabled?).');
        }
        openssl_pkey_export($key, $pem, null, array_intersect_key(self::keyOptions(), ['config' => 1]));

        return [self::b64(self::publicBytes($key)), $pem];
    }

    /** The uncompressed 65-byte public point of an EC key. */
    protected static function publicBytes($key): string
    {
        $ec = openssl_pkey_get_details($key)['ec'];

        return "\x04".str_pad($ec['x'], 32, "\0", STR_PAD_LEFT).str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
    }

    protected static function publicPem(string $raw65): string
    {
        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode(hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$raw65), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    /** A private key from its raw 32-byte value and public point (used for the published test example). */
    public static function privatePem(string $d32, string $pub65): string
    {
        return "-----BEGIN EC PRIVATE KEY-----\n".chunk_split(base64_encode(hex2bin('30770201010420').$d32.hex2bin('a00a06082a8648ce3d030107a144034200').$pub65), 64, "\n")."-----END EC PRIVATE KEY-----\n";
    }

    /**
     * The body of the push request: the payload encrypted for one browser subscription. $asPrivatePem and $salt are only for tests
     * (the standard publishes an example with fixed values); normally both are random.
     */
    public static function encrypt(string $payload, string $p256dh, string $auth, ?string $asPrivatePem = null, ?string $salt = null): string
    {
        $uaPublic = self::unb64($p256dh);
        $authSecret = self::unb64($auth);
        if (strlen($uaPublic) !== 65 || $uaPublic[0] !== "\x04" || strlen($authSecret) < 16) {
            throw new PushException('This browser subscription is not valid.', true);
        }
        if (strlen($payload) > 3900) {
            throw new PushException('The notification is too long to send to a browser.');
        }

        $as = $asPrivatePem ? openssl_pkey_get_private($asPrivatePem) : openssl_pkey_new(self::keyOptions());
        $asPublic = self::publicBytes($as);
        $secret = openssl_pkey_derive(openssl_pkey_get_public(self::publicPem($uaPublic)), $as, 32);
        if ($secret === false) {
            throw new PushException('This browser subscription is not valid.', true);
        }

        $salt ??= random_bytes(16);
        $ikm = hash_hkdf('sha256', $secret, 32, "WebPush: info\0".$uaPublic.$asPublic, $authSecret);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
        $tag = '';
        $cipher = openssl_encrypt($payload."\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);

        return $salt.pack('N', 4096).chr(65).$asPublic.$cipher.$tag;
    }

    /** The Authorization header that proves the request comes from the owner of the VAPID key. */
    public static function vapidHeader(string $endpoint, string $publicKey, string $privatePem, string $subject): string
    {
        $u = parse_url($endpoint);
        $head = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64(json_encode(['aud' => $u['scheme'].'://'.$u['host'].(isset($u['port']) ? ':'.$u['port'] : ''), 'exp' => time() + 43200, 'sub' => $subject]));
        openssl_sign("$head.$claims", $der, openssl_pkey_get_private($privatePem), OPENSSL_ALGO_SHA256);

        return 'vapid t='."$head.$claims.".self::b64(self::derToRaw($der)).', k='.$publicKey;
    }

    /** openssl signs in DER (two integers); JWT wants the two 32-byte numbers joined. */
    protected static function derToRaw(string $der): string
    {
        $pos = 2 + (ord($der[1]) & 0x80 ? ord($der[1]) & 0x7F : 0); // skip the sequence header
        $out = '';
        for ($i = 0; $i < 2; $i++) {
            $len = ord($der[$pos + 1]);
            $int = ltrim(substr($der, $pos + 2, $len), "\0");
            $out .= str_pad($int, 32, "\0", STR_PAD_LEFT);
            $pos += 2 + $len;
        }

        return $out;
    }

    /** Whether this subscription endpoint may be used at all. */
    public static function assertEndpoint(string $endpoint): void
    {
        $host = (string) parse_url($endpoint, PHP_URL_HOST);
        if (! str_starts_with(strtolower($endpoint), 'https://') && ! config('services.outbound.allow_private')) {
            throw new PushException('The push address must use https.', true);
        }
        if (! config('services.outbound.allow_private') && ! preg_match(self::SERVICES, $host)) {
            throw new PushException('That is not a known browser push service.', true);
        }
        try {
            UrlGuard::assertPublic($endpoint);
        } catch (InvalidArgumentException $e) {
            throw new PushException($e->getMessage(), true);
        }
    }

    /**
     * Sends one notification to one browser subscription. Returns the push service's reference (if it gives one).
     *
     * @param  array{endpoint: string, keys: array{p256dh: string, auth: string}}  $subscription
     */
    public static function send(array $subscription, array $payload, string $publicKey, string $privatePem, string $subject, int $ttl = 86400): ?string
    {
        self::assertEndpoint($subscription['endpoint']);
        $body = self::encrypt(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $subscription['keys']['p256dh'] ?? '', $subscription['keys']['auth'] ?? '');

        try {
            $response = Http::timeout(20)->withoutRedirecting()->withHeaders([
                'Authorization' => self::vapidHeader($subscription['endpoint'], $publicKey, $privatePem, $subject),
                'Content-Encoding' => 'aes128gcm', 'TTL' => (string) $ttl, 'Urgency' => 'normal',
            ])->withBody($body, 'application/octet-stream')->post($subscription['endpoint']);
        } catch (ConnectionException) {
            throw new PushException('Could not reach the browser push service. Try again later (temporary).');
        }

        if ($response->successful()) {
            return $response->header('Location') ?: null;
        }

        throw match (true) {
            in_array($response->status(), [404, 410], true) => new PushException('The browser says this subscription no longer exists (the person turned notifications off).', true),
            $response->status() === 429 || $response->serverError() => new PushException('The browser push service is busy, try again later (temporary, HTTP '.$response->status().').'),
            in_array($response->status(), [400, 401, 403], true) => new PushException('The browser push service refused the request (HTTP '.$response->status().'): '.mb_substr(trim(strip_tags((string) $response->body())), 0, 200)),
            default => new PushException('The browser push service answered HTTP '.$response->status().'.'),
        };
    }
}
