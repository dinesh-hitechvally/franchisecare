<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Company;
use App\Models\PushDevice;
use App\Services\Integrations\Providers;
use App\Services\SendLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/** Expands a campaign into one log row (email_logs, sms_logs or push_logs: one per device) + SendMessageJob per recipient, spread out at the company's sending speed. */
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = Campaign::withoutGlobalScope('company')->find($this->campaignId); // null when it was deleted meanwhile
        if (! $campaign || in_array($campaign->status, ['SENT', 'CANCELLED'], true)) {
            return;
        }

        $campaign->forceFill(['status' => 'SENDING', 'started_at' => now()])->save();

        $isEmail = $campaign->type === 'email';
        $isPush = $campaign->type === 'push';
        // messages go out at the company's own pace (messages per minute; 0 = as fast as possible), so a mail account's limits are not hit
        $perMinute = SendLog::perMinute(Company::find($campaign->company_id));
        $gap = $perMinute > 0 ? 60 / $perMinute : 0;
        $n = 0;
        $total = 0; // everyone the audience had at this moment: kept so the campaign page does not change when people are added later
        $left = 0; // people without an address: nothing can be sent, so they are only counted (nothing is written to the message log)

        if ($campaign->toAllDevices()) {
            // every active device, whoever it belongs to. A device of a person who unsubscribed is still left out, like everywhere else.
            PushDevice::withoutGlobalScope('company')->where('company_id', $campaign->company_id)->where('status', 'ACTIVE')->when($campaign->push_platform, fn ($q, $p) => $q->where('platform', strtoupper($p)))->with('person:id,is_subscribed')
                ->chunkById(200, function ($devices) use ($campaign, $gap, &$n, &$total) {
                    if (Campaign::withoutGlobalScope('company')->whereKey($campaign->id)->value('status') === 'CANCELLED') {
                        return false;
                    }
                    foreach ($devices as $device) {
                        $total++;
                        if ($device->person_id && $device->person && ! $device->person->is_subscribed) {
                            continue;
                        }
                        $log = $campaign->logModel()::create([
                            'company_id' => $campaign->company_id, 'campaign_id' => $campaign->id, 'person_id' => $device->person_id, 'push_device_id' => $device->id,
                            'token' => Str::random(40), 'recipient' => $device->label ?: ucfirst($device->platform), 'status' => 'QUEUED',
                        ]);
                        SendMessageJob::dispatch($log->id, 'push')->delay(now()->addSeconds((int) round($n * $gap)));
                        $n++;
                    }
                });
        } else {
            $this->expandPeople($campaign, $isEmail, $isPush, $gap, $n, $left, $total);
        }

        // kept on the campaign so the reports can still say how many people were left out
        Campaign::withoutGlobalScope('company')->whereKey($campaign->id)->update(['audience_skipped' => $left, 'audience_total' => $total]);

        FinalizeCampaign::check($campaign->id);
    }

    /** One log row + message per person (per device for push) of the campaign's audience. */
    protected function expandPeople(Campaign $campaign, bool $isEmail, bool $isPush, float|int $gap, int &$n, int &$left, int &$total): void
    {
        $campaign->audienceQuery()->chunkById(200, function ($people) use ($campaign, $isEmail, $isPush, $gap, &$n, &$left, &$total) {
            // stopped by a person while the list was still being expanded: create nothing more
            if (Campaign::withoutGlobalScope('company')->whereKey($campaign->id)->value('status') === 'CANCELLED') {
                return false;
            }

            // push goes to devices, not addresses: a person can have several (phone, laptop…) and gets one notification on each
            $devices = $isPush
                ? PushDevice::withoutGlobalScope('company')->where('company_id', $campaign->company_id)->where('status', 'ACTIVE')->whereIn('person_id', $people->pluck('id'))->get()->groupBy('person_id')
                : collect();

            foreach ($people as $person) {
                $total++;
                if ($isPush) {
                    if (! isset($devices[$person->id])) {
                        $left++; // no device registered: nothing can be sent

                        continue;
                    }
                    if (! $person->is_subscribed) {
                        continue;
                    }
                    foreach ($devices[$person->id] as $device) {
                        $log = $campaign->logModel()::create([
                            'company_id' => $campaign->company_id, 'campaign_id' => $campaign->id, 'person_id' => $person->id, 'push_device_id' => $device->id,
                            'token' => Str::random(40), 'recipient' => $device->label ?: ucfirst($device->platform), 'status' => 'QUEUED',
                        ]);
                        SendMessageJob::dispatch($log->id, 'push')->delay(now()->addSeconds((int) round($n * $gap)));
                        $n++;
                    }

                    continue;
                }

                $social = $campaign->isSocial();
                $recipient = $social ? Providers::find($campaign->type)?->address($person) : ($isEmail ? $person->email : $person->phone); // social: the phone (WhatsApp) or the id the person has on that platform
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
                    'status' => 'QUEUED',
                ] + ($social ? ['platform' => $campaign->type, 'integration_id' => $campaign->social_account_id] : []));

                SendMessageJob::dispatch($log->id, $social ? 'social' : $campaign->type)->delay(now()->addSeconds((int) round($n * $gap)));
                $n++;
            }
        });

    }
}
