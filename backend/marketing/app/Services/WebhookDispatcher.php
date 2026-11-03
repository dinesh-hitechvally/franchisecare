<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Webhook;

class WebhookDispatcher
{
    public const EVENTS = [
        'person.created' => 'A person is added',
        'person.unsubscribed' => 'A person unsubscribes',
        'campaign.sent' => 'A campaign finishes sending',
        'form.submitted' => 'A sign-up form is submitted',
        'message.failed' => 'A message fails to send',
        'workflow.completed' => 'A person finishes a workflow',
    ];

    /** Queue a delivery for every active webhook of the company that listens to $event. */
    public static function dispatch(int $companyId, string $event, array $data): void
    {
        Webhook::withoutGlobalScope('company')->where('company_id', $companyId)->where('active', true)->get()
            ->filter(fn ($w) => in_array('*', $w->events ?? []) || in_array($event, $w->events ?? []))
            ->each(fn ($w) => SendWebhookJob::dispatch($w->id, $event, $data));
    }
}
