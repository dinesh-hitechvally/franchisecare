<?php

namespace App\Services;

use App\Models\Person;

/** One place that flips a person's newsletter subscription, so the date and webhooks stay consistent. */
class Subscriptions
{
    public static function set(Person $person, bool $on): bool
    {
        if ((bool) $person->is_subscribed === $on) {
            return false;
        }

        $person->forceFill(['is_subscribed' => $on, 'subscribed_at' => $on ? now() : null])->save();

        if (! $on) {
            WebhookDispatcher::dispatch($person->company_id, 'person.unsubscribed', Automation::personPayload($person));
            Automation::fire('unsubscribed', $person);
        }

        return true;
    }
}
