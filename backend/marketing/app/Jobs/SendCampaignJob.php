<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Expands a campaign into one MessageLog + SendMessageJob per recipient. */
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = Campaign::withoutGlobalScopes()->findOrFail($this->campaignId);
        if ($campaign->status === 'sent') {
            return;
        }

        $campaign->forceFill(['status' => 'sending', 'started_at' => now()])->save();

        $query = $campaign->contact_list_id
            ? Contact::withoutGlobalScopes()->whereHas('lists', fn ($q) => $q->where('contact_lists.id', $campaign->contact_list_id))
            : Contact::withoutGlobalScopes();
        $query->where('business_id', $campaign->business_id);

        $isEmail = $campaign->type === 'email';

        $query->chunkById(200, function ($contacts) use ($campaign, $isEmail) {
            foreach ($contacts as $contact) {
                $recipient = $isEmail ? $contact->email : $contact->phone;
                $subscribed = $isEmail ? $contact->email_subscribed : $contact->sms_subscribed;

                $log = MessageLog::create([
                    'business_id' => $campaign->business_id,
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'channel' => $campaign->type,
                    'recipient' => $recipient ?: '(none)',
                    'status' => $recipient && $subscribed ? 'queued' : 'skipped',
                    'error' => ! $recipient ? 'Contact has no '.($isEmail ? 'email' : 'phone')
                        : (! $subscribed ? 'Contact unsubscribed' : null),
                ]);

                if ($log->status === 'queued') {
                    SendMessageJob::dispatch($log->id);
                }
            }
        });

        FinalizeCampaign::check($campaign->id);
    }
}
