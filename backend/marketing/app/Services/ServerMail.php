<?php

namespace App\Services;

/**
 * "Default" mail: the server sends the message itself with its own mail program (PHP's sendmail_path, e.g. Postfix/Exim on a
 * Linux host, or Mailpit on Laragon), not through an SMTP login. MAIL_SENDMAIL_PATH in .env overrides it.
 */
class ServerMail
{
    /** The command that hands a message to the server's mail system, with the flags Symfony's sendmail transport needs. */
    public static function command(): string
    {
        $c = trim((string) config('mail.server_command'));
        if ($c === '') {
            return '';
        }

        return str_contains($c, ' -bs') || str_contains($c, ' -t') ? $c : $c.' -t -i';
    }

    /** The program itself (first word of the command; quotes allowed). */
    public static function binary(): string
    {
        $c = self::command();
        if ($c === '') {
            return '';
        }
        if ($c[0] === '"' || $c[0] === "'") {
            return (string) strtok(substr($c, 1), $c[0]);
        }

        return (string) strtok($c, ' ');
    }

    /** Can the program be found? A bare name (e.g. "sendmail") is looked up on the PATH when it runs, so it is assumed to exist. */
    public static function ready(): bool
    {
        $b = self::binary();
        if ($b === '') {
            return false;
        }

        return ! str_contains($b, '/') && ! str_contains($b, '\\') ? true : is_file($b);
    }

    public static function name(): string
    {
        return basename(str_replace('\\', '/', self::binary())) ?: 'sendmail';
    }

    /** Mailpit only catches mail for you to read on this computer; it never reaches real inboxes. */
    public static function isCatcher(): bool
    {
        return stripos(self::binary(), 'mailpit') !== false || stripos(self::binary(), 'mailhog') !== false;
    }
}
