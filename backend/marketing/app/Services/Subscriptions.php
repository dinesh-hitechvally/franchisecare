<?php

namespace App\Services;

use App\Models\Contact;

/** One place that flips a contact's newsletter subscription, so the date and webhooks stay consistent. */
class Subscriptions
{
    public static function set(Contact $contact, bool $on): bool
    {
        if ((bool) $contact->is_subscribed === $on) {
            return false;
        }

        $contact->forceFill(['is_subscribed' => $on, 'subscribed_at' => $on ? now() : null])->save();

        if (! $on) {
            WebhookDispatcher::dispatch($contact->company_id, 'contact.unsubscribed', Automation::contactPayload($contact));
            Automation::fire('unsubscribed', $contact);
        }

        return true;
    }
}
