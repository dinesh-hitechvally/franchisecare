<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Mail;

/** Returns the mailer a company should send with: the one service it has switched on (Google/Microsoft sign-in, an SMTP server) or the app default. */
class CompanyMailer
{
    public static function for(Company $company): Mailer
    {
        $active = MailSettings::active($company);
        if (! $active) {
            return Mail::mailer();
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
