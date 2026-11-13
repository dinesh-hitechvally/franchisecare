<?php

namespace App\Services\Push;

use App\Models\Company;
use App\Models\PushDevice;
use App\Services\PushSettings;

/** Delivers one notification to one device through the right service: browsers by Web Push, Android / iOS apps through Firebase. */
class PushManager
{
    /** The service that carries a message to this device now: webpush, fcm, or "log" (Log only: nothing is sent). */
    public static function service(Company $company, ?string $platform): string
    {
        if (PushSettings::mode($company) === 'log') {
            return 'log';
        }

        return $platform === 'web' ? 'webpush' : 'fcm';
    }

    /**
     * Sends the notification. Returns the service's reference when it gives one. Throws PushException; when ->gone is set the device
     * is marked revoked so nobody sends to it again.
     *
     * @param  array{title: string, body: string, url?: ?string, image?: ?string}  $n  url is what a tap opens (already the click-tracking link)
     */
    public static function send(Company $company, PushDevice $device, array $n): ?string
    {
        if (self::service($company, $device->platform) === 'log') {
            return null;
        }

        try {
            if ($device->platform === 'web') {
                [$public, $private] = PushSettings::webKeys($company);

                return WebPush::send(
                    ['endpoint' => $device->token, 'keys' => (array) $device->keys],
                    array_filter(['title' => $n['title'], 'body' => $n['body'], 'url' => $n['url'] ?? null, 'image' => $n['image'] ?? null], fn ($v) => $v !== null && $v !== ''),
                    $public, $private, 'mailto:'.($company->from_email ?: ($company->reply_to ?: 'push@example.com')),
                );
            }

            $account = PushSettings::fcm($company) ?? throw new PushException('Connect Firebase (Settings → Push notifications) to send to Android and iOS apps.');

            return Fcm::send($account, $device->token, $n['title'], $n['body'], $n['url'] ?? null, $n['image'] ?? null);
        } catch (PushException $e) {
            if ($e->gone) {
                $device->forceFill(['status' => 'revoked'])->save();
            }

            throw $e;
        }
    }
}
