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
        $total = 0; // everyone the audience had at this moment: kept so the campaign page does not change when people are added later
        $left = 0; // people without an address: nothing can be sent, so they are only counted (nothing is written to the message log)

        $campaign->audienceQuery()->chunkById(200, function ($people) use ($campaign, $isEmail, $gap, &$n, &$left, &$total) {
            // stopped by a person while the list was still being expanded: create nothing more
            if (Campaign::withoutGlobalScope('company')->whereKey($campaign->id)->value('status') === 'cancelled') {
                return false;
            }

            foreach ($people as $person) {
                $total++;
                $recipient = $isEmail ? $person->email : $person->phone;
                if (! $recipient) {
                    $left++;

                    continue;
                }
                if (! $person->is_subscribed) {
                    continue; // opted out: never sent to, and not a "skipped" message either
                }

                $log = $campaign->logModel()::create([
                    'company_id' => $campaign->company_id,
                    'campaign_id' => $campaign->id,
                    'person_id' => $person->id,
                    'recipient' => $recipient,
                    'status' => 'queued',
                ]);

                SendMessageJob::dispatch($log->id, $campaign->type)->delay(now()->addSeconds((int) round($n * $gap)));
                $n++;
            }
        });

        // kept on the campaign so the reports can still say how many people were left out
        Campaign::withoutGlobalScope('company')->whereKey($campaign->id)->update(['audience_skipped' => $left, 'audience_total' => $total]);

        FinalizeCampaign::check($campaign->id);
    }
}
