<?php

namespace App\Http\Controllers;

use App\Services\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Read-only view of the "<table>_history" mirrors. There is deliberately no way to edit or delete a copy. */
class HistoryController extends Controller
{
    /** Every kind of record that has history, for the filters. */
    public function types()
    {
        return collect(History::TYPES)->keys()->values();
    }

    /**
     * One feed across all tables (or one table with ?type=), newest first. By default only the changes the signed-in person made
     * ("My activity"); with ?all=1 the changes of everyone in the company (the timeline of one kind of record, e.g. all pages).
     */
    public function index(Request $request)
    {
        $company = $request->user()->company_id;
        $types = $request->query('type') ? [$this->type($request->query('type'))] : array_keys(History::TYPES);

        $me = $request->user()->id;
        // only for one named kind of record, and never for people or company settings: "My activity" stays personal
        $everyone = $request->boolean('all') && $request->query('type') && ! in_array($request->query('type'), ['user', 'company'], true);
        $selects = array_map(function ($type) use ($request, $company, $me, $everyone) {
            $table = History::historyTable(History::TYPES[$type]);
            $hasId = in_array('id', History::mirrored(History::TYPES[$type]), true);

            return DB::table($table)->where('history_company_id', $company)->when(! $everyone, fn ($q) => $q->where('changed_by', $me))
                ->when($request->query('id') && $hasId, fn ($q) => $q->where('id', (int) $request->query('id')))
                ->when($request->query('action'), fn ($q, $v) => $q->where('change_action', $v))
                ->when($request->query('source'), fn ($q, $v) => $q->where('change_source', $v))
                ->when($request->query('search'), fn ($q, $v) => $q->where('record_label', 'like', "%$v%"))
                ->when($request->query('from'), fn ($q, $v) => $q->where('changed_at', '>=', $v.' 00:00:00'))
                ->when($request->query('to'), fn ($q, $v) => $q->where('changed_at', '<=', $v.' 23:59:59'))
                ->selectRaw("'$type' as type, history_id, ".($hasId ? 'id' : 'NULL').' as row_id, change_action, record_label, changed_by_name, change_source, change_ip, changed_at, changed_fields');
        }, $types);

        $union = array_shift($selects);
        foreach ($selects as $s) {
            $union->unionAll($s);
        }

        $page = DB::query()->fromSub($union, 'h')->orderByDesc('changed_at')->orderByDesc('history_id')->paginate(40);
        $page->getCollection()->transform(fn ($r) => $this->withChanges($r));

        return $page;
    }

    /** The full history of one record (a contact's also includes its list and tag changes). */
    public function record(Request $request, string $type, int $id)
    {
        $type = $this->type($type);
        $company = $request->user()->company_id;
        $table = History::historyTable(History::TYPES[$type]);

        $rows = DB::table($table)->where('history_company_id', $company)->where('id', $id)
            ->selectRaw("'$type' as type, history_id, id as row_id, change_action, record_label, changed_by_name, change_source, change_ip, changed_at, changed_fields")
            ->get();

        if ($type === 'contact') {
            foreach (['list_membership' => 'contact_list_members', 'contact_tag' => 'contact_tag'] as $t => $src) {
                $rows = $rows->concat(DB::table(History::historyTable($src))->where('history_company_id', $company)->where('contact_id', $id)
                    ->selectRaw("'$t' as type, history_id, NULL as row_id, change_action, record_label, changed_by_name, change_source, change_ip, changed_at, changed_fields")->get());
            }
        }

        return $rows->sortByDesc(fn ($r) => $r->changed_at.sprintf('%012d', $r->history_id))->values()->take(300)
            ->map(fn ($r) => $this->withChanges($r))->values();
    }

    /** What a saved copy said, to show as a preview and as HTML. Only for records that have content; read-only like the rest. */
    public const VIEWABLE = [
        'template' => ['name', 'type', 'subject', 'body', 'design', 'attachments'],
        'campaign' => ['name', 'type', 'subject', 'body', 'design', 'attachments'],
        'landing_page' => ['name', 'slug', 'title', 'html', 'design'],
        'form' => ['name', 'type', 'title', 'description', 'fields', 'style', 'button_text', 'success_message', 'existing_message', 'redirect_url', 'save_contact', 'subscribe',
            'notify_emails', 'autoreply', 'autoreply_subject', 'autoreply_body', 'autoreply_attachments', 'list_ids', 'tag_ids', 'active'],
    ];

    public function entry(Request $request, string $type, int $historyId)
    {
        $type = $this->type($type);
        abort_unless(isset(self::VIEWABLE[$type]), 404, 'This kind of record has no content to preview.');

        $row = DB::table(History::historyTable(History::TYPES[$type]))->where('history_id', $historyId)->where('history_company_id', $request->user()->company_id)->first();
        abort_unless($row, 404);

        return [
            'type' => $type, 'history_id' => $row->history_id, 'row_id' => $row->id,
            'exists' => $this->modelFor($type)::query()->whereKey($row->id)->exists(), // false once it has been deleted: there is no editor to open
            'current_status' => $type === 'campaign' ? \App\Models\Campaign::query()->whereKey($row->id)->value('status') : null, // a campaign that has gone out can no longer be edited
 'change_action' => $row->change_action, 'changed_at' => $row->changed_at, 'changed_by_name' => $row->changed_by_name,
            'data' => $this->content($type, $row),
        ];
    }

    /** The saved copy as the record itself would give it: its own model casts the JSON columns, so the editor can open it. */
    protected function content(string $type, object $row): array
    {
        $model = (new ($this->modelFor($type)))->setRawAttributes((array) $row, true); // as stored: the model's own casts then decode the JSON columns

        $data = $model->only(self::VIEWABLE[$type]);
        if ($type === 'form') {
            $data['fields'] = $model->builderFields(); // the same shape the builder gets for a live form
            $data['style'] = $model->resolvedStyle();
            $data['notify_emails'] = $model->notify_emails ?? [];
        }

        return $data;
    }

    protected function modelFor(string $type): string
    {
        return match ($type) {
            'template' => \App\Models\Template::class,
            'campaign' => \App\Models\Campaign::class,
            'landing_page' => \App\Models\LandingPage::class,
            'form' => \App\Models\Form::class,
        };
    }

    protected function type(string $type): string
    {
        abort_unless(isset(History::TYPES[$type]), 404, 'Unknown kind of record.');

        return $type;
    }

    /** Adds `changes`: [column => {old, new}] compared with the previous copy of the same record. */
    protected function withChanges(object $r): object
    {
        $full = DB::table(History::historyTable(History::TYPES[$r->type]))->where('history_id', $r->history_id)->first();
        $r->changes = $full ? (object) History::changes($r->type, $full) : (object) [];
        $r->changed_fields = $r->changed_fields ? json_decode($r->changed_fields, true) : [];

        return $r;
    }
}
