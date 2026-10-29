<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\MessageLog;
use App\Services\MessageRenderer;
use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $logId) {}

    public function handle(MessageRenderer $renderer, SmsManager $sms): void
    {
        $log = MessageLog::withoutGlobalScopes()->with('contact')->findOrFail($this->logId);
        if ($log->status !== 'queued') {
            return;
        }

        $business = Business::findOrFail($log->business_id);
        $campaign = $log->campaign_id ? Campaign::withoutGlobalScopes()->find($log->campaign_id) : null;

        try {
            if ($log->channel === 'email') {
                [$subject, $html] = $renderer->renderEmail($campaign->subject ?? '', $campaign->body ?? '', $business, $log->contact, $log);

                Mail::html($html, function ($m) use ($log, $business, $subject) {
                    $m->to($log->recipient)->subject($subject);
                    if ($business->from_email) {
                        $m->from($business->from_email, $business->from_name ?: $business->name);
                    }
                    if ($business->reply_to) {
                        $m->replyTo($business->reply_to);
                    }
                });

                $log->update(['status' => 'sent', 'subject' => $subject, 'body' => $html, 'sent_at' => now()]);
            } else {
                $text = $renderer->renderSms($campaign->body ?? '', $business, $log->contact);
                $id = $sms->forBusiness($business)->send($log->recipient, $text);

                $log->update(['status' => 'sent', 'body' => $text, 'provider_id' => $id, 'sent_at' => now()]);
            }
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
        }

        if ($log->campaign_id) {
            FinalizeCampaign::check($log->campaign_id);
        }
    }
}
