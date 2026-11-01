<?php

namespace App\Services;

use App\Jobs\ProcessWorkflowRunJob;
use App\Models\AutomationHistory;
use App\Models\Contact;
use App\Models\Workflow;
use App\Models\WorkflowRun;

class Automation
{
    public const TRIGGERS = [
        'contact_created' => ['label' => 'Contact is added', 'description' => 'Starts when a new contact is created (manually, by import, form or API).', 'config' => null],
        'list_joined' => ['label' => 'Contact joins a list', 'description' => 'Starts when a contact is added to the chosen list (or any list).', 'config' => 'list'],
        'tag_added' => ['label' => 'Tag is added', 'description' => 'Starts when the chosen tag (or any tag) is added to a contact.', 'config' => 'tag'],
        'form_submitted' => ['label' => 'Form is submitted', 'description' => 'Starts when someone submits the chosen sign-up form (or any form).', 'config' => 'form'],
    ];

    public const STEPS = ['send_email', 'send_sms', 'wait', 'add_tag', 'remove_tag', 'add_to_list'];

    /** Start every active workflow that listens to $type (and whose config matches $ctx) for this contact. */
    public static function fire(string $type, Contact $contact, array $ctx = []): void
    {
        $workflows = Workflow::withoutGlobalScope('company')
            ->where('company_id', $contact->company_id)->where('status', 'active')->where('trigger_type', $type)->get();

        foreach ($workflows as $wf) {
            $cfg = $wf->trigger_config ?? [];
            $want = match ($type) {
                'list_joined' => $cfg['list_id'] ?? null,
                'tag_added' => $cfg['tag_id'] ?? null,
                'form_submitted' => $cfg['form_id'] ?? null,
                default => null,
            };
            $got = $ctx['id'] ?? null;
            if ($want && (int) $want !== (int) $got) {
                continue;
            }
            // a contact can't be inside the same workflow twice at once
            if (WorkflowRun::withoutGlobalScope('company')->where('workflow_id', $wf->id)->where('contact_id', $contact->id)->where('status', 'running')->exists()) {
                continue;
            }

            $run = WorkflowRun::withoutGlobalScope('company')->create([
                'company_id' => $wf->company_id, 'workflow_id' => $wf->id, 'contact_id' => $contact->id,
                'status' => 'running', 'current_step' => 0, 'next_run_at' => now(), 'started_at' => now(),
            ]);
            self::log($run, -1, 'trigger', 'done', self::TRIGGERS[$type]['label']);
            ProcessWorkflowRunJob::dispatch($run->id);
        }
    }

    /** Convenience for controllers: fire the events implied by a contact change. */
    public static function contactChanged(Contact $contact, bool $created, array $newListIds = [], array $newTagIds = []): void
    {
        if ($created) {
            self::fire('contact_created', $contact);
            WebhookDispatcher::dispatch($contact->company_id, 'contact.created', self::contactPayload($contact));
        }
        foreach ($newListIds as $id) {
            self::fire('list_joined', $contact, ['id' => $id]);
        }
        foreach ($newTagIds as $id) {
            self::fire('tag_added', $contact, ['id' => $id]);
        }
    }

    public static function contactPayload(Contact $c): array
    {
        return $c->only('id', 'first_name', 'last_name', 'email', 'phone', 'email_subscribed', 'sms_subscribed', 'source');
    }

    public static function log(WorkflowRun $run, int $index, string $type, string $status, ?string $detail = null): void
    {
        AutomationHistory::withoutGlobalScope('company')->create([
            'company_id' => $run->company_id, 'workflow_id' => $run->workflow_id, 'workflow_run_id' => $run->id,
            'contact_id' => $run->contact_id, 'step_index' => $index, 'step_type' => $type, 'status' => $status, 'detail' => $detail,
        ]);
    }
}
