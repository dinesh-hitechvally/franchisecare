<?php

namespace App\Services;

use App\Models\Contact;

/** One place that flips a contact's email/SMS subscription, so timestamps and webhooks stay consistent. */
class Subscriptions
{
    public static function set(Contact $contact, string $channel, bool $on): bool
    {
        $col = $channel === 'sms' ? 'sms_subscribed' : 'email_subscribed';
        if ((bool) $contact->{$col} === $on) {
            return false;
        }

        $contact->forceFill([$col => $on, $channel.'_unsubscribed_at' => $on ? null : now()])->save();

        if (! $on) {
            WebhookDispatcher::dispatch($contact->company_id, 'contact.unsubscribed', Automation::contactPayload($contact) + ['channel' => $channel]);
        }

        return true;
    }
}
