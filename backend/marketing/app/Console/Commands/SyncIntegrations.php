<?php

namespace App\Console\Commands;

use App\Services\Integrations\Syncs;
use Illuminate\Console\Command;

class SyncIntegrations extends Command
{
    protected $signature = 'integrations:sync';

    protected $description = 'Start the automatic import of customers for every connection that is due';

    public function handle(): int
    {
        $started = 0;
        foreach (Syncs::due() as $integration) {
            [, $new] = Syncs::start($integration, 'scheduled');
            $started += $new ? 1 : 0;
        }
        $this->info("Started $started sync".($started === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }
}
