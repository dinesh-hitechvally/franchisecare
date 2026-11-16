<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\IntegrationSync;
use App\Services\Integrations\Importer;
use App\Services\Integrations\IntegrationException;
use App\Models\Person;
use App\Services\Integrations\Providers;
use App\Services\PersonSources;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Imports a store's customers a few pages at a time. A long import is therefore many short jobs: when this one has done its
 * share it saves where it stopped (the sync's cursor) and queues the next.
 */
class SyncIntegrationJob implements ShouldQueue
{
    use Queueable;

    /** Pages read by one job before it hands over to the next. */
    public const PAGES_PER_JOB = 10;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public int $syncId) {}

    public function handle(): void
    {
        $sync = IntegrationSync::withoutGlobalScope('company')->find($this->syncId);
        if (! $sync || $sync->status !== 'running') {
            return;
        }
        $integration = Integration::withoutGlobalScope('company')->find($sync->integration_id); // null when it was disconnected meanwhile
        $provider = $integration ? Providers::find($integration->provider) : null;
        if (! $integration || ! $provider) {
            $sync->forceFill(['status' => 'failed', 'error' => 'The connection was removed.', 'finished_at' => now()])->save();

            return;
        }

        $stats = ['fetched' => $sync->fetched, 'created' => $sync->created, 'updated' => $sync->updated, 'skipped' => $sync->skipped, 'exported' => $sync->exported, 'export_failed' => $sync->export_failed];
        $cursor = $sync->cursor;

        // direction: import (platform → People), export (People → platform) or both. The import runs first; the export phase's cursor is "x:<last person id>".
        $direction = $provider->supportsExport() ? $integration->setting('direction', 'import') : 'import';
        $exports = in_array($direction, ['export', 'both'], true);
        $imports = $direction !== 'export';

        try {
            if ($imports && ! str_starts_with((string) $cursor, 'x:')) {
                $importer = new Importer($integration, $sync->id);
                for ($page = 0; $page < self::PAGES_PER_JOB; $page++) {
                    [$customers, $next] = $provider->customers($integration, $cursor, $sync->since);
                    $importer->import($customers, $stats);
                    $cursor = $next;
                    if ($cursor === null) {
                        break;
                    }
                }
            } elseif (! $imports && $cursor === null) {
                $cursor = 'x:0'; // send only: straight to the export phase
            }
            if ($cursor === null && $exports) {
                $cursor = 'x:0'; // the import is done: now send people
                $sync->forceFill($stats + ['cursor' => $cursor])->save();
                static::dispatch($sync->id);

                return;
            }
            if (str_starts_with((string) $cursor, 'x:')) {
                $cursor = $this->export($integration, $provider, $sync, $stats, (int) substr($cursor, 2));
            }
            $sync->forceFill($stats + ['cursor' => $cursor])->save();

            if ($cursor !== null) {
                static::dispatch($sync->id); // the next chunk

                return;
            }
            $sync->forceFill(['status' => 'success', 'cursor' => null, 'error' => $stats['export_failed'] ? $sync->error : null, 'finished_at' => now()])->save(); // a hint about refused people is kept
            $settings = $integration->settings ?? [];
            if ($exports && ! empty($settings['export_all'])) {
                unset($settings['export_all']); // everyone has now been sent: from here on only people who changed
                $integration->settings = $settings;
            }
            $integration->forceFill(['status' => 'connected', 'last_error' => null, 'last_synced_at' => $sync->started_at])->save();
        } catch (Throwable $e) {
            $message = $e instanceof IntegrationException ? $e->getMessage() : 'Something went wrong while importing. '.mb_substr($e->getMessage(), 0, 200);
            report($e);
            $sync->forceFill($stats + ['status' => 'failed', 'error' => $message, 'finished_at' => now()])->save();
            $integration->forceFill(['status' => 'error', 'last_error' => $message])->save();
        }
    }

    /** Writes each person's outcome into their sync history, and the id the platform gave them. */
    protected function recordExport(Integration $integration, IntegrationSync $sync, $people, array $results): void
    {
        foreach ($people as $person) {
            $r = $results[$person->id] ?? null;
            if (! $r) {
                continue;
            }
            $o = ['integration_id' => $integration->id, 'external_id' => $r['external_id'] ?? '', 'label' => $integration->name, 'sync_id' => $sync->id, 'direction' => 'out', 'message' => $r['message'] ?? null];
            if ($r['status'] === 'sent') {
                PersonSources::seen($person, $integration->provider, $o + ['action' => 'SENT']);
            } else {
                PersonSources::event($person, $integration->provider, $r['status'] === 'failed' ? 'FAILED' : 'SKIPPED', $o); // refused or nothing to do: no id to remember
            }
        }
    }

    /** People sent per request to the platform. */
    protected const EXPORT_BATCH = 100;

    /**
     * Sends a few batches of People (everyone with an email, or only those in the chosen list; after the first sync only people changed since).
     * Returns the next cursor, or null when everyone has been sent.
     */
    protected function export(Integration $integration, $provider, IntegrationSync $sync, array &$stats, int $afterId): ?string
    {
        // who is sent: the people of any chosen list, segment or tag (they add up); nothing chosen = everyone with an email address
        $audience = $integration->setting('export_audience') ?: (($l = $integration->setting('export_list_id')) ? ['lists' => [$l]] : []);
        $lists = (array) ($audience['lists'] ?? []);
        $tags = (array) ($audience['tags'] ?? []);
        $segments = \App\Models\Segment::withoutGlobalScope('company')->where('company_id', $integration->company_id)->whereIn('id', (array) ($audience['segments'] ?? []))->get();
        for ($batch = 0; $batch < self::PAGES_PER_JOB; $batch++) {
            $people = Person::withoutGlobalScope('company')->where('company_id', $integration->company_id)->where('id', '>', $afterId)
                ->whereNotNull('email')->where('email', '!=', '')
                ->when($sync->since && ! $integration->setting('export_all'), fn ($q) => $q->where('updated_at', '>=', $sync->since)) // export_all: everyone, once
                ->when($audience, fn ($q) => $q->where(function ($any) use ($lists, $tags, $segments, $integration) {
                    $any->whereRaw('1 = 0'); // each chosen kind adds its people
                    if ($lists) {
                        $any->orWhereHas('lists', fn ($l) => $l->whereIn('people_lists.id', $lists));
                    }
                    if ($tags) {
                        $any->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $tags));
                    }
                    foreach ($segments as $segment) {
                        $any->orWhereIn('people.id', \App\Services\SegmentQuery::forSegment($segment)->select('people.id'));
                    }
                }))
                ->orderBy('id')->limit(self::EXPORT_BATCH)->get();
            if ($people->isEmpty()) {
                return null;
            }

            $result = $provider->export($integration, $people);
            $this->recordExport($integration, $sync, $people, $result['results'] ?? []);
            $stats['exported'] += $result['sent'];
            $stats['export_failed'] += $result['failed'];
            $stats['skipped'] += $result['skipped'];
            if ($result['error'] && $stats['export_failed'] === $result['failed']) {
                $sync->forceFill(['error' => 'Some people were refused: '.mb_substr($result['error'], 0, 300)])->save(); // the first refusal, as a hint
            }
            $afterId = $people->last()->id;
            if ($people->count() < self::EXPORT_BATCH) {
                return null;
            }
        }

        return 'x:'.$afterId;
    }
}
