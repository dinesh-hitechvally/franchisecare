<?php

namespace App\Services;

use App\Jobs\ProcessWorkflowRunJob;
use App\Models\AutomationHistory;
use App\Models\Person;
use App\Models\PersonField;
use App\Models\PersonFieldValue;
use App\Models\EmailLog;
use App\Models\Workflow;
use App\Models\WorkflowRun;

class Automation
{
    /** type => label, what it does, which setting it needs (list | tag | form | url | date | days), and the group it is shown under. */
    public const TRIGGERS = [
        'person_created' => ['group' => 'People', 'label' => 'Person is added', 'description' => 'Starts when a new person is added (manually, by import, form or API).', 'config' => null],
        'list_joined' => ['group' => 'People', 'label' => 'Person joins a list', 'description' => 'Starts when a person is added to the chosen list (or any list).', 'config' => 'list'],
        'list_left' => ['group' => 'People', 'label' => 'Person leaves a list', 'description' => 'Starts when a person is taken off the chosen list (or any list).', 'config' => 'list'],
        'tag_added' => ['group' => 'People', 'label' => 'Tag is added', 'description' => 'Starts when the chosen tag (or any tag) is added to a person.', 'config' => 'tag'],
        'tag_removed' => ['group' => 'People', 'label' => 'Tag is removed', 'description' => 'Starts when the chosen tag (or any tag) is taken off a person.', 'config' => 'tag'],
        'form_submitted' => ['group' => 'People', 'label' => 'Form is submitted', 'description' => 'Starts when someone submits the chosen sign-up form (or any form).', 'config' => 'form'],
        'unsubscribed' => ['group' => 'People', 'label' => 'Person unsubscribes', 'description' => 'Starts when someone unsubscribes. Workflows never email or text unsubscribed people, so use it to tag them or move them between lists.', 'config' => null],
        'link_clicked' => ['group' => 'Email activity', 'label' => 'Person clicks a link', 'description' => 'Starts when someone clicks a link in one of your emails: any link, or only links containing the text you choose.', 'config' => 'url'],
        'email_opened' => ['group' => 'Email activity', 'label' => 'Person opens an email', 'description' => 'Starts the first time someone opens each email you send. Opens are counted when the email’s image loads, so some are missed.', 'config' => null],
        'date_reached' => ['group' => 'Time', 'label' => 'A date arrives', 'description' => 'Starts on a date saved on the person (a birthday, a renewal date), or some days before or after it, once or every year. Checked once a day.', 'config' => 'date'],
        'inactive' => ['group' => 'Time', 'label' => 'Person has not opened emails', 'description' => 'Starts when someone has received your emails but opened none for the number of days you choose. Checked once a day, and not repeated for the same person within that time.', 'config' => 'days'],
    ];

    public const STEPS = ['send_email', 'send_sms', 'wait', 'add_tag', 'remove_tag', 'add_to_list'];

    /** Start every active workflow that listens to $type (and whose settings match $ctx) for this person. */
    public static function fire(string $type, Person $person, array $ctx = []): void
    {
        $workflows = Workflow::withoutGlobalScope('company')
            ->where('company_id', $person->company_id)->where('status', 'active')->where('trigger_type', $type)->get();

        foreach ($workflows as $wf) {
            $cfg = $wf->trigger_config ?? [];
            $want = match ($type) {
                'list_joined', 'list_left' => $cfg['list_id'] ?? null,
                'tag_added', 'tag_removed' => $cfg['tag_id'] ?? null,
                'form_submitted' => $cfg['form_id'] ?? null,
                default => null,
            };
            if ($want && (int) $want !== (int) ($ctx['id'] ?? null)) {
                continue;
            }
            // "a link containing ...": only the clicks on a matching address
            $needle = $type === 'link_clicked' ? trim((string) ($cfg['url'] ?? '')) : '';
            if ($needle !== '' && ! str_contains(mb_strtolower((string) ($ctx['url'] ?? '')), mb_strtolower($needle))) {
                continue;
            }
            self::startFor($wf, $person);
        }
    }

    /** Puts one person into one workflow: a run that the queue then works through. False when they are already inside it. */
    public static function startFor(Workflow $wf, Person $person): bool
    {
        // a person can't be inside the same workflow twice at once
        if (WorkflowRun::withoutGlobalScope('company')->where('workflow_id', $wf->id)->where('person_id', $person->id)->where('status', 'running')->exists()) {
            return false;
        }

        $run = WorkflowRun::withoutGlobalScope('company')->create([
            'company_id' => $wf->company_id, 'workflow_id' => $wf->id, 'person_id' => $person->id,
            'status' => 'running', 'current_step' => 0, 'next_run_at' => now(), 'started_at' => now(),
        ]);
        self::log($run, -1, 'trigger', 'done', self::TRIGGERS[$wf->trigger_type]['label'] ?? $wf->trigger_type);
        ProcessWorkflowRunJob::dispatch($run->id);

        return true;
    }

    /**
     * The checks that depend on time, run once a day: "a date arrives" and "has not opened emails".
     * Returns how many people were put into a workflow.
     */
    public static function runTimeTriggers(): int
    {
        return self::dateTriggers() + self::inactiveTriggers();
    }

    protected static function dateTriggers(): int
    {
        $started = 0;
        $today = now()->startOfDay();
        foreach (Workflow::withoutGlobalScope('company')->where('status', 'active')->where('trigger_type', 'date_reached')->get() as $wf) {
            $cfg = $wf->trigger_config ?? [];
            $field = PersonField::withoutGlobalScope('company')->where('company_id', $wf->company_id)->where('type', 'date')->find($cfg['field_id'] ?? 0);
            if (! $field) {
                continue;
            }
            // it starts when today = their date + offset (days before = a negative offset); so their date has to be today - offset
            $days = (int) ($cfg['days'] ?? 0);
            $offset = match ($cfg['when'] ?? 'on') { 'before' => -$days, 'after' => $days, default => 0 };
            $target = $today->copy()->subDays($offset);

            // dates are matched as YYYY-MM-DD; "every year" ignores the year
            $values = PersonFieldValue::where('person_field_id', $field->id);
            ! empty($cfg['yearly']) ? $values->where('value', 'like', '____-'.$target->format('m-d')) : $values->where('value', $target->toDateString());

            foreach ($values->pluck('person_id')->chunk(500) as $ids) {
                foreach (Person::withoutGlobalScope('company')->where('company_id', $wf->company_id)->whereIn('id', $ids)->get() as $person) {
                    // the same person is never started twice on one day, however often this runs
                    if (WorkflowRun::withoutGlobalScope('company')->where('workflow_id', $wf->id)->where('person_id', $person->id)->where('started_at', '>=', $today)->exists()) {
                        continue;
                    }
                    $started += self::startFor($wf, $person) ? 1 : 0;
                }
            }
        }

        return $started;
    }

    protected static function inactiveTriggers(): int
    {
        $started = 0;
        foreach (Workflow::withoutGlobalScope('company')->where('status', 'active')->where('trigger_type', 'inactive')->get() as $wf) {
            $days = max(1, (int) (($wf->trigger_config ?? [])['days'] ?? 30));
            $cut = now()->subDays($days)->toDateTimeString();

            // received our email at least $days ago, and opened none since then (or ever)
            $ids = EmailLog::withoutGlobalScope('company')->where('company_id', $wf->company_id)->where('status', 'sent')->whereNotNull('person_id')
                ->selectRaw('person_id')->groupBy('person_id')
                ->havingRaw('MIN(sent_at) < ?', [$cut])->havingRaw('(MAX(opened_at) IS NULL OR MAX(opened_at) < ?)', [$cut])
                ->pluck('person_id');

            foreach ($ids->chunk(500) as $chunk) {
                // only people who can still be emailed, and not started again within the same number of days
                foreach (Person::withoutGlobalScope('company')->where('company_id', $wf->company_id)->where('is_subscribed', true)->whereIn('id', $chunk)->get() as $person) {
                    if (WorkflowRun::withoutGlobalScope('company')->where('workflow_id', $wf->id)->where('person_id', $person->id)->where('started_at', '>=', $cut)->exists()) {
                        continue;
                    }
                    $started += self::startFor($wf, $person) ? 1 : 0;
                }
            }
        }

        return $started;
    }

    /** Convenience for controllers: fire the events implied by a person change. */
    public static function personChanged(Person $person, bool $created, array $newListIds = [], array $newTagIds = []): void
    {
        if ($created) {
            self::fire('person_created', $person);
            WebhookDispatcher::dispatch($person->company_id, 'person.created', self::personPayload($person));
        }
        foreach ($newListIds as $id) {
            self::fire('list_joined', $person, ['id' => $id]);
        }
        foreach ($newTagIds as $id) {
            self::fire('tag_added', $person, ['id' => $id]);
        }
    }

    public static function personPayload(Person $c): array
    {
        return $c->only('id', 'first_name', 'last_name', 'email', 'phone', 'is_subscribed', 'source');
    }

    public static function log(WorkflowRun $run, int $index, string $type, string $status, ?string $detail = null): void
    {
        AutomationHistory::withoutGlobalScope('company')->create([
            'company_id' => $run->company_id, 'workflow_id' => $run->workflow_id, 'workflow_run_id' => $run->id,
            'person_id' => $run->person_id, 'step_index' => $index, 'step_type' => $type, 'status' => $status, 'detail' => $detail,
        ]);
    }
}
