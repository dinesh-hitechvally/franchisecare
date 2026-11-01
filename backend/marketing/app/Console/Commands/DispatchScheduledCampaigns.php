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
        Campaign::withoutGlobalScope('company')->where('status', 'scheduled')->where('scheduled_at', '<=', now())
            ->each(function (Campaign $c) {
                // atomic claim so overlapping runs can't double-send
                if (Campaign::withoutGlobalScope('company')->whereKey($c->id)->where('status', 'scheduled')->update(['status' => 'sending'])) {
                    \App\Services\History::record('updated', $c->fresh(), ['status']); // a plain query: no model event, so record it here
                    SendCampaignJob::dispatch($c->id);
                    $this->info("Dispatched campaign {$c->id}");
                }
            });
    }
}
