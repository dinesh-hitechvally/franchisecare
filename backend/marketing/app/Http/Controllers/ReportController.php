<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReportMath;
use App\Models\Campaign;
use App\Models\ClickEvent;
use App\Models\Person;
use App\Models\PeopleList;
use App\Models\Form;
use App\Models\LandingPage;
use App\Models\EmailLog;
use App\Models\SmsLog;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/** Campaign reports (per campaign) and the three analytics dashboards. */
class ReportController extends Controller
{
    use ReportMath;

    // ------------------------------------------------------------------ campaign reports

    public function campaigns(Request $request)
    {
        $rows = Campaign::whereIn('status', ['sending', 'sent', 'failed'])
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->when(in_array($request->query('status'), ['sending', 'sent', 'failed'], true), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->query('search'), fn ($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when($request->query('from'), fn ($q, $v) => $q->where('created_at', '>=', $v.' 00:00:00'))
            ->when($request->query('to'), fn ($q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->withLogCounts()->latest()->paginate(20);

        $rows->getCollection()->transform(function ($c) {
            $c->logTotals();
            $c->open_rate = $this->pct($c->opened_count, $c->sent_count);
            $c->click_rate = $this->pct($c->clicked_count, $c->sent_count);

            return $c;
        });

        return $rows;
    }

    public function campaign(Campaign $campaign)
    {
        // emails and texts are in different tables; only emails can be opened or clicked
        $isEmail = $campaign->type !== 'sms';
        $logs = $campaign->logModel()::where('campaign_id', $campaign->id);
        $count = fn ($s) => (clone $logs)->where('status', $s)->count();
        $sent = $count('sent');
        $opened = $isEmail ? (clone $logs)->whereNotNull('opened_at')->count() : 0;
        $clickers = $isEmail ? (clone $logs)->where('clicks', '>', 0)->count() : 0;
        $clicks = $isEmail ? (int) (clone $logs)->sum('clicks') : 0;

        $span = max(1, min(14, (int) ceil(($campaign->started_at ?? $campaign->created_at)->diffInDays(now())) + 1));
        $opens = $isEmail ? (clone $logs)->selectRaw('DATE(opened_at) as d, COUNT(*) as c')->whereNotNull('opened_at')
            ->where('opened_at', '>=', now()->subDays($span - 1)->startOfDay())->groupBy('d')->get() : collect();
        $clickRows = ClickEvent::where('company_id', $campaign->company_id)->where('campaign_id', $campaign->id)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->where('created_at', '>=', now()->subDays($span - 1)->startOfDay())->groupBy('d')->get();

        return [
            'campaign' => $campaign->load('list:id,name', 'segment:id,name'),
            'stats' => [
                'recipients' => (clone $logs)->count(), 'sent' => $sent, 'failed' => $count('failed'), 'skipped' => $count('skipped') + (int) $campaign->audience_skipped, 'logged' => $count('logged'),
                'opened' => $opened, 'open_rate' => $this->pct($opened, $sent),
                'clickers' => $clickers, 'clicks' => $clicks, 'click_rate' => $this->pct($clickers, $sent),
                'click_to_open' => $this->pct($clickers, $opened),
            ],
            'timeline' => ['opens' => $this->series($opens, $span), 'clicks' => $this->series($clickRows, $span)],
            'top_links' => ClickEvent::where('company_id', $campaign->company_id)->where('campaign_id', $campaign->id)
                ->selectRaw('url, COUNT(*) as clicks, COUNT(DISTINCT email_log_id) as unique_clicks')->groupBy('url')->orderByDesc('clicks')->limit(10)->get(),
        ];
    }

    /**
     * Every recipient of a campaign with how it went, one page at a time: delivered, logged only, failed, queued (from the message log),
     * and skipped (people with no email address / phone number). Skipped people have no log rows, so they are worked out from the
     * campaign's audience and listed after the logged messages.
     */
    public function deliveries(Request $request, Campaign $campaign)
    {
        $isEmail = $campaign->type !== 'sms';
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));
        $per = 20;
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $per;
        $col = $isEmail ? 'people.email' : 'people.phone';

        $logs = $campaign->logModel()::where('campaign_id', $campaign->id)
            ->when(in_array($status, ['sent', 'logged', 'failed', 'queued'], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('recipient', 'like', "%$search%")
                ->orWhereIn('person_id', Person::where(fn ($c) => $c->where('first_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%"))->select('id'))));
        $left = $campaign->audienceQuery()->where('people.is_subscribed', true)->where(fn ($q) => $q->whereNull($col)->orWhere($col, ''))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('first_name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%")));

        $wantLogs = $status !== 'skipped';
        $wantLeft = $status === '' || $status === 'skipped';
        $logsTotal = $wantLogs ? (clone $logs)->count() : 0;
        $leftTotal = $wantLeft ? (clone $left)->count() : 0;

        $items = collect();
        if ($wantLogs && $offset < $logsTotal) {
            $rows = (clone $logs)->orderBy('id')->offset($offset)->limit($per)->get(['recipient', 'status', 'error', 'person_id', 'sent_at']);
            $names = Person::whereIn('id', $rows->pluck('person_id')->filter())->get(['id', 'first_name', 'last_name'])
                ->mapWithKeys(fn ($c) => [$c->id => trim($c->first_name.' '.$c->last_name)]);
            $items = $rows->map(fn ($l) => ['name' => $names[$l->person_id] ?? '', 'recipient' => $l->recipient, 'status' => $l->status, 'error' => $l->error, 'at' => $l->sent_at]);
        }
        if ($wantLeft && $items->count() < $per) {
            $skip = max(0, $offset - $logsTotal);
            $items = $items->concat((clone $left)->orderBy('people.id')->offset($skip)->limit($per - $items->count())->get(['people.id', 'first_name', 'last_name', 'email', 'phone'])
                ->map(fn ($c) => [
                    'name' => trim($c->first_name.' '.$c->last_name), 'recipient' => '', 'status' => 'skipped', 'at' => null,
                    'error' => $isEmail ? 'No email address found for this person' : 'No phone number found for this person',
                ]));
        }

        return new LengthAwarePaginator($items->values(), $logsTotal + $leftTotal, $per, $page);
    }

    /** CSV of every recipient with delivery + engagement status. */
    public function export(Campaign $campaign)
    {
        $name = 'campaign-'.$campaign->id.'-recipients.csv';

        return response()->streamDownload(function () use ($campaign) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['recipient', 'status', 'sent_at', 'opened_at', 'clicks', 'error']);
            $campaign->logModel()::where('campaign_id', $campaign->id)->orderBy('id')->chunkById(500, function ($rows) use ($out) {
                foreach ($rows as $l) {
                    fputcsv($out, [self::safe($l->recipient), $l->status, $l->sent_at, $l->opened_at, $l->clicks, self::safe((string) $l->error)]);
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    /** Neutralise spreadsheet formula injection in exported cells. */
    protected static function safe(string $v): string
    {
        return $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'".$v : $v;
    }

    // ------------------------------------------------------------------ analytics

    public function campaignAnalytics(Request $request)
    {
        $days = in_array((int) $request->query('days'), [7, 14, 30, 60, 90], true) ? (int) $request->query('days') : 30;
        $from = now()->subDays($days - 1)->startOfDay();

        $sentEmails = EmailLog::where('status', 'sent')->where('sent_at', '>=', $from);
        $sentTexts = SmsLog::where('status', 'sent')->where('sent_at', '>=', $from);
        $emailSent = (clone $sentEmails)->count();
        $sentCount = $emailSent + (clone $sentTexts)->count();
        $opened = EmailLog::whereNotNull('opened_at')->where('opened_at', '>=', $from)->count();
        $clickers = EmailLog::where('clicks', '>', 0)->where('sent_at', '>=', $from)->count();
        $biz = $request->user()->company_id;

        $top = Campaign::where('type', 'email')->where('status', 'sent')->where('completed_at', '>=', $from)
            ->withCount(['emailLogs as sent_count' => fn ($q) => $q->where('status', 'sent'), 'emailLogs as opened_count' => fn ($q) => $q->whereNotNull('opened_at')])
            ->get()->filter(fn ($c) => $c->sent_count > 0)
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'sent' => $c->sent_count, 'open_rate' => $this->pct($c->opened_count, $c->sent_count)])
            ->sortByDesc('open_rate')->take(5)->values();

        return [
            'days' => $days,
            'totals' => [
                'sent' => $sentCount, 'emails' => $emailSent, 'sms' => $sentCount - $emailSent,
                'failed' => EmailLog::where('status', 'failed')->where('created_at', '>=', $from)->count() + SmsLog::where('status', 'failed')->where('created_at', '>=', $from)->count(),
                'open_rate' => $this->pct($opened, $emailSent), 'click_rate' => $this->pct($clickers, $emailSent),
                'campaigns' => Campaign::where('created_at', '>=', $from)->count(),
            ],
            'daily' => [
                'sent' => $this->series(collect($sentEmails->selectRaw('DATE(sent_at) as d, COUNT(*) as c')->groupBy('d')->get()->all())->concat($sentTexts->selectRaw('DATE(sent_at) as d, COUNT(*) as c')->groupBy('d')->get()->all())
                    ->groupBy('d')->map(fn ($g, $d) => ['d' => $d, 'c' => $g->sum('c')])->values(), $days),
                'opened' => $this->series(EmailLog::whereNotNull('opened_at')->where('opened_at', '>=', $from)->selectRaw('DATE(opened_at) as d, COUNT(*) as c')->groupBy('d')->get(), $days),
                'clicked' => $this->series(ClickEvent::where('company_id', $biz)->where('created_at', '>=', $from)->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->get(), $days),
            ],
            'top_campaigns' => $top,
            'top_links' => ClickEvent::where('company_id', $biz)->where('created_at', '>=', $from)
                ->selectRaw('url, COUNT(*) as clicks')->groupBy('url')->orderByDesc('clicks')->limit(8)->get(),
        ];
    }

    public function personAnalytics(Request $request)
    {
        $total = Person::count();
        $since = now()->subDays(29)->startOfDay();
        $engaged = EmailLog::whereNotNull('opened_at')->where('opened_at', '>=', now()->subDays(30))->distinct()->count('person_id');
        $received = EmailLog::where('status', 'sent')->distinct()->count('person_id');
        $everOpened = EmailLog::whereNotNull('opened_at')->distinct()->count('person_id');

        return [
            'total' => $total,
            'new_30d' => Person::where('created_at', '>=', $since)->count(),
            'growth' => $this->series(Person::where('created_at', '>=', $since)->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->get(), 30),
            'subscriptions' => [
                'subscribed' => Person::where('is_subscribed', true)->count(),
                'unsubscribed' => Person::where('is_subscribed', false)->count(),
            ],
            'by_source' => Person::selectRaw('source as label, COUNT(*) as value')->groupBy('source')->orderByDesc('value')->get(),
            'by_list' => PeopleList::withCount('people')->orderByDesc('people_count')->limit(8)->get()->map(fn ($l) => ['label' => $l->name, 'value' => $l->people_count]),
            'by_tag' => Tag::withCount('people')->orderByDesc('people_count')->limit(8)->get()->map(fn ($t) => ['label' => $t->name, 'value' => $t->people_count]),
            'engagement' => [
                'engaged_30d' => $engaged,
                'opened_before' => max(0, $everOpened - $engaged),
                'never_opened' => max(0, $received - $everOpened),
                'not_emailed' => max(0, $total - $received),
            ],
        ];
    }

    public function conversionAnalytics(Request $request)
    {
        $forms = Form::orderByDesc('submissions')->get(['id', 'name', 'views', 'submissions', 'active'])
            ->map(fn ($f) => $f->setAttribute('rate', $this->pct($f->submissions, $f->views)));
        $formSubs = Form::pluck('submissions', 'id');
        $pages = LandingPage::with('form:id,name')->orderByDesc('views')->get()->map(fn ($p) => [
            'id' => $p->id, 'name' => $p->name, 'published' => $p->published, 'views' => $p->views, 'form' => $p->form?->name,
            'submissions' => (int) ($formSubs[$p->form_id] ?? 0),
            'rate' => $p->form_id ? $this->pct((int) ($formSubs[$p->form_id] ?? 0), $p->views) : null,
        ]);
        $since = now()->subDays(29)->startOfDay();

        return [
            'forms' => $forms,
            'pages' => $pages,
            'totals' => [
                'form_views' => (int) $forms->sum('views'), 'form_submissions' => (int) $forms->sum('submissions'),
                'page_views' => (int) $pages->sum('views'),
                'overall_rate' => $this->pct((int) $forms->sum('submissions'), (int) $forms->sum('views')),
            ],
            'signups_30d' => $this->series(Person::whereIn('source', ['form', 'api'])->where('created_at', '>=', $since)->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->get(), 30),
            'by_source_30d' => Person::where('created_at', '>=', $since)->selectRaw('source as label, COUNT(*) as value')->groupBy('source')->orderByDesc('value')->get(),
        ];
    }
}
