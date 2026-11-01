<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Data mirroring: every change to a row of a tracked table writes a full copy of that row into
 * "<table>_history", together with who changed it, from where, and which columns differ from the previous copy.
 *
 * Models opt in with the Auditable trait. Recording never breaks the action being recorded: failures are only reported.
 */
class History
{
    /** type shown in the app => source table (each has a "<table>_history" mirror) */
    public const TYPES = [
        'company' => 'companies',
        'user' => 'users',
        'contact' => 'contacts',
        'contact_list' => 'contact_lists',
        'list_membership' => 'contact_list_members',
        'tag' => 'tags',
        'contact_tag' => 'contact_tag',
        'segment' => 'segments',
        'template' => 'templates',
        'layout' => 'layouts',
        'campaign' => 'campaigns',
        'form' => 'forms',
        'landing_page' => 'landing_pages',
        'workflow' => 'workflows',
        'webhook' => 'webhooks',
        'contact_field' => 'contact_fields',
        'contact_field_value' => 'contact_field_values',
        'form_submission' => 'form_submissions',
        'media' => 'media',
    ];

    /** Columns that change all the time without meaning anything: a change to only these is not recorded. */
    public const NOISE = ['updated_at', 'views', 'submissions', 'locked_until'];

    /** Column names whose values are secrets: the copy keeps that a value exists, not the value. */
    protected const SECRET = '/pass|secret|token|api_?key|access_?key|private/i';

    /** Columns every history table has besides the mirrored ones. */
    public const META = ['history_id', 'history_company_id', 'change_action', 'changed_fields', 'record_label', 'changed_by', 'changed_by_name', 'change_source', 'change_ip', 'changed_at'];

    protected static array $columns = [];

    public static function historyTable(string $table): string
    {
        return $table.'_history';
    }

    /** Columns of the mirrored table (history table minus its own bookkeeping columns). */
    public static function mirrored(string $table): array
    {
        return self::$columns[$table] ??= array_values(array_diff(Schema::getColumnListing(self::historyTable($table)), self::META));
    }

    /** @param  list<string>|null  $changed  columns that changed (updates only) */
    public static function record(string $action, Model $m, ?array $changed = null, ?\App\Models\User $actor = null): void
    {
        try {
            $company = self::companyOf($m);
            if (! $company) {
                return;
            }
            $table = $m->getTable();
            $user = $actor ?? Auth::user();

            DB::table(self::historyTable($table))->insert(self::snapshot($m) + [
                'history_company_id' => $company,
                'change_action' => $action,
                'changed_fields' => $changed ? json_encode(array_values($changed)) : null,
                'record_label' => mb_substr(self::labelOf($m), 0, 250),
                'changed_by' => $user?->id, 'changed_by_name' => $user?->name,
                'change_source' => self::source(),
                'change_ip' => request()->ip(),
                'changed_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** The row as stored right now (after the change), or the model's own attributes when the row is gone. */
    protected static function snapshot(Model $m): array
    {
        $row = null;
        if ($m->getKey() !== null && ! ($m instanceof \Illuminate\Database\Eloquent\Relations\Pivot)) {
            $row = DB::table($m->getTable())->where($m->getKeyName(), $m->getKey())->first();
        }
        $row = $row ? (array) $row : $m->getAttributes();

        $out = [];
        foreach (self::mirrored($m->getTable()) as $col) {
            $v = $row[$col] ?? null;
            if ($v instanceof \DateTimeInterface) {
                $v = $v->format('Y-m-d H:i:s');
            } elseif (is_array($v)) {
                $v = json_encode($v);
            }
            $out[$col] = self::hide($col, $v);
        }

        return $out;
    }

    protected static function hide(string $col, mixed $v): mixed
    {
        if ($v === null || $v === '') {
            return $v;
        }
        if (preg_match(self::SECRET, $col)) {
            return '••••••';
        }
        if (is_string($v) && $v[0] === '{' && is_array($decoded = json_decode($v, true))) { // e.g. company settings holding SMTP / SMS keys
            return json_encode(self::maskArray($decoded));
        }

        return $v;
    }

    protected static function maskArray(array $a): array
    {
        foreach ($a as $k => $v) {
            if (is_string($k) && preg_match(self::SECRET, $k) && $v !== null && $v !== '') {
                $a[$k] = '••••••';
            } elseif (is_array($v)) {
                $a[$k] = self::maskArray($v);
            }
        }

        return $a;
    }

    public static function source(): string
    {
        $r = request();
        if ($r->is('api/public/*', 'f/*', 'p/*', 'api/unsubscribe/*', 'api/t/*')) {
            return 'public';
        }
        if ($r->is('api/v1/*')) {
            return 'api';
        }

        return Auth::user() || $r->is('api/login', 'api/logout') ? 'dashboard' : 'system';
    }

    public static function companyOf(Model $m): ?int
    {
        if (method_exists($m, 'auditCompanyId')) {
            return $m->auditCompanyId();
        }

        return $m instanceof \App\Models\Company ? $m->getKey() : $m->company_id;
    }

    public static function typeOf(Model $m): string
    {
        return array_search($m->getTable(), self::TYPES, true) ?: $m->getTable();
    }

    public static function labelOf(Model $m): string
    {
        if (method_exists($m, 'auditLabel')) {
            return $m->auditLabel();
        }
        foreach (['name', 'title', 'email', 'label'] as $k) {
            if (filled($m->getAttribute($k))) {
                return (string) $m->getAttribute($k);
            }
        }

        return '#'.$m->getKey();
    }

    /**
     * The columns of one history row that differ from the previous copy of the same record:
     * [column => ['old' => …, 'new' => …]]. A first copy lists what the record started with; a pivot has no earlier copy.
     */
    public static function changes(string $type, object $row): array
    {
        $table = self::TYPES[$type];
        $cols = in_array($table, ['contact_list_members', 'contact_tag'], true) ? [] : array_diff(self::mirrored($table), ['id', 'company_id', 'created_at', 'updated_at', 'deleted_at']);

        $prev = null;
        if (isset($row->id) && $row->id !== null) {
            $prev = DB::table(self::historyTable($table))->where('id', $row->id)->where('history_id', '<', $row->history_id)->orderByDesc('history_id')->first();
        }
        $fields = $row->changed_fields ? (json_decode($row->changed_fields, true) ?: []) : [];

        $out = [];
        foreach ($cols as $c) {
            $new = $row->{$c} ?? null;
            $old = $prev->{$c} ?? null;
            if ($prev ? $old === $new : $new === null) {
                continue;
            }
            if ($prev && $row->change_action === 'updated' && $fields && ! in_array($c, $fields, true)) {
                continue;
            }
            $out[$c] = ['old' => self::short($old), 'new' => self::short($new)];
        }

        return $out;
    }

    protected static function short(mixed $v): mixed
    {
        return is_string($v) && mb_strlen($v) > 300 ? mb_substr($v, 0, 300).'… ('.number_format(mb_strlen($v)).' characters)' : $v;
    }
}
