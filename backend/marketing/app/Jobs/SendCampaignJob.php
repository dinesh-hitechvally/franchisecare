<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Company;
use App\Services\SendLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Expands a campaign into one log row (email_logs or sms_logs) + SendMessageJob per recipient, spread out at the company's sending speed. */
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = Campaign::withoutGlobalScope('company')->find($this->campaignId); // null when it was deleted meanwhile
        if (! $campaign || in_array($campaign->status, ['sent', 'cancelled'], true)) {
            return;
        }

        $campaign->forceFill(['status' => 'sending', 'started_at' => now()])->save();

        $isEmail = $campaign->type === 'email';
        // messages go out at the company's own pace (messages per minute; 0 = as fast as possible), so a mail account's limits are not hit
        $perMinute = SendLog::perMinute(Company::find($campaign->company_id));
        $gap = $perMinute > 0 ? 60 / $perMinute : 0;
        $n = 0;
        $left = 0; // unsubscribed or without an address: not messages, so nothing is written to the message log for them

        $campaign->audienceQuery()->chunkById(200, function ($contacts) use ($campaign, $isEmail, $gap, &$n, &$left) {
            // stopped by a person while the list was still being expanded: create nothing more
            if (Campaign::withoutGlobalScope('company')->whereKey($campaign->id)->value('status') === 'cancelled') {
                return false;
            }

            foreach ($contacts as $contact) {
                $recipient = $isEmail ? $contact->email : $contact->phone;
                if (! $recipient || ! $contact->is_subscribed) {
                    $left++;

                    continue;
                }

                $log = $campaign->logModel()::create([
                    'company_id' => $campaign->company_id,
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'recipient' => $recipient,
                    'status' => 'queued',
                ]);

                SendMessageJob::dispatch($log->id, $campaign->type)->delay(now()->addSeconds((int) round($n * $gap)));
                $n++;
            }
        });

        // kept on the campaign so the reports can still say how many people were left out
        Campaign::withoutGlobalScope('company')->whereKey($campaign->id)->update(['audience_skipped' => $left]);

        FinalizeCampaign::check($campaign->id);
    }
}
