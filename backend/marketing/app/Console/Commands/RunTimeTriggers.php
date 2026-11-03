<?php

namespace App\Console\Commands;

use App\Services\Automation;
use Illuminate\Console\Command;

class RunTimeTriggers extends Command
{
    protected $signature = 'automation:time-triggers';

    protected $description = 'Start workflows that depend on time: a date arrives, or a person has not opened emails';

    public function handle(): void
    {
        $this->info('Started '.Automation::runTimeTriggers().' workflow run(s).');
    }
}
