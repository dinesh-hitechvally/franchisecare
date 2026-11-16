<?php

namespace App\Services;

use App\Models\Person;
use App\Models\PersonSource;
use App\Models\PersonSyncEvent;

/**
 * Remembers where each person comes from and which platforms they are on, with their id on each, and keeps their sync history.
 * It never breaks the thing being recorded: a failure here is reported, not thrown.
 */
class PersonSources
{
    /**
     * The person was seen on a platform (created from it, found on it, or sent to it). Options:
     *   integration_id, external_id, label, created (the person is new here), changed (an existing person was updated),
     *   direction (in|out), action (to log a specific event), message, sync_id.
     * A history line is written when the person is new, newly linked to this platform, changed, or when `action` says so.
     */
    public static function seen(Person $person, string $platform, array $o = []): void
    {
        try {
            $integrationId = (int) ($o['integration_id'] ?? 0);
            $externalId = mb_substr((string) ($o['external_id'] ?? ''), 0, 191);
            // a connection has one row per person (its id may be learned later); anything else has one per id (each form)
            $keys = ['person_id' => $person->id, 'platform' => $platform, 'integration_id' => $integrationId] + ($integrationId ? [] : ['external_id' => $externalId]);

            $row = PersonSource::withoutGlobalScope('company')->where($keys)->first() ?? new PersonSource;
            $isNew = ! $row->exists;
            $row->forceFill($keys + ['company_id' => $person->company_id]);
            if ($externalId !== '') {
                $row->external_id = $externalId;
            } elseif ($isNew) {
                $row->external_id = '';
            }
            if (! empty($o['label'])) {
                $row->label = mb_substr((string) $o['label'], 0, 255);
            }
            $row->first_seen_at ??= now();
            $row->last_synced_at = now();
            $row->save();

            $action = $o['action'] ?? ($isNew ? (! empty($o['created']) ? 'CREATED' : 'LINKED') : (! empty($o['changed']) ? 'UPDATED' : null));
            if ($action) {
                self::event($person, $platform, $action, $o + ['external_id' => $row->external_id]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** One line in the person's sync history. */
    public static function event(Person $person, string $platform, string $action, array $o = []): void
    {
        try {
            (new PersonSyncEvent)->forceFill([
                'company_id' => $person->company_id, 'person_id' => $person->id, 'platform' => $platform,
                'integration_id' => ($o['integration_id'] ?? null) ?: null, 'sync_id' => $o['sync_id'] ?? null,
                'direction' => strtoupper($o['direction'] ?? 'in'), 'action' => strtoupper($action),
                'external_id' => ($o['external_id'] ?? '') !== '' ? mb_substr((string) $o['external_id'], 0, 191) : null,
                'label' => ! empty($o['label']) ? mb_substr((string) $o['label'], 0, 255) : null,
                'message' => ! empty($o['message']) ? mb_substr((string) $o['message'], 0, 1000) : null,
            ])->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
