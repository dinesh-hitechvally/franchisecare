<?php

namespace App\Jobs;

use App\Models\Person;
use App\Models\Tag;
use App\Models\Template;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\Automation;
use App\Services\WebhookDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Runs a person through a workflow until it finishes or reaches a "wait" step. */
class ProcessWorkflowRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $runId) {}

    public function handle(): void
    {
        // atomic lease so the scheduler and a queue retry can never process the same run twice
        $leased = WorkflowRun::withoutGlobalScope('company')->whereKey($this->runId)->where('status', 'running')
            ->where(fn ($q) => $q->whereNull('locked_until')->orWhere('locked_until', '<', now()))
            ->update(['locked_until' => now()->addMinutes(2)]);
        if (! $leased) {
            return;
        }

        $run = WorkflowRun::withoutGlobalScope('company')->findOrFail($this->runId);
        $wf = Workflow::withoutGlobalScope('company')->find($run->workflow_id);
        $person = Person::withoutGlobalScope('company')->find($run->person_id);

        if (! $wf) {
            $this->fail($run, 'Workflow was deleted');

            return;
        }
        if ($wf->status !== 'active') { // paused: leave the run where it is
            $run->update(['locked_until' => null]);

            return;
        }
        if (! $person) {
            $this->fail($run, 'Person no longer exists');

            return;
        }

        $steps = $wf->steps ?? [];
        while ($run->current_step < count($steps)) {
            $i = $run->current_step;
            $step = $steps[$i];

            try {
                if (($step['type'] ?? '') === 'wait') {
                    $secs = $this->waitSeconds($step);
                    $run->current_step = $i + 1;
                    $run->next_run_at = now()->addSeconds($secs);
                    $run->locked_until = null;
                    $run->save();
                    Automation::log($run, $i, 'wait', 'waiting', 'Wait '.($step['amount'] ?? 0).' '.($step['unit'] ?? 'minutes'));
                    if ($secs > 0) {
                        return; // the scheduler picks it up when next_run_at arrives
                    }
                    $run->locked_until = now()->addMinutes(2);
                    $run->save();

                    continue;
                }
                [$status, $detail] = $this->perform($step, $wf, $person, $run);
                Automation::log($run, $i, $step['type'], $status, $detail);
            } catch (Throwable $e) {
                Automation::log($run, $i, $step['type'] ?? 'unknown', 'failed', mb_substr($e->getMessage(), 0, 500));
                $this->fail($run, $e->getMessage());

                return;
            }

            $run->current_step = $i + 1;
            $run->save();
        }

        $run->update(['status' => 'completed', 'completed_at' => now(), 'locked_until' => null, 'next_run_at' => null]);
        Automation::log($run, count($steps), 'end', 'done', 'Workflow completed');
        WebhookDispatcher::dispatch($run->company_id, 'workflow.completed', ['workflow' => $wf->name, 'person' => Automation::personPayload($person)]);
    }

    protected function fail(WorkflowRun $run, string $msg): void
    {
        $run->update(['status' => 'failed', 'error' => mb_substr($msg, 0, 1000), 'locked_until' => null, 'completed_at' => now()]);
    }

    protected function waitSeconds(array $step): int
    {
        $n = max(0, (int) ($step['amount'] ?? 0));

        return $n * match ($step['unit'] ?? 'minutes') {
            'days' => 86400, 'hours' => 3600, default => 60,
        };
    }

    /** @return array{0:string,1:string} [status, detail] */
    protected function perform(array $step, Workflow $wf, Person $person, WorkflowRun $run): array
    {
        switch ($step['type']) {
            case 'send_email':
            case 'send_sms':
                $email = $step['type'] === 'send_email';
                $to = $email ? $person->email : $person->phone;
                if (! $to) {
                    return ['skipped', 'Person has no '.($email ? 'email' : 'phone')];
                }
                if (! $person->is_subscribed) {
                    return ['skipped', 'Person is unsubscribed'];
                }
                $tpl = ! empty($step['template_id']) ? Template::withoutGlobalScope('company')->where('company_id', $wf->company_id)->find($step['template_id']) : null;
                $subject = $tpl->subject ?? ($step['subject'] ?? '');
                $body = $tpl->body ?? ($step['body'] ?? '');
                if ($email && $tpl) {
                    $body .= \App\Services\Attachments::marker($tpl->attachments); // the template's files go out with it
                }
                if ($body === '') {
                    return ['failed', 'Step has no template or message'];
                }
                $log = ($email ? \App\Models\EmailLog::class : \App\Models\SmsLog::class)::create([
                    'company_id' => $wf->company_id, 'workflow_id' => $wf->id, 'person_id' => $person->id,
                    'source' => 'automation', 'recipient' => $to, 'body' => $body, 'status' => 'queued',
                ] + ($email ? ['subject' => $subject] : []));
                SendMessageJob::dispatch($log->id, $email ? 'email' : 'sms');

                return ['done', ($email ? 'Email' : 'SMS').' queued to '.$to];

            case 'add_tag':
            case 'remove_tag':
                $tag = Tag::withoutGlobalScope('company')->where('company_id', $wf->company_id)->find($step['tag_id'] ?? 0);
                if (! $tag) {
                    return ['failed', 'Tag no longer exists'];
                }
                $step['type'] === 'add_tag' ? $person->tags()->syncWithoutDetaching([$tag->id]) : ($person->tags()->detach($tag->id) && \App\Services\Automation::fire('tag_removed', $person, ['id' => $tag->id])); // only when it was really on them, so a workflow that removes the tag it listens for cannot loop

                return ['done', ($step['type'] === 'add_tag' ? 'Added tag ' : 'Removed tag ').$tag->name];

            case 'add_to_list':
                $list = \App\Models\PeopleList::withoutGlobalScope('company')->where('company_id', $wf->company_id)->find($step['list_id'] ?? 0);
                if (! $list) {
                    return ['failed', 'List no longer exists'];
                }
                $person->lists()->syncWithoutDetaching([$list->id]);

                return ['done', 'Added to list '.$list->name];
        }

        return ['failed', 'Unknown step type'];
    }
}
