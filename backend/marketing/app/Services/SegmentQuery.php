<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns segment rules into a Contact query.
 * A rule is { field, op, value }; the segment's `match` (all|any) decides AND / OR.
 */
class SegmentQuery
{
    /** field => [label, type, ops] — also used by the UI rule builder. */
    public const FIELDS = [
        'is_subscribed' => ['Subscribed to newsletter', 'bool', ['is']],
        'has_email' => ['Has an email address', 'bool', ['is']],
        'has_phone' => ['Has a phone number', 'bool', ['is']],
        'tag' => ['Tag', 'tag', ['in', 'not_in']],
        'list' => ['List', 'list', ['in', 'not_in']],
        'first_name' => ['First name', 'text', ['equals', 'contains', 'not_contains']],
        'last_name' => ['Last name', 'text', ['equals', 'contains', 'not_contains']],
        'email' => ['Email', 'text', ['equals', 'contains', 'not_contains']],
        'email_domain' => ['Email domain', 'text', ['equals']],
        'phone' => ['Phone', 'text', ['equals', 'contains', 'starts_with']],
        'source' => ['Source', 'source', ['equals']],
        'created' => ['Date added', 'date', ['after', 'before']],
        'opened_recently' => ['Opened an email in the last N days', 'days', ['yes', 'no']],
    ];

    public static function query(array $rules, string $match, int $companyId): Builder
    {
        $q = Contact::withoutGlobalScope('company')->where('contacts.company_id', $companyId);
        $rules = array_values(array_filter($rules, fn ($r) => isset($r['field']) && isset(self::FIELDS[$r['field']])));

        if ($rules) {
            $bool = $match === 'any' ? 'or' : 'and';
            $q->where(function (Builder $g) use ($rules, $bool, $companyId) {
                foreach ($rules as $r) {
                    self::apply($g, $r, $bool, $companyId);
                }
            });
        }

        return $q;
    }

    protected static function apply(Builder $q, array $r, string $bool, int $companyId): void
    {
        $f = $r['field'];
        $op = $r['op'] ?? '';
        $v = $r['value'] ?? null;
        $has = $bool === 'or' ? 'orWhereHas' : 'whereHas';
        $doesnt = $bool === 'or' ? 'orWhereDoesntHave' : 'whereDoesntHave';
        $yes = filter_var($v, FILTER_VALIDATE_BOOLEAN);

        switch ($f) {
            case 'is_subscribed':
            case 'email_subscribed': // rules saved before there was a single newsletter subscription
            case 'sms_subscribed':
                $q->where('is_subscribed', '=', $yes, $bool);
                break;
            case 'has_email':
            case 'has_phone':
                $col = $f === 'has_email' ? 'email' : 'phone';
                $q->where(function ($w) use ($col, $yes) {
                    $yes ? $w->whereNotNull($col)->where($col, '!=', '') : $w->whereNull($col)->orWhere($col, '');
                }, null, null, $bool);
                break;
            case 'tag':
                $op === 'not_in'
                    ? $q->{$doesnt}('tags', fn ($t) => $t->where('tags.id', (int) $v))
                    : $q->{$has}('tags', fn ($t) => $t->where('tags.id', (int) $v));
                break;
            case 'list':
                $op === 'not_in'
                    ? $q->{$doesnt}('lists', fn ($t) => $t->where('contact_lists.id', (int) $v))
                    : $q->{$has}('lists', fn ($t) => $t->where('contact_lists.id', (int) $v));
                break;
            case 'first_name':
            case 'last_name':
            case 'email':
            case 'phone':
                $v = (string) $v;
                match ($op) {
                    'contains' => $q->where($f, 'like', '%'.$v.'%', $bool),
                    'not_contains' => $q->where(fn ($w) => $w->whereNull($f)->orWhere($f, 'not like', '%'.$v.'%'), null, null, $bool),
                    'starts_with' => $q->where($f, 'like', $v.'%', $bool),
                    default => $q->where($f, '=', $v, $bool),
                };
                break;
            case 'email_domain':
                $q->where('email', 'like', '%@'.ltrim((string) $v, '@'), $bool);
                break;
            case 'source':
                $q->where('source', '=', (string) $v, $bool);
                break;
            case 'created':
                $date = date_create((string) $v) ?: now();
                $op === 'before'
                    ? $q->where('contacts.created_at', '<', $date->format('Y-m-d 00:00:00'), $bool)
                    : $q->where('contacts.created_at', '>=', $date->format('Y-m-d 00:00:00'), $bool);
                break;
            case 'opened_recently':
                $since = now()->subDays(max(1, (int) $v));
                $sub = fn ($m) => $m->where('opened_at', '>=', $since);
                $op === 'no' ? $q->{$doesnt}('emailLogs', $sub) : $q->{$has}('emailLogs', $sub);
                break;
        }
    }

    public static function forSegment($segment): Builder
    {
        return self::query($segment->rules ?? [], $segment->match, $segment->company_id);
    }
}
