<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use Illuminate\Console\Command;

class DispatchScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch-scheduled';

    protected $description = 'Start campaigns whose scheduled time has arrived';

    public function handle(): void
    {
        Campaign::withoutGlobalScopes()->where('status', 'scheduled')->where('scheduled_at', '<=', now())
            ->each(function (Campaign $c) {
                // atomic claim so overlapping runs can't double-send
                if (Campaign::withoutGlobalScopes()->whereKey($c->id)->where('status', 'scheduled')->update(['status' => 'sending'])) {
                    SendCampaignJob::dispatch($c->id);
                    $this->info("Dispatched campaign {$c->id}");
                }
            });
    }
}
