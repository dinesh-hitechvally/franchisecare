<?php

namespace App\Models\Concerns;

use App\Models\Campaign;
use App\Models\Person;

/** What EmailLog and SmsLog share: the campaign / person they belong to, and the "real" statistics scope. */
trait IsSendLog
{
    public static function bootIsSendLog(): void
    {
        // test sends and staff notifications are in the send history but never count in statistics (the history list lifts this scope)
        static::addGlobalScope('real', fn ($q) => $q->whereNotIn($q->getModel()->getTable().'.source', ['test', 'notification']));
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
