<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\MessageLog;

class FinalizeCampaign
{
    /** Marks the campaign finished once no message is left queued. */
    public static function check(int $campaignId): void
    {
        if (MessageLog::withoutGlobalScopes()->where('campaign_id', $campaignId)->where('status', 'queued')->exists()) {
            return;
        }

        $allFailed = ! MessageLog::withoutGlobalScopes()->where('campaign_id', $campaignId)->where('status', 'sent')->exists()
            && MessageLog::withoutGlobalScopes()->where('campaign_id', $campaignId)->where('status', 'failed')->exists();

        Campaign::withoutGlobalScopes()->whereKey($campaignId)->where('status', 'sending')->update([
            'status' => $allFailed ? 'failed' : 'sent',
            'completed_at' => now(),
        ]);
    }
}
