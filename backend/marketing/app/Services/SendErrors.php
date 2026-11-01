<?php

namespace App\Services;

/** Turns a send error into what the person should actually do about it. Returns null when there is no useful advice. */
class SendErrors
{
    /** Advice for a message from the send history, given its channel and the service that carried it. */
    public static function forLog(string $channel, ?string $error, ?string $service): ?string
    {
        if (! filled($error)) {
            return null;
        }
        if ($channel === 'sms') {
            return self::sms($error, $service);
        }
        // "default" = the server's own mail program (no connection details); other keys are connected services
        $conn = match ($service) {
            null, 'default' => [],
            'gmail' => ['host' => 'smtp.gmail.com'],
            'outlook' => ['host' => 'smtp.office365.com'],
            default => ['host' => (string) $service],
        };

        return self::email($error, $conn);
    }

    /** @param  array<string,mixed>  $email  the connection that was used ([] = the server's own mail program) */
    public static function email(string $error, array $email): ?string
    {
        if (! $email && ServerMail::isCatcher() && preg_match('/dial tcp|connectex|:1025/i', $error)) {
            return 'This server sends through Mailpit, but Mailpit is not running. Start it (in Laragon: Menu → Mail → Start Mailpit, or Start All), then send again. Sent mail appears in the Mailpit inbox at http://localhost:8025, it does not reach real inboxes.';
        }
        if (! $email && preg_match('/mail program|sendmail|proc_open|Process/i', $error)) {
            return "The server's own mail program could not send. Check MAIL_SENDMAIL_PATH in .env (it defaults to PHP's sendmail_path), or connect a mail service in the list above.";
        }
        $host = strtolower((string) ($email['host'] ?? ''));
        $rejected = str_contains($error, '535') || str_contains($error, 'Failed to authenticate') || str_contains($error, '534');

        if ($rejected && (str_contains($host, 'gmail') || str_contains($error, '5.7.8') || str_contains($error, 'gmail'))) {
            return 'Gmail did not accept that password. Gmail does not allow your normal password here: turn on 2-Step Verification for the Google account, create an "App password" (myaccount.google.com → Security → App passwords) and paste that 16-character code as the password. The username must be the full Gmail address. For a Google Workspace account, the administrator may also need to allow app passwords.';
        }
        if ($rejected && str_contains($host, 'office365')) {
            return 'Microsoft 365 refused the sign-in. Check the username is the full mailbox address, that "Authenticated SMTP" is enabled for that mailbox, and use an app password if the account has two-step sign-in.';
        }
        if ($rejected) {
            return 'The mail server rejected the username or password. Re-enter them (the password field is blank when you only want to keep the saved one) and check whether your provider needs an app password or an API key instead of your normal password.';
        }
        if (preg_match('/connection (could not be established|refused|timed out)|timed out|getaddrinfo|php_network/i', $error)) {
            return 'Could not reach the mail server. Check the server name and port, and that the encryption matches the port (587 with TLS/STARTTLS, or 465 with SSL).';
        }
        if (stripos($error, 'starttls') !== false || stripos($error, 'ssl') !== false || stripos($error, 'tls') !== false) {
            return 'The encryption setting does not match the server. Port 587 normally uses TLS/STARTTLS and port 465 uses SSL.';
        }
        if (preg_match('/5\.7\.1|relay|not allowed|sender|from address/i', $error)) {
            return 'The server refused the sender address. Set From email to an address your mail provider lets you send from (for Gmail, Outlook, Yahoo and Zoho that is the account you sign in with).';
        }
        if (preg_match('/unsubscribed|has no email|no email/i', $error)) {
            return 'This message was skipped on purpose: the person has unsubscribed, or has no email address.';
        }

        return null;
    }

    public static function sms(string $error, ?string $service): ?string
    {
        if (preg_match('/has no phone|unsubscribed/i', $error)) {
            return 'This text was skipped on purpose: the person has unsubscribed, or has no phone number.';
        }
        if (preg_match('/401|403|authenticat|credential|unauthori[sz]ed|invalid.*(key|token|secret|sid)|api[_ ]?key/i', $error)) {
            return 'The SMS service did not accept your login. Open Settings → SMS sending, re-enter the account details for '.($service && $service !== 'log' ? ucfirst($service) : 'the service').' and save.';
        }
        if (preg_match('/21211|21614|invalid.*(number|to|destination|recipient)|not a valid|unroutable|destination/i', $error)) {
            return 'The SMS service says the phone number is not valid. Use the international format with the country code, for example +977 9806064921 for Nepal or +61 400 000 000 for Australia.';
        }
        if (preg_match('/21608|trial|unverified|verify/i', $error)) {
            return 'A trial or unverified account can only text numbers you have verified with the SMS service. Verify the number there, or upgrade the account.';
        }
        if (preg_match('/21606|sender|from.*(not|invalid)|originator|alphanumeric/i', $error)) {
            return 'The sender (number or name) was refused. Set a sender your SMS service has approved for the destination country, in Settings → SMS sending.';
        }
        if (preg_match('/timed out|timeout|could not resolve|connection/i', $error)) {
            return 'Could not reach the SMS service. Check the server\'s internet connection and try again.';
        }

        return null;
    }
}
