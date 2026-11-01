<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Campaign;
use App\Models\EmailLog;
use App\Models\SmsLog;
use App\Services\Attachments;
use App\Services\CompanyMailer;
use App\Services\SendLog;
use App\Services\MessageRenderer;
use App\Services\Sms\SmsManager;
use App\Services\WebhookDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** $channel says which table the log row is in: email_logs or sms_logs */
    public function __construct(public int $logId, public string $channel = 'email') {}

    public function handle(MessageRenderer $renderer, SmsManager $sms): void
    {
        $model = $this->channel === 'sms' ? SmsLog::class : EmailLog::class;
        $log = $model::withoutGlobalScope('company')->with('contact.fieldValues.field')->findOrFail($this->logId);
        if ($log->status !== 'queued') {
            return;
        }

        $company = Company::findOrFail($log->company_id);
        $campaign = $log->campaign_id ? Campaign::withoutGlobalScope('company')->find($log->campaign_id) : null;
        // campaign messages take their content from the campaign; workflow messages carry it on the log itself
        // the campaign or the person was deleted after this message was queued: don't send it
        if (($log->campaign_id && ! $campaign) || ($log->contact_id && ! $log->contact)) {
            $log->update(['status' => 'skipped', 'error' => $log->campaign_id && ! $campaign ? 'The campaign was deleted before this was sent.' : 'The contact was deleted before this was sent.']);
            if ($log->campaign_id) {
                FinalizeCampaign::check($log->campaign_id);
            }

            return;
        }
        $subjectSrc = $campaign->subject ?? $log->subject ?? '';
        $bodySrc = $campaign->body ?? $log->body ?? '';

        try {
            if ($this->channel === 'email') {
                [$subject, $html] = $renderer->renderEmail($subjectSrc, $bodySrc, $company, $log->contact, $log);

                $files = Attachments::in($html.Attachments::marker($campaign->attachments ?? null), $log->company_id); // the campaign's files, plus any the message body itself names (form auto-replies, workflow templates)

                CompanyMailer::for($company)->html($html, function ($m) use ($log, $company, $subject, $files) {
                    foreach ($files as $f) {
                        $m->attach($f['path'], ['as' => $f['name'], 'mime' => $f['mime']]);
                    }
                    $m->to($log->recipient)->subject($subject);
                    if ($company->from_email) {
                        $m->from($company->from_email, $company->from_name ?: $company->name);
                    }
                    if ($company->reply_to) {
                        $m->replyTo($company->reply_to);
                    }
                });

                $log->update(['status' => SendLog::deliveredStatus($company, 'email'), 'subject' => $subject, 'body' => $html, 'sent_at' => now(), 'service' => SendLog::service($company, 'email')]);
            } else {
                $text = $renderer->renderSms($bodySrc, $company, $log->contact);
                $id = $sms->forCompany($company)->send($log->recipient, $text);

                $log->update(['status' => SendLog::deliveredStatus($company, 'sms'), 'body' => $text, 'provider_id' => $id, 'sent_at' => now(), 'service' => SendLog::service($company, 'sms')]);
            }
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000), 'service' => SendLog::service($company, $this->channel)]);
            WebhookDispatcher::dispatch($log->company_id, 'message.failed', [
                'channel' => $this->channel, 'recipient' => $log->recipient, 'campaign_id' => $log->campaign_id, 'error' => $log->error,
            ]);
        }

        if ($log->campaign_id) {
            FinalizeCampaign::check($log->campaign_id);
        }
    }
}
