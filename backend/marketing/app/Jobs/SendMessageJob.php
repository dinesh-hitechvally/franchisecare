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

    // a temporary problem (timeout, "try again later", rate limit) is retried twice, a minute and then five minutes later; anything else fails at once
    public int $tries = 3;

    protected function isTransient(Throwable $e): bool
    {
        return (bool) preg_match('/timed? ?out|temporar|try again|rate.?limit|too many|throttl|service unavailable|(421|429|450|451|452|500|502|503|504)/i', $e->getMessage());
    }

    /** $channel says which table the log row is in: email_logs or sms_logs */
    public function __construct(public int $logId, public string $channel = 'email') {}

    public function handle(MessageRenderer $renderer, SmsManager $sms): void
    {
        $model = $this->channel === 'sms' ? SmsLog::class : EmailLog::class;
        $log = $model::withoutGlobalScope('company')->with('person.fieldValues.field')->findOrFail($this->logId);
        if ($log->status !== 'queued') {
            return;
        }

        $company = Company::findOrFail($log->company_id);
        $campaign = $log->campaign_id ? Campaign::withoutGlobalScope('company')->find($log->campaign_id) : null;
        // campaign messages take their content from the campaign; workflow messages carry it on the log itself
        // the campaign or the person was deleted after this message was queued: don't send it
        if (($log->campaign_id && ! $campaign) || ($log->person_id && ! $log->person)) {
            $log->update(['status' => 'skipped', 'error' => $log->campaign_id && ! $campaign ? 'The campaign was deleted before this was sent.' : 'The person was deleted before this was sent.']);
            if ($log->campaign_id) {
                FinalizeCampaign::check($log->campaign_id);
            }

            return;
        }
        $subjectSrc = $campaign->subject ?? $log->subject ?? '';
        $bodySrc = $campaign->body ?? $log->body ?? '';

        try {
            if ($this->channel === 'email') {
                [$subject, $html] = $renderer->renderEmail($subjectSrc, $bodySrc, $company, $log->person, $log);

                $files = Attachments::in($html.Attachments::marker($campaign->attachments ?? null), $log->company_id); // the campaign's files, plus any the message body itself names (form auto-replies, workflow templates)

                // the text copy keeps the real links, not the click-tracking ones the HTML gets
                $plain = MessageRenderer::plainText($renderer->renderEmail($subjectSrc, $bodySrc, $company, $log->person)[1]);
                $unsub = $log->person ? url("/api/unsubscribe/{$log->person->unsubscribe_token}?channel=email") : null;

                CompanyMailer::for($company)->html($html, function ($m) use ($log, $company, $subject, $files, $plain, $unsub) {
                    $m->text($plain);
                    if ($unsub) {
                        // the "Unsubscribe" button mail apps show next to the sender; required by Gmail and Yahoo for bulk senders
                        $headers = $m->getSymfonyMessage()->getHeaders();
                        $headers->addTextHeader('List-Unsubscribe', '<'.$unsub.'>');
                        $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                    }
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

                $log->update(['status' => SendLog::deliveredStatus($company, 'email'), 'error' => null, 'subject' => $subject, 'body' => $html, 'sent_at' => now(), 'service' => SendLog::service($company, 'email')]);
            } else {
                $text = $renderer->renderSms($bodySrc, $company, $log->person);
                $id = $sms->forCompany($company)->send($log->recipient, $text);

                $log->update(['status' => SendLog::deliveredStatus($company, 'sms'), 'error' => null, 'body' => $text, 'provider_id' => $id, 'sent_at' => now(), 'service' => SendLog::service($company, 'sms')]);
            }
        } catch (Throwable $e) {
            if ($this->isTransient($e) && $this->attempts() < $this->tries && config('queue.default') !== 'sync') {
                $log->update(['error' => 'Will try again: '.mb_substr($e->getMessage(), 0, 900)]); // still queued; the next attempt clears this on success
                $this->release($this->attempts() === 1 ? 60 : 300);

                return;
            }
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
