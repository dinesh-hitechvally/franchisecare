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

    /** One feed across all tables (or one table with ?type=), newest first: only the changes the signed-in person made. */
    public function index(Request $request)
    {
        $company = $request->user()->company_id;
        $types = $request->query('type') ? [$this->type($request->query('type'))] : array_keys(History::TYPES);

        $me = $request->user()->id;
        $selects = array_map(function ($type) use ($request, $company, $me) {
            $table = History::historyTable(History::TYPES[$type]);
            $hasId = in_array('id', History::mirrored(History::TYPES[$type]), true);

            return DB::table($table)->where('history_company_id', $company)->where('changed_by', $me)
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
