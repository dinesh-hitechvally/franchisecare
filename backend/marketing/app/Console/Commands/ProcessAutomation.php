<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWorkflowRunJob;
use App\Models\WorkflowRun;
use Illuminate\Console\Command;

class ProcessAutomation extends Command
{
    protected $signature = 'automation:process';

    protected $description = 'Resume workflow runs whose wait period has ended';

    public function handle(): void
    {
        WorkflowRun::withoutGlobalScope('company')->where('status', 'running')->where('next_run_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('locked_until')->orWhere('locked_until', '<', now()))
            ->whereHas('workflow', fn ($q) => $q->where('status', 'active'))
            ->pluck('id')->each(fn ($id) => ProcessWorkflowRunJob::dispatch($id));
    }
}
