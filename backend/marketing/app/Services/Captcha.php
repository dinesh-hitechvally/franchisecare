<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * A small built-in human check for hosted forms: "7 + 4 = ?". No outside service and no keys.
 *
 * The answer travels inside an encrypted, expiring token that the page sends back with the visitor's answer, so nothing is
 * stored on our side until it is used. A token allows a few wrong tries and works once.
 */
class Captcha
{
    public const TTL = 21600; // seconds a challenge stays valid (6 hours)

    public const MAX_WRONG = 5;

    /** @return array{question: string, token: string} */
    public static function challenge(): array
    {
        if (random_int(0, 1) === 0) {
            $a = random_int(1, 9);
            $b = random_int(1, 9);
            $question = "$a + $b";
            $answer = $a + $b;
        } else {
            $a = random_int(5, 15);
            $b = random_int(1, 4);
            $question = "$a − $b";
            $answer = $a - $b;
        }

        return [
            'question' => $question.' = ?',
            'token' => Crypt::encryptString(json_encode(['a' => $answer, 'exp' => time() + self::TTL, 'n' => bin2hex(random_bytes(6))])),
        ];
    }

    /** null when the answer is right, otherwise what to tell the visitor. A wrong answer counts against the token. */
    public static function check(?string $token, $answer): ?string
    {
        $expired = 'The check has expired. Please reload the page and try again.';
        $data = null;
        try {
            $data = json_decode(Crypt::decryptString((string) $token), true);
        } catch (\Throwable $e) {
            return $expired;
        }
        if (! is_array($data) || ($data['exp'] ?? 0) < time() || Cache::has(self::usedKey($token)) || (int) Cache::get(self::wrongKey($token), 0) >= self::MAX_WRONG) {
            return $expired;
        }
        if (! is_numeric(trim((string) $answer)) || (int) trim((string) $answer) !== (int) $data['a']) {
            Cache::add(self::wrongKey($token), 0, self::TTL);
            Cache::increment(self::wrongKey($token));

            return 'That answer is not right. Please try again.';
        }

        return null;
    }

    /** The form was accepted: this token cannot be used again. */
    public static function consume(?string $token): void
    {
        if ($token) {
            Cache::put(self::usedKey($token), 1, self::TTL);
        }
    }

    protected static function usedKey(string $token): string
    {
        return 'captcha-used:'.sha1($token);
    }

    protected static function wrongKey(string $token): string
    {
        return 'captcha-wrong:'.sha1($token);
    }
}
