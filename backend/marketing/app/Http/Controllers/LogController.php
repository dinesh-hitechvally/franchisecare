<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\EmailLog;
use App\Models\SmsLog;
use App\Models\Template;
use App\Services\SendErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    /**
     * The send history: every email (email_logs) and SMS (sms_logs), including test sends and staff notifications,
     * newest first. The two tables are read together; filter by channel to read just one.
     */
    public function index(Request $request)
    {
        $company = $request->user()->company_id;
        $channels = in_array($request->query('channel'), ['email', 'sms'], true) ? [$request->query('channel')] : ['email', 'sms'];

        $selects = array_map(function ($channel) use ($request, $company) {
            $email = $channel === 'email';

            return DB::table($email ? 'email_logs' : 'sms_logs')->where('company_id', $company)->whereNull('deleted_at')
                ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
                ->when($request->query('source'), fn ($q, $v) => $q->where('source', $v))
                ->when($request->query('service'), fn ($q, $v) => $q->where('service', $v))
                ->when($request->query('campaign_id'), fn ($q, $v) => $q->where('campaign_id', $v))
                ->when($request->query('from'), fn ($q, $v) => $q->where('created_at', '>=', $v.' 00:00:00'))
                ->when($request->query('to'), fn ($q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
                ->when($request->query('search'), fn ($q, $v) => $q->where(fn ($w) => $w->where('recipient', 'like', "%$v%")->orWhere($email ? 'subject' : 'body', 'like', "%$v%")))
                ->selectRaw("'{$channel}' as channel, id, campaign_id, recipient, ".($email ? 'subject' : 'NULL as subject').', status, source, service, error, sent_at, '
                    .($email ? 'opened_at, clicks' : 'NULL as opened_at, 0 as clicks').', created_at');
        }, $channels);

        $union = array_shift($selects);
        foreach ($selects as $s) {
            $union->unionAll($s);
        }

        $page = DB::query()->fromSub($union, 'h')->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(min(50, max(5, (int) $request->query('per_page', 30))));

        $names = Campaign::whereIn('id', $page->getCollection()->pluck('campaign_id')->filter()->unique())->pluck('name', 'id');
        $page->getCollection()->transform(function ($r) use ($names) {
            foreach (['sent_at', 'opened_at', 'created_at'] as $k) {
                $r->{$k} = $r->{$k} ? Carbon::parse($r->{$k})->toISOString() : null; // ISO with a zone, like the models give
            }
            $r->campaign = $r->campaign_id && isset($names[$r->campaign_id]) ? ['id' => $r->campaign_id, 'name' => $names[$r->campaign_id]] : null;

            return $r;
        });

        return $page;
    }

    /** One message in full. Emails and texts live in different tables, so the channel is part of the address. */
    public function show(string $channel, int $id)
    {
        abort_unless(in_array($channel, ['email', 'sms'], true), 404);
        $model = $channel === 'sms' ? SmsLog::class : EmailLog::class;

        $log = $model::withoutGlobalScope('real')->with('campaign:id,name')->findOrFail($id);
        $log->setAttribute('hint', SendErrors::forLog($channel, $log->error, $log->service)); // what to do about the error, when we know

        return $log;
    }

    public function dashboard()
    {
        $stat = fn ($model) => [
            'sent' => $model::where('status', 'sent')->count(),
            'failed' => $model::where('status', 'failed')->count(),
        ];
        $emailSent = max(1, EmailLog::where('status', 'sent')->count());

        return [
            'contacts' => Contact::count(),
            'campaigns' => Campaign::count(),
            'email' => $stat(EmailLog::class) + [
                'open_rate' => round(EmailLog::whereNotNull('opened_at')->count() / $emailSent * 100, 1),
                'clicked' => EmailLog::where('clicks', '>', 0)->count(),
            ],
            'sms' => $stat(SmsLog::class),
            'lists' => ContactList::count(),
            'templates' => Template::count(),
            'unsubscribed' => Contact::where(fn ($q) => $q->where('email_subscribed', false)->orWhere('sms_subscribed', false))->count(),
            'drafts' => Campaign::where('status', 'draft')->count(),
            'scheduled' => Campaign::where('status', 'scheduled')->count(),
            'daily' => $this->daily(),
            'upcoming' => Campaign::with('list:id,name')->where('status', 'scheduled')->orderBy('scheduled_at')->limit(5)
                ->get(['id', 'name', 'type', 'contact_list_id', 'scheduled_at']),
            'recent_logs' => EmailLog::latest('id')->limit(5)->get(['id', 'recipient', 'status', 'created_at'])
                ->concat(SmsLog::latest('id')->limit(5)->get(['id', 'recipient', 'status', 'created_at']))
                ->sortByDesc('created_at')->take(5)->values(),
            'recent_campaigns' => Campaign::latest()->limit(5)->get(['id', 'name', 'type', 'status', 'created_at']),
        ];
    }

    /** Messages sent per day for the last 14 days (dashboard bar chart), emails and texts together. */
    protected function daily(): array
    {
        $since = now()->subDays(13)->startOfDay();
        $counts = EmailLog::where('status', 'sent')->where('sent_at', '>=', $since)->get(['sent_at'])
            ->concat(SmsLog::where('status', 'sent')->where('sent_at', '>=', $since)->get(['sent_at']))
            ->groupBy(fn ($l) => $l->sent_at->toDateString())->map->count();

        return collect(range(13, 0))->map(fn ($i) => $counts[now()->subDays($i)->toDateString()] ?? 0)->all();
    }
}
