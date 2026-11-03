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

        if ($logs()->where('status', 'queued')->exists()) {
            return;
        }

        $allFailed = ! $logs()->where('status', 'sent')->exists()
            && $logs()->where('status', 'failed')->exists();

        $changed = Campaign::withoutGlobalScope('company')->whereKey($campaignId)->where('status', 'sending')->update([
            'status' => $allFailed ? 'failed' : 'sent',
            'completed_at' => now(),
        ]);

        if ($changed) {
            $c = Campaign::withoutGlobalScope('company')->find($campaignId);
            \App\Services\History::record('updated', $c, ['status', 'completed_at']); // plain query above: record the change here
            $count = fn ($s) => $logs()->where('status', $s)->count();
            \App\Services\WebhookDispatcher::dispatch($c->company_id, 'campaign.sent', [
                'id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'status' => $c->status,
                'sent' => $count('sent'), 'failed' => $count('failed'), 'skipped' => $count('skipped') + (int) $c->audience_skipped, 'logged' => $count('logged'),
            ]);
        }
    }
}
