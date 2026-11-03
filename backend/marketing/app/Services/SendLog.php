<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailLog;
use App\Models\SmsLog;
use Throwable;

/** Writes the send history (email_logs / sms_logs) for sends that do not go through the campaign queue: test messages and staff notifications. */
class SendLog
{
    /** Campaign sending speed in messages per minute (Settings → Email sending); 0 means no limit. */
    public static function perMinute(Company $company): int
    {
        return max(0, (int) $company->setting('sending.per_minute', 60));
    }

    /** The service that would carry a message now: a mail/SMS connection key, "default" (platform mail) or "log" (Log only for SMS). */
    public static function service(Company $company, string $channel): string
    {
        return $channel === 'sms' ? SmsSettings::activeKey($company) : MailSettings::activeKey($company);
    }

    /**
     * "sent" when the message really left for a provider, "logged" when the service in use only writes to a log
     * ("Log only", for both email and SMS). Default never logs: it sends or fails.
     */
    public static function deliveredStatus(Company $company, string $channel): string
    {
        if ($channel === 'sms') {
            return SmsSettings::activeKey($company) === 'log' ? 'logged' : 'sent';
        }

        return MailSettings::activeKey($company) === 'log' ? 'logged' : 'sent';
    }

    /**
     * Runs $send and records it in the send history either way: "sent" (with the provider's message id when it returns one)
     * or "failed" (with the error, which is then thrown again so the caller can show it).
     */
    public static function run(Company $company, string $channel, string $to, ?string $subject, ?string $body, string $source, \Closure $send): mixed
    {
        $isSms = $channel === 'sms';
        $log = ($isSms ? SmsLog::class : EmailLog::class)::create([
            'company_id' => $company->id, 'recipient' => $to, 'body' => $body,
            'status' => 'queued', 'source' => $source, 'service' => self::service($company, $channel),
        ] + ($isSms ? [] : ['subject' => $subject]));

        try {
            $result = $send();
            $log->update(['status' => self::deliveredStatus($company, $channel), 'sent_at' => now(), 'provider_id' => is_string($result) ? mb_substr($result, 0, 255) : null]);

            return $result;
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);

            throw $e;
        }
    }
}
