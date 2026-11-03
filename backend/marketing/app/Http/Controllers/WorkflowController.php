<?php

namespace App\Http\Controllers;

use App\Models\AutomationHistory;
use App\Models\PeopleList;
use App\Models\Form;
use App\Models\Tag;
use App\Models\Template;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\Automation;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index()
    {
        return Workflow::withCount([
            'runs as running_count' => fn ($q) => $q->where('status', 'running'),
            'runs as completed_count' => fn ($q) => $q->where('status', 'completed'),
            'runs as failed_count' => fn ($q) => $q->where('status', 'failed'),
        ])->latest()->get();
    }

    public function store(Request $request)
    {
        return response(Workflow::create($this->validated($request) + ['status' => 'draft']), 201);
    }

    public function update(Request $request, Workflow $workflow)
    {
        $workflow->update($this->validated($request));

        return $workflow;
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->delete();

        return response()->noContent();
    }

    /** draft/paused -> active, active -> paused. */
    public function toggle(Workflow $workflow)
    {
        if ($workflow->status === 'active') {
            $workflow->forceFill(['status' => 'paused'])->save();
        } else {
            abort_if(empty($workflow->steps), 422, 'Add at least one step before activating.');
            $workflow->forceFill(['status' => 'active'])->save();
        }

        return $workflow;
    }

    /** Catalogue of trigger types and which workflows use each. */
    public function triggers()
    {
        $workflows = Workflow::get(['id', 'name', 'status', 'trigger_type', 'trigger_config'])->groupBy('trigger_type');

        return collect(Automation::TRIGGERS)->map(fn ($t, $key) => [
            'type' => $key, 'group' => $t['group'], 'label' => $t['label'], 'description' => $t['description'], 'config' => $t['config'],
            'workflows' => ($workflows[$key] ?? collect())->values(),
        ])->values();
    }

    public function runs(Request $request)
    {
        return WorkflowRun::with('workflow:id,name', 'person:id,first_name,last_name,email')
            ->when($request->query('workflow_id'), fn ($q, $v) => $q->where('workflow_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('id')->paginate(25);
    }

    public function cancelRun(WorkflowRun $run)
    {
        abort_unless($run->status === 'running', 422, 'Only running items can be cancelled.');
        $run->update(['status' => 'cancelled', 'completed_at' => now(), 'locked_until' => null, 'next_run_at' => null]);
        Automation::log($run, -1, 'cancelled', 'done', 'Cancelled manually');

        return $run;
    }

    /** Step-by-step audit trail. */
    public function history(Request $request)
    {
        return AutomationHistory::with('workflow:id,name', 'person:id,first_name,last_name,email')
            ->when($request->query('workflow_id'), fn ($q, $v) => $q->where('workflow_id', $v))
            ->when($request->query('run_id'), fn ($q, $v) => $q->where('workflow_run_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('id')->paginate(40);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|in:'.implode(',', array_keys(Automation::TRIGGERS)),
            'trigger_config' => 'nullable|array',
            'trigger_config.list_id' => 'nullable|integer',
            'trigger_config.tag_id' => 'nullable|integer',
            'trigger_config.form_id' => 'nullable|integer',
            'trigger_config.url' => 'nullable|string|max:500',
            'trigger_config.field_id' => 'nullable|integer',
            'trigger_config.days' => 'nullable|integer|min:0|max:3650',
            'trigger_config.when' => 'nullable|in:before,on,after',
            'trigger_config.yearly' => 'nullable|boolean',
            'steps' => 'present|array|max:30',
            'steps.*.type' => 'required|in:'.implode(',', Automation::STEPS),
            'steps.*.template_id' => 'nullable|integer',
            'steps.*.body' => 'nullable|string|max:1600',
            'steps.*.subject' => 'nullable|string|max:255',
            'steps.*.amount' => 'nullable|integer|min:0|max:3650',
            'steps.*.unit' => 'nullable|in:minutes,hours,days',
            'steps.*.tag_id' => 'nullable|integer',
            'steps.*.list_id' => 'nullable|integer',
        ]);

        // every id must belong to this company
        $cfg = $data['trigger_config'] ?? [];
        abort_if(! empty($cfg['list_id']) && ! PeopleList::whereKey($cfg['list_id'])->exists(), 422, 'Trigger list not found.');
        abort_if(! empty($cfg['tag_id']) && ! Tag::whereKey($cfg['tag_id'])->exists(), 422, 'Trigger tag not found.');
        abort_if(! empty($cfg['form_id']) && ! Form::whereKey($cfg['form_id'])->exists(), 422, 'Trigger form not found.');
        if ($data['trigger_type'] === 'date_reached') {
            abort_unless(! empty($cfg['field_id']) && \App\Models\PersonField::whereKey($cfg['field_id'])->where('type', 'date')->exists(), 422, 'Choose the date field this workflow follows (a custom field of the type Date).');
            abort_if(($cfg['when'] ?? 'on') !== 'on' && (int) ($cfg['days'] ?? 0) < 1, 422, 'Say how many days before or after the date it should start.');
        }
        abort_if($data['trigger_type'] === 'inactive' && (int) ($cfg['days'] ?? 0) < 1, 422, 'Say after how many days without an opened email the workflow starts.');

        foreach ($data['steps'] as $i => $s) {
            $n = $i + 1;
            switch ($s['type']) {
                case 'send_email':
                case 'send_sms':
                    $want = $s['type'] === 'send_email' ? 'email' : 'sms';
                    abort_if(empty($s['template_id']) && empty($s['body']), 422, "Step {$n}: choose a template".($want === 'sms' ? ' or write a message' : '').'.');
                    abort_if(! empty($s['template_id']) && ! Template::where('type', $want)->whereKey($s['template_id'])->exists(), 422, "Step {$n}: template not found.");
                    break;
                case 'wait':
                    abort_if(! isset($s['amount'], $s['unit']), 422, "Step {$n}: set how long to wait.");
                    break;
                case 'add_tag':
                case 'remove_tag':
                    abort_if(empty($s['tag_id']) || ! Tag::whereKey($s['tag_id'])->exists(), 422, "Step {$n}: choose a tag.");
                    break;
                case 'add_to_list':
                    abort_if(empty($s['list_id']) || ! PeopleList::whereKey($s['list_id'])->exists(), 422, "Step {$n}: choose a list.");
                    break;
            }
        }

        return $data;
    }
}
