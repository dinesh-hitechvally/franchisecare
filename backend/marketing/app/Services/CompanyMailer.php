<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/** Returns the mailer a company should send with: the one service it has switched on (Google/Microsoft sign-in, an SMTP server) or the app default. */
class CompanyMailer
{
    public static function for(Company $company): Mailer
    {
        $active = MailSettings::active($company);
        if (! $active) {
            // Default = the server sends it itself. A real platform mailer chosen in .env is used as it is; when .env only has
            // the "log" placeholder, the server's own mail program is used. Either it goes out or it fails, never "just logged".
            if (config('mail.default') !== 'log') {
                return Mail::mailer();
            }
            if (! ServerMail::ready()) {
                throw new RuntimeException(ServerMail::command() === ''
                    ? "This server has no mail program configured (PHP's sendmail_path is empty), so Default cannot send. Set MAIL_SENDMAIL_PATH in .env, connect a mail service, or choose \"Log only\" to try things out."
                    : "The server's mail program was not found (".ServerMail::binary()."), so Default cannot send. Fix MAIL_SENDMAIL_PATH in .env, connect a mail service, or choose \"Log only\" to try things out.");
            }
            $name = 'server_'.substr(md5(ServerMail::command()), 0, 8);
            config(["mail.mailers.{$name}" => ['transport' => 'sendmail', 'path' => ServerMail::command()]]);

            return Mail::mailer($name);
        }
        if ($active['type'] === 'log') {
            return Mail::mailer('log'); // only written to the log
        }
        $conn = $active['conn'];

        // "Connect your account": SMTP login with a short-lived OAuth token
        if ($active['type'] === 'oauth') {
            $token = MailOAuth::accessToken($company, $active['key']);
            $name = 'oauth'.$company->id.'_'.substr(md5($token), 0, 8); // a fresh token means a fresh mailer
            config(["mail.mailers.{$name}" => [
                'transport' => 'xoauth2',
                'host' => MailOAuth::smtpHost($active['key']),
                'port' => 587,
                'username' => $conn['email'],
                'password' => $token,
            ]]);

            return Mail::mailer($name);
        }

        $name = 'biz'.$company->id.'_'.substr(md5(json_encode($conn)), 0, 8); // new name whenever the settings change
        config(["mail.mailers.{$name}" => [
            'transport' => 'smtp',
            'scheme' => ($conn['encryption'] ?? 'tls') === 'ssl' ? 'smtps' : 'smtp',
            'host' => $conn['host'],
            'port' => (int) ($conn['port'] ?? 587),
            'username' => $conn['username'] ?? null,
            'password' => $conn['password'] ?? null,
            'timeout' => 15,
        ]]);

        return Mail::mailer($name);
    }
}
