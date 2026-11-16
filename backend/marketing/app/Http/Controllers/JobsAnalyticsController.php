<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\EmailLog;
use App\Models\IntegrationSync;
use App\Models\PushLog;
use App\Models\SmsLog;
use App\Models\SocialLog;
use App\Models\WebhookDelivery;
use App\Models\WorkflowRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics → Jobs & queue: every kind of background work, what is still waiting, what finished and what failed.
 * Laravel deletes a queue job when it finishes, so "completed" work is read from what each kind of job leaves behind:
 * import runs, campaigns, messages, workflow runs and webhook deliveries. The raw queue (jobs waiting for a worker) is shown beside them.
 */
class JobsAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = in_array((int) $request->query('days'), [7, 14, 30, 60, 90], true) ? (int) $request->query('days') : 30;
        $from = now()->subDays($days - 1)->startOfDay();

        return [
            'days' => $days,
            'queue' => $this->queue(),
            'groups' => [
                $this->syncs($from), $this->campaigns($from), $this->messages($from), $this->workflows($from), $this->webhooks($from),
            ],
        ];
    }

    protected function group(string $key, string $label, string $what, int $pending, int $completed, int $failed, array $recent, array $extra = []): array
    {
        return ['key' => $key, 'label' => $label, 'what' => $what, 'pending' => $pending, 'completed' => $completed, 'failed' => $failed, 'recent' => $recent] + $extra;
    }

    /** Import and send runs of the connected integrations. A run that has been "running" for hours was lost (for example no worker). */
    protected function syncs($from): array
    {
        $q = fn () => IntegrationSync::where('created_at', '>=', $from);
        $recent = $q()->with('integration:id,name,provider')->latest('id')->limit(8)->get()->map(fn ($s) => [
            'title' => ($s->integration?->name ?? 'Integration').' · '.($s->full ? 'full' : 'changes only'), 'status' => $s->status, 'at' => $s->started_at ?? $s->created_at,
            'detail' => $s->status === 'failed' ? $s->error : ($s->fetched.' read · '.$s->created.' new'.($s->exported ? ' · '.$s->exported.' sent' : '')),
            'link' => '/integrations/'.$s->integration_id.'/history',
        ])->all();

        return $this->group('syncs', 'Integration syncs', 'Importing from and sending to connected platforms.',
            $q()->where('status', 'RUNNING')->count(), $q()->where('status', 'SUCCESS')->count(), $q()->where('status', 'FAILED')->count(), $recent,
            ['stale' => IntegrationSync::where('status', 'RUNNING')->where('created_at', '<', now()->subMinutes(10))->count()]);
    }

    protected function campaigns($from): array
    {
        $q = fn () => Campaign::where('created_at', '>=', $from);
        $recent = $q()->whereIn('status', ['SCHEDULED', 'SENDING', 'SENT', 'FAILED', 'CANCELLED'])->latest('id')->limit(8)->get()->map(fn ($c) => [
            'title' => $c->name.' · '.$c->type, 'status' => strtolower($c->status), 'at' => $c->completed_at ?? $c->started_at ?? $c->scheduled_at ?? $c->created_at,
            'detail' => $c->status === 'SCHEDULED' ? 'Scheduled' : null, 'link' => in_array($c->status, ['SENT', 'SENDING', 'FAILED'], true) ? '/reports/'.$c->id : '/campaigns/'.$c->id,
        ])->all();

        return $this->group('campaigns', 'Campaigns', 'Scheduled campaigns waiting for their time, campaigns being sent, and finished ones.',
            $q()->whereIn('status', ['SCHEDULED', 'SENDING'])->count(), $q()->where('status', 'SENT')->count(), $q()->where('status', 'FAILED')->count(), $recent);
    }

    /** Every message of every channel: queued waits for a worker. */
    protected function messages($from): array
    {
        $models = ['email' => EmailLog::class, 'sms' => SmsLog::class, 'push' => PushLog::class, 'social' => SocialLog::class];
        $count = fn (string $model, array $statuses) => $model::where('created_at', '>=', $from)->whereIn('status', $statuses)->count();
        $sum = fn (array $statuses) => array_sum(array_map(fn ($m) => $count($m, $statuses), $models));
        $by = [];
        foreach ($models as $channel => $model) {
            $by[] = ['channel' => $channel, 'pending' => $count($model, ['QUEUED']), 'completed' => $count($model, ['SENT', 'LOGGED']), 'failed' => $count($model, ['FAILED']), 'skipped' => $count($model, ['SKIPPED'])];
        }
        $recent = collect($models)->flatMap(fn ($model, $channel) => $model::where('created_at', '>=', $from)->whereIn('status', ['QUEUED', 'FAILED'])->latest('id')->limit(5)->get()
            ->map(fn ($l) => ['title' => ucfirst($l->platform ?? $channel).' to '.$l->recipient, 'status' => strtolower($l->status), 'at' => $l->created_at, 'detail' => $l->error, 'link' => '/message-logs']))
            ->sortByDesc('at')->take(8)->values()->all();

        return $this->group('messages', 'Messages', 'Emails, texts, notifications and social messages: waiting to be sent, sent, and failed.',
            $sum(['QUEUED']), $sum(['SENT', 'LOGGED']), $sum(['FAILED']), $recent, ['by_channel' => $by]);
    }

    protected function workflows($from): array
    {
        $q = fn () => WorkflowRun::where('created_at', '>=', $from);
        $recent = $q()->with('workflow:id,name')->latest('id')->limit(8)->get()->map(fn ($r) => [
            'title' => ($r->workflow?->name ?? 'Workflow').' · run #'.$r->id, 'status' => $r->status, 'at' => $r->completed_at ?? $r->next_run_at ?? $r->created_at,
            'detail' => $r->status === 'failed' ? $r->error : null, 'link' => '/automation/history',
        ])->all();

        return $this->group('workflows', 'Workflow runs', 'People moving through your automations: in progress (including waiting steps), finished and failed.',
            $q()->where('status', 'RUNNING')->count(), $q()->where('status', 'COMPLETED')->count(), $q()->where('status', 'FAILED')->count(), $recent);
    }

    protected function webhooks($from): array
    {
        $q = fn () => WebhookDelivery::where('created_at', '>=', $from);
        $recent = $q()->where('success', false)->latest('id')->limit(8)->get()->map(fn ($d) => [
            'title' => $d->event, 'status' => 'failed', 'at' => $d->created_at, 'detail' => $d->status_code ? 'HTTP '.$d->status_code : 'No answer', 'link' => '/automation/webhooks',
        ])->all();

        return $this->group('webhooks', 'Webhook deliveries', 'Calls to your webhook addresses.', 0, $q()->where('success', true)->count(), $q()->where('success', false)->count(), $recent);
    }

    /** The queue itself (all companies together: only counts, never contents): jobs waiting for a worker, being worked on, delayed, and failed. */
    protected function queue(): array
    {
        $driver = (string) config('queue.default');
        $out = ['driver' => $driver, 'available' => false, 'pending' => 0, 'delayed' => 0, 'processing' => 0, 'failed' => 0, 'oldest_waiting_minutes' => null, 'by_job' => [], 'worker_hint' => null];
        if ($driver === 'sync') {
            $out['worker_hint'] = 'Jobs run immediately while a page is loading (QUEUE_CONNECTION=sync), so nothing waits.';

            return $out;
        }
        if ($driver !== 'database' || ! Schema::hasTable('jobs')) {
            $out['worker_hint'] = 'The queue ('.$driver.') is not shown here.';

            return $out;
        }

        $now = time();
        $jobs = DB::table('jobs');
        $out['available'] = true;
        $out['pending'] = (clone $jobs)->whereNull('reserved_at')->where('available_at', '<=', $now)->count();
        $out['delayed'] = (clone $jobs)->whereNull('reserved_at')->where('available_at', '>', $now)->count();
        $out['processing'] = (clone $jobs)->whereNotNull('reserved_at')->count();
        $out['failed'] = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $oldest = (clone $jobs)->whereNull('reserved_at')->where('available_at', '<=', $now)->min('available_at');
        $out['oldest_waiting_minutes'] = $oldest ? max(0, (int) floor(($now - (int) $oldest) / 60)) : null;

        $name = fn ($payload) => class_basename((string) (json_decode((string) $payload, true)['displayName'] ?? 'Job'));
        $tally = [];
        foreach ((clone $jobs)->limit(2000)->get(['payload', 'reserved_at', 'available_at']) as $j) {
            $key = $name($j->payload);
            $tally[$key]['job'] = $key;
            $tally[$key]['pending'] = ($tally[$key]['pending'] ?? 0) + 1;
        }
        if (Schema::hasTable('failed_jobs')) {
            foreach (DB::table('failed_jobs')->limit(2000)->get(['payload']) as $j) {
                $key = $name($j->payload);
                $tally[$key]['job'] = $key;
                $tally[$key]['failed'] = ($tally[$key]['failed'] ?? 0) + 1;
            }
        }
        $out['by_job'] = collect($tally)->map(fn ($t) => ['job' => $t['job'], 'pending' => $t['pending'] ?? 0, 'failed' => $t['failed'] ?? 0])->sortByDesc(fn ($t) => $t['pending'] + $t['failed'])->values()->all();

        // jobs that have waited for minutes mean no worker is taking them
        if ($out['pending'] > 0 && ($out['oldest_waiting_minutes'] ?? 0) >= 2) {
            $out['worker_hint'] = 'Jobs have been waiting for '.$out['oldest_waiting_minutes'].' minutes, so no queue worker seems to be running. Start one with: php artisan queue:work';
        }

        return $out;
    }
}
