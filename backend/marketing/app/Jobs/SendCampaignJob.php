<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Expands a campaign into one log row (email_logs or sms_logs) + SendMessageJob per recipient. */
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = Campaign::withoutGlobalScope('company')->find($this->campaignId); // null when it was deleted meanwhile
        if (! $campaign || $campaign->status === 'sent') {
            return;
        }

        $campaign->forceFill(['status' => 'sending', 'started_at' => now()])->save();

        if ($campaign->segment_id && ($segment = \App\Models\Segment::withoutGlobalScope('company')->where('company_id', $campaign->company_id)->find($campaign->segment_id))) {
            $query = \App\Services\SegmentQuery::forSegment($segment);
        } else {
            $query = $campaign->contact_list_id
                ? Contact::withoutGlobalScope('company')->whereHas('lists', fn ($q) => $q->where('contact_lists.id', $campaign->contact_list_id))
                : Contact::withoutGlobalScope('company');
            $query->where('company_id', $campaign->company_id);
        }

        $isEmail = $campaign->type === 'email';

        $query->chunkById(200, function ($contacts) use ($campaign, $isEmail) {
            foreach ($contacts as $contact) {
                $recipient = $isEmail ? $contact->email : $contact->phone;
                $subscribed = $isEmail ? $contact->email_subscribed : $contact->sms_subscribed;

                $log = $campaign->logModel()::create([
                    'company_id' => $campaign->company_id,
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'recipient' => $recipient ?: '(none)',
                    'status' => $recipient && $subscribed ? 'queued' : 'skipped',
                    'error' => ! $recipient ? 'Contact has no '.($isEmail ? 'email' : 'phone')
                        : (! $subscribed ? 'Contact unsubscribed' : null),
                ]);

                if ($log->status === 'queued') {
                    SendMessageJob::dispatch($log->id, $campaign->type);
                }
            }
        });

        FinalizeCampaign::check($campaign->id);
    }
}
