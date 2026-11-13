<?php

namespace App\Services\Push;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** Notifications to Android and iOS apps through Firebase Cloud Messaging (HTTP v1), signed in with the company's Firebase service account. */
class Fcm
{
    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token'; // fixed: never taken from the uploaded file

    /** Checks an uploaded service-account file and returns what is kept of it. */
    public static function parse(string $json): array
    {
        $a = json_decode($json, true);
        if (! is_array($a) || ($a['type'] ?? null) !== 'service_account' || empty($a['project_id']) || empty($a['client_email']) || empty($a['private_key'])) {
            throw new PushException('That is not a Firebase service-account file. In Firebase open Project settings → Service accounts → Generate new private key, and paste the whole file.');
        }
        if (! openssl_pkey_get_private($a['private_key'])) {
            throw new PushException('The private key inside that file is not valid.');
        }

        return ['project_id' => (string) $a['project_id'], 'client_email' => (string) $a['client_email'], 'private_key' => (string) $a['private_key']];
    }

    protected static function accessToken(array $account): string
    {
        return Cache::remember('fcm_token:'.sha1($account['client_email'].$account['private_key']), 3300, function () use ($account) {
            $now = time();
            $head = WebPush::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = WebPush::b64(json_encode(['iss' => $account['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging', 'aud' => self::TOKEN_URL, 'iat' => $now, 'exp' => $now + 3600]));
            openssl_sign("$head.$claims", $sig, openssl_pkey_get_private($account['private_key']), OPENSSL_ALGO_SHA256);

            try {
                $r = Http::timeout(20)->asForm()->post(self::TOKEN_URL, ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => "$head.$claims.".WebPush::b64($sig)]);
            } catch (ConnectionException) {
                throw new PushException('Could not reach Google to sign in to Firebase. Try again later (temporary).');
            }
            if (! $r->successful() || ! $r->json('access_token')) {
                throw new PushException('Firebase did not accept the service account'.($r->json('error_description') ? ': '.mb_substr((string) $r->json('error_description'), 0, 150) : '').'.');
            }

            return (string) $r->json('access_token');
        });
    }

    /** Checks the service account works (signs in). */
    public static function check(array $account): void
    {
        Cache::forget('fcm_token:'.sha1($account['client_email'].$account['private_key']));
        self::accessToken($account);
    }

    /** Sends one notification to one app installation. Returns Firebase's message name. */
    public static function send(array $account, string $token, string $title, string $body, ?string $url = null, ?string $image = null): string
    {
        $message = ['token' => $token, 'notification' => array_filter(['title' => $title, 'body' => $body, 'image' => $image]), 'data' => array_filter(['url' => $url, 'title' => $title, 'body' => $body])];

        try {
            $r = Http::timeout(20)->withToken(self::accessToken($account))->post('https://fcm.googleapis.com/v1/projects/'.$account['project_id'].'/messages:send', ['message' => $message]);
        } catch (ConnectionException) {
            throw new PushException('Could not reach Firebase. Try again later (temporary).');
        }
        if ($r->successful()) {
            return (string) $r->json('name');
        }

        $status = (string) $r->json('error.status');
        $detail = mb_substr((string) $r->json('error.message'), 0, 200);
        throw match (true) {
            $status === 'NOT_FOUND' || str_contains((string) $r->body(), 'UNREGISTERED') => new PushException('Firebase says this app installation no longer exists (the app was removed or its token changed).', true),
            $status === 'INVALID_ARGUMENT' && str_contains($detail, 'token') => new PushException('Firebase says this device token is not valid.', true),
            $r->status() === 401 => new PushException('Firebase refused the service account. Upload the file again in Settings → Push notifications.'),
            $r->status() === 429 || $r->serverError() => new PushException('Firebase is busy, try again later (temporary, HTTP '.$r->status().').'),
            default => new PushException('Firebase refused the notification: '.($detail ?: 'HTTP '.$r->status())),
        };
    }
}
