<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReportMath;
use App\Models\AutomationHistory;
use App\Models\Campaign;
use App\Models\ClickEvent;
use App\Models\Contact;
use App\Models\EmailLog;
use App\Models\FormSubmission;
use App\Models\SmsLog;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The analytics reports: overview, email, SMS, delivery & errors, automation.
 * (Contacts and forms/pages reports live in ReportController.) Every report takes ?days= (7, 14, 30, 60 or 90).
 */
class AnalyticsController extends Controller
{
    use ReportMath;

    protected function range(Request $request): array
    {
        $days = in_array((int) $request->query('days'), [7, 14, 30, 60, 90], true) ? (int) $request->query('days') : 30;

        return [$days, now()->subDays($days - 1)->startOfDay()];
    }

    /** Rows of {d, c}: how many rows of $query have $col on each day since $from. */
    protected function daily($query, string $col, Carbon $from): Collection
    {
        return $query->where($col, '>=', $from)->selectRaw("DATE($col) as d, COUNT(*) as c")->groupBy('d')->get()->map(fn ($r) => ['d' => $r->d, 'c' => (int) $r->c]);
    }

    protected function merged(Collection ...$lists): Collection
    {
        return collect($lists)->flatten(1)->groupBy('d')->map(fn ($g, $d) => ['d' => $d, 'c' => $g->sum('c')])->values();
    }

    /** status => count for a log model since $from. */
    protected function statuses(string $model, Carbon $from): array
    {
        return $model::where('created_at', '>=', $from)->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status')->map(fn ($c) => (int) $c)->all();
    }

    // ------------------------------------------------------------------ overview

    public function overview(Request $request)
    {
        [$days, $from] = $this->range($request);
        $prevFrom = $from->copy()->subDays($days);

        $sent = fn ($a, $b) => EmailLog::where('status', 'sent')->where('sent_at', '>=', $a)->where('sent_at', '<', $b)->count()
            + SmsLog::where('status', 'sent')->where('sent_at', '>=', $a)->where('sent_at', '<', $b)->count();
        $count = fn ($model, $col, $a, $b) => $model::where($col, '>=', $a)->where($col, '<', $b)->count();
        $end = now()->addSecond();

        $metric = fn ($now, $before) => ['value' => $now, 'prev' => $before, 'change' => $before > 0 ? round(($now - $before) / $before * 100) : null];

        $emailSent = EmailLog::where('status', 'sent')->where('sent_at', '>=', $from)->count();
        $opened = EmailLog::where('status', 'sent')->where('sent_at', '>=', $from)->whereNotNull('opened_at')->count();
        $clickers = EmailLog::where('status', 'sent')->where('sent_at', '>=', $from)->where('clicks', '>', 0)->count();

        return [
            'days' => $days,
            'metrics' => [
                'messages' => $metric($sent($from, $end), $sent($prevFrom, $from)),
                'new_contacts' => $metric($count(Contact::class, 'created_at', $from, $end), $count(Contact::class, 'created_at', $prevFrom, $from)),
                'submissions' => $metric($count(FormSubmission::class, 'created_at', $from, $end), $count(FormSubmission::class, 'created_at', $prevFrom, $from)),
                'automation_runs' => $metric($count(WorkflowRun::class, 'started_at', $from, $end), $count(WorkflowRun::class, 'started_at', $prevFrom, $from)),
            ],
            'totals' => [
                'emails' => $emailSent,
                'sms' => SmsLog::where('status', 'sent')->where('sent_at', '>=', $from)->count(),
                'failed' => EmailLog::where('status', 'failed')->where('created_at', '>=', $from)->count() + SmsLog::where('status', 'failed')->where('created_at', '>=', $from)->count(),
                'open_rate' => $this->pct($opened, $emailSent),
                'click_rate' => $this->pct($clickers, $emailSent),
                'contacts' => Contact::count(),
                'subscribed' => Contact::where('is_subscribed', true)->count(),
                'unsubscribed' => Contact::where('is_subscribed', false)->count(),
            ],
            'daily' => [
                'messages' => $this->series($this->merged($this->daily(EmailLog::where('status', 'sent'), 'sent_at', $from), $this->daily(SmsLog::where('status', 'sent'), 'sent_at', $from)), $days),
                'contacts' => $this->series($this->daily(Contact::query(), 'created_at', $from), $days),
                'submissions' => $this->series($this->daily(FormSubmission::query(), 'created_at', $from), $days),
            ],
        ];
    }

    // ------------------------------------------------------------------ email

    public function email(Request $request)
    {
        [$days, $from] = $this->range($request);
        $biz = $request->user()->company_id;

        $sentQ = fn () => EmailLog::where('status', 'sent')->where('sent_at', '>=', $from);
        $sent = $sentQ()->count();
        $opened = $sentQ()->whereNotNull('opened_at')->count();
        $clickers = $sentQ()->where('clicks', '>', 0)->count();

        $campaigns = Campaign::where('type', 'email')->whereHas('emailLogs', fn ($q) => $q->where('status', 'sent')->where('sent_at', '>=', $from))
            ->withCount([
                'emailLogs as sent_count' => fn ($q) => $q->where('status', 'sent')->where('sent_at', '>=', $from),
                'emailLogs as opened_count' => fn ($q) => $q->where('status', 'sent')->where('sent_at', '>=', $from)->whereNotNull('opened_at'),
                'emailLogs as clicked_count' => fn ($q) => $q->where('status', 'sent')->where('sent_at', '>=', $from)->where('clicks', '>', 0),
            ])->get()->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'sent' => $c->sent_count, 'opened' => $c->opened_count, 'clicked' => $c->clicked_count,
                'open_rate' => $this->pct($c->opened_count, $c->sent_count), 'click_rate' => $this->pct($c->clicked_count, $c->sent_count),
            ])->sortByDesc('sent')->take(15)->values();

        return [
            'days' => $days,
            'totals' => [
                'sent' => $sent, 'opened' => $opened, 'clicked' => $clickers,
                'open_rate' => $this->pct($opened, $sent), 'click_rate' => $this->pct($clickers, $sent),
                'total_clicks' => (int) $sentQ()->sum('clicks'),
                'failed' => EmailLog::where('status', 'failed')->where('created_at', '>=', $from)->count(),
                'skipped' => EmailLog::where('status', 'skipped')->where('created_at', '>=', $from)->count(),
            ],
            'daily' => [
                'sent' => $this->series($this->daily(EmailLog::where('status', 'sent'), 'sent_at', $from), $days),
                'opened' => $this->series($this->daily(EmailLog::whereNotNull('opened_at'), 'opened_at', $from), $days),
                'clicked' => $this->series($this->daily(ClickEvent::where('company_id', $biz), 'created_at', $from), $days),
            ],
            'campaigns' => $campaigns,
            'by_source' => $sentQ()->selectRaw('source as label, COUNT(*) as value')->groupBy('source')->orderByDesc('value')->get(),
            'top_links' => ClickEvent::where('company_id', $biz)->where('created_at', '>=', $from)->selectRaw('url, COUNT(*) as clicks')->groupBy('url')->orderByDesc('clicks')->limit(10)->get(),
        ];
    }

    // ------------------------------------------------------------------ sms

    public function sms(Request $request)
    {
        [$days, $from] = $this->range($request);
        $status = $this->statuses(SmsLog::class, $from);
        $sent = SmsLog::where('status', 'sent')->where('sent_at', '>=', $from)->count();

        $campaigns = Campaign::where('type', 'sms')->whereHas('smsLogs', fn ($q) => $q->where('created_at', '>=', $from))
            ->withCount([
                'smsLogs as sent_count' => fn ($q) => $q->where('status', 'sent')->where('created_at', '>=', $from),
                'smsLogs as failed_count' => fn ($q) => $q->where('status', 'failed')->where('created_at', '>=', $from),
                'smsLogs as skipped_count' => fn ($q) => $q->where('status', 'skipped')->where('created_at', '>=', $from),
            ])->get()->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'sent' => $c->sent_count, 'failed' => $c->failed_count, 'skipped' => $c->skipped_count,
                'delivery_rate' => $this->pct($c->sent_count, $c->sent_count + $c->failed_count),
            ])->sortByDesc('sent')->take(15)->values();

        return [
            'days' => $days,
            'totals' => [
                'sent' => $sent, 'failed' => $status['failed'] ?? 0, 'skipped' => $status['skipped'] ?? 0, 'logged' => $status['logged'] ?? 0,
                'success_rate' => $this->pct($sent, $sent + ($status['failed'] ?? 0)),
            ],
            'daily' => [
                'sent' => $this->series($this->daily(SmsLog::where('status', 'sent'), 'sent_at', $from), $days),
                'failed' => $this->series($this->daily(SmsLog::where('status', 'failed'), 'created_at', $from), $days),
            ],
            'campaigns' => $campaigns,
            'by_service' => SmsLog::where('status', 'sent')->where('sent_at', '>=', $from)->selectRaw('service as label, COUNT(*) as value')->groupBy('service')->orderByDesc('value')->get(),
            'by_source' => SmsLog::where('status', 'sent')->where('sent_at', '>=', $from)->selectRaw('source as label, COUNT(*) as value')->groupBy('source')->orderByDesc('value')->get(),
        ];
    }

    // ------------------------------------------------------------------ delivery & errors

    public function delivery(Request $request)
    {
        [$days, $from] = $this->range($request);

        // errors often name the person (an address, a number): blank those out so the same problem groups together
        $reasons = function (string $model, string $status) use ($from) {
            return $model::where('status', $status)->where('created_at', '>=', $from)->whereNotNull('error')->limit(5000)->pluck('error')
                ->map(fn ($e) => mb_substr(trim(preg_replace(['/[\w.+\-]+@[\w\-]+(\.[\w\-]+)+/', '/\+?\d[\d\s\-()]{6,}\d/', '/\s+/'], ['(address)', '(number)', ' '], $e)), 0, 90));
        };
        $group = fn (string $status) => collect(['email' => $reasons(EmailLog::class, $status), 'sms' => $reasons(SmsLog::class, $status)])
            ->flatMap(fn ($list, $channel) => $list->map(fn ($e) => ['reason' => $e, 'channel' => $channel]))
            ->groupBy('reason')->map(fn ($g, $r) => ['reason' => $r, 'count' => $g->count(), 'channels' => $g->pluck('channel')->unique()->values()])
            ->sortByDesc('count')->take(10)->values();

        $services = function (string $model, string $channel) use ($from) {
            return $model::where('created_at', '>=', $from)->selectRaw("service, SUM(status = 'sent') as sent, SUM(status = 'failed') as failed, SUM(status = 'skipped') as skipped, SUM(status = 'logged') as logged")
                ->groupBy('service')->get()->map(fn ($r) => ['channel' => $channel, 'service' => $r->service ?: 'default', 'sent' => (int) $r->sent, 'failed' => (int) $r->failed, 'skipped' => (int) $r->skipped, 'logged' => (int) $r->logged,
                    'success_rate' => $this->pct((int) $r->sent, (int) $r->sent + (int) $r->failed)]);
        };

        $recent = fn (string $model, string $channel) => $model::where('status', 'failed')->where('created_at', '>=', $from)->latest('id')->limit(10)->get(['id', 'recipient', 'error', 'service', 'created_at'])
            ->map(fn ($l) => ['channel' => $channel, 'to' => $l->recipient, 'error' => $l->error, 'service' => $l->service, 'when' => $l->created_at]);

        $email = $this->statuses(EmailLog::class, $from);
        $sms = $this->statuses(SmsLog::class, $from);
        $sum = fn ($k) => ($email[$k] ?? 0) + ($sms[$k] ?? 0);

        return [
            'days' => $days,
            'totals' => ['sent' => $sum('sent'), 'failed' => $sum('failed'), 'skipped' => $sum('skipped'), 'queued' => $sum('queued'), 'logged' => $sum('logged'),
                'success_rate' => $this->pct($sum('sent'), $sum('sent') + $sum('failed'))],
            'by_channel' => ['email' => $email, 'sms' => $sms],
            'daily_failed' => $this->series($this->merged($this->daily(EmailLog::where('status', 'failed'), 'created_at', $from), $this->daily(SmsLog::where('status', 'failed'), 'created_at', $from)), $days),
            'failed_reasons' => $group('failed'),
            'skipped_reasons' => $group('skipped'),
            'by_service' => $services(EmailLog::class, 'email')->concat($services(SmsLog::class, 'sms'))->sortByDesc(fn ($r) => $r['sent'] + $r['failed'])->values(),
            'recent_failures' => $recent(EmailLog::class, 'email')->concat($recent(SmsLog::class, 'sms'))->sortByDesc('when')->take(10)->values(),
        ];
    }

    // ------------------------------------------------------------------ automation

    public function automation(Request $request)
    {
        [$days, $from] = $this->range($request);

        $runs = WorkflowRun::where('started_at', '>=', $from)->selectRaw('workflow_id, status, COUNT(*) as c')->groupBy('workflow_id', 'status')->get();
        $byStatus = $runs->groupBy('status')->map(fn ($g) => (int) $g->sum('c'));
        $mail = EmailLog::where('source', 'automation')->where('status', 'sent')->where('sent_at', '>=', $from)->selectRaw('workflow_id, COUNT(*) as c')->groupBy('workflow_id')->pluck('c', 'workflow_id');
        $texts = SmsLog::where('source', 'automation')->where('status', 'sent')->where('sent_at', '>=', $from)->selectRaw('workflow_id, COUNT(*) as c')->groupBy('workflow_id')->pluck('c', 'workflow_id');

        $workflows = Workflow::orderBy('name')->get(['id', 'name', 'status', 'trigger_type'])->map(function ($w) use ($runs, $mail, $texts) {
            $mine = $runs->where('workflow_id', $w->id)->pluck('c', 'status');
            $started = (int) $mine->sum();

            return [
                'id' => $w->id, 'name' => $w->name, 'status' => $w->status, 'trigger' => $w->trigger_type,
                'started' => $started, 'completed' => (int) ($mine['completed'] ?? 0), 'running' => (int) ($mine['running'] ?? 0), 'failed' => (int) ($mine['failed'] ?? 0),
                'completion_rate' => $this->pct((int) ($mine['completed'] ?? 0), $started),
                'emails' => (int) ($mail[$w->id] ?? 0), 'sms' => (int) ($texts[$w->id] ?? 0),
            ];
        })->sortByDesc('started')->values();

        return [
            'days' => $days,
            'totals' => [
                'workflows' => Workflow::count(), 'active' => Workflow::where('status', 'active')->count(),
                'started' => (int) $byStatus->sum(), 'completed' => (int) ($byStatus['completed'] ?? 0), 'running' => (int) ($byStatus['running'] ?? 0), 'failed' => (int) ($byStatus['failed'] ?? 0),
                'emails' => (int) $mail->sum(), 'sms' => (int) $texts->sum(),
            ],
            'daily' => $this->series($this->daily(WorkflowRun::query(), 'started_at', $from), $days),
            'steps' => AutomationHistory::where('created_at', '>=', $from)->selectRaw('step_type as label, COUNT(*) as value')->groupBy('step_type')->orderByDesc('value')->get(),
            'step_results' => AutomationHistory::where('created_at', '>=', $from)->selectRaw('status as label, COUNT(*) as value')->groupBy('status')->orderByDesc('value')->get(),
            'workflows' => $workflows,
        ];
    }
}
