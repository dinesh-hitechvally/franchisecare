<?php

namespace App\Models\Concerns;

use App\Services\History;
use Illuminate\Database\Eloquent\Model;

/**
 * Mirrors the model's table: after every create, update, delete or restore a full copy of the row is written to
 * "<table>_history" (see App\Services\History).
 *
 * A model may define auditLabel() (readable name of the row) and auditCompanyId() (for rows without a company_id column).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => History::record('created', $m));

        static::updated(function (Model $m) {
            $changed = array_keys($m->getChanges());
            if (! array_diff($changed, History::NOISE)) {
                return; // only counters / bookkeeping moved
            }
            History::record('updated', $m, array_values(array_diff($changed, ['updated_at']))); // the timestamp always moves; it says nothing
        });

        static::deleted(function (Model $m) {
            $forced = method_exists($m, 'isForceDeleting') && $m->isForceDeleting() && ! ($m instanceof \Illuminate\Database\Eloquent\Relations\Pivot);
            History::record($forced ? 'purged' : 'deleted', $m);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => History::record('restored', $m));
        }
    }
}
