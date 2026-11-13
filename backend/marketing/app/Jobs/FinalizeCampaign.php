<?php

namespace App\Jobs;

use App\Models\Campaign;

class FinalizeCampaign
{
    /** Marks the campaign finished once no message is left queued. */
    public static function check(int $campaignId): void
    {
        $campaign = Campaign::withoutGlobalScope('company')->find($campaignId);
        if (! $campaign) {
            return;
        }
        $logs = fn () => $campaign->logModel()::withoutGlobalScope('company')->where('campaign_id', $campaignId);

        if ($logs()->where('status', 'QUEUED')->exists()) {
            return;
        }

        $allFailed = ! $logs()->where('status', 'SENT')->exists()
            && $logs()->where('status', 'FAILED')->exists();

        $changed = Campaign::withoutGlobalScope('company')->whereKey($campaignId)->where('status', 'SENDING')->update([
            'status' => $allFailed ? 'FAILED' : 'SENT',
            'completed_at' => now(),
        ]);

        if ($changed) {
            $c = Campaign::withoutGlobalScope('company')->find($campaignId);
            \App\Services\History::record('updated', $c, ['status', 'completed_at']); // plain query above: record the change here
            $count = fn ($s) => $logs()->where('status', $s)->count();
            \App\Services\WebhookDispatcher::dispatch($c->company_id, 'campaign.sent', [
                'id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'status' => $c->status,
                'sent' => $count('SENT'), 'failed' => $count('FAILED'), 'skipped' => $count('SKIPPED') + (int) $c->audience_skipped, 'logged' => $count('LOGGED'),
            ]);
        }
    }
}
