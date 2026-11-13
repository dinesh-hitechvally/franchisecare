<?php

namespace App\Services\Integrations;

use App\Jobs\SyncIntegrationJob;
use App\Models\Integration;
use App\Models\IntegrationSync;

class Syncs
{
    /** A sync that has been "running" for longer than this is assumed lost (for example the worker was stopped) and no longer blocks a new one. */
    public const STALE_AFTER_HOURS = 2;

    public static function running(Integration $integration): ?IntegrationSync
    {
        return IntegrationSync::withoutGlobalScope('company')->where('integration_id', $integration->id)->where('status', 'RUNNING')
            ->where('created_at', '>=', now()->subHours(self::STALE_AFTER_HOURS))->latest('id')->first();
    }

    /**
     * Starts an import for the connection and queues it. $full reads every customer; otherwise only the ones changed since the
     * last successful sync (the first sync is always full). Returns [the sync, whether a new one was started]: while one is
     * already running that one is returned instead.
     *
     * @return array{0: IntegrationSync, 1: bool}
     */
    public static function start(Integration $integration, string $trigger = 'manual', bool $full = false): array
    {
        if ($running = self::running($integration)) {
            return [$running, false];
        }

        $since = ! $full && $integration->last_synced_at ? $integration->last_synced_at->copy()->subMinutes(5) : null; // a little overlap, so nothing falls between two syncs
        $sync = (new IntegrationSync)->forceFill([
            'company_id' => $integration->company_id, 'integration_id' => $integration->id, 'trigger' => $trigger,
            'full' => $since === null, 'status' => 'running', 'since' => $since, 'started_at' => now(),
        ]);
        $sync->save();

        SyncIntegrationJob::dispatch($sync->id);

        return [$sync, true];
    }

    /** The connections that are due for their automatic sync, and have none running. */
    public static function due(): \Illuminate\Support\Collection
    {
        return Integration::withoutGlobalScope('company')->where('status', 'CONNECTED')->whereNotNull('last_synced_at')->get()
            ->filter(function (Integration $i) {
                $every = (int) $i->setting('sync_every', 0);

                return $every > 0 && $i->last_synced_at->lte(now()->subMinutes($every)) && ! self::running($i);
            })->values();
    }
}
