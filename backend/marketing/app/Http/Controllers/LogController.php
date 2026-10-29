<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\MessageLog;
use App\Models\ContactList;
use App\Models\Template;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        return MessageLog::with('campaign:id,name')
            ->select('id', 'campaign_id', 'channel', 'recipient', 'subject', 'status', 'error', 'sent_at', 'opened_at', 'clicks', 'created_at')
            ->when($request->query('channel'), fn ($q, $v) => $q->where('channel', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('campaign_id'), fn ($q, $v) => $q->where('campaign_id', $v))
            ->when($request->query('search'), fn ($q, $v) => $q->where('recipient', 'like', "%$v%"))
            ->latest('id')->paginate(30);
    }

    public function show(MessageLog $log)
    {
        return $log;
    }

    public function dashboard()
    {
        $stat = fn ($channel) => [
            'sent' => MessageLog::where('channel', $channel)->where('status', 'sent')->count(),
            'failed' => MessageLog::where('channel', $channel)->where('status', 'failed')->count(),
        ];
        $emailSent = max(1, MessageLog::where('channel', 'email')->where('status', 'sent')->count());

        return [
            'contacts' => Contact::count(),
            'campaigns' => Campaign::count(),
            'email' => $stat('email') + [
                'open_rate' => round(MessageLog::where('channel', 'email')->whereNotNull('opened_at')->count() / $emailSent * 100, 1),
                'clicked' => MessageLog::where('channel', 'email')->where('clicks', '>', 0)->count(),
            ],
            'sms' => $stat('sms'),
            'lists' => ContactList::count(),
            'templates' => Template::count(),
            'unsubscribed' => Contact::where(fn ($q) => $q->where('email_subscribed', false)->orWhere('sms_subscribed', false))->count(),
            'drafts' => Campaign::where('status', 'draft')->count(),
            'scheduled' => Campaign::where('status', 'scheduled')->count(),
            'daily' => $this->daily(),
            'upcoming' => Campaign::with('list:id,name')->where('status', 'scheduled')->orderBy('scheduled_at')->limit(5)
                ->get(['id', 'name', 'type', 'contact_list_id', 'scheduled_at']),
            'recent_logs' => MessageLog::latest('id')->limit(5)->get(['id', 'channel', 'recipient', 'status', 'created_at']),
            'recent_campaigns' => Campaign::latest()->limit(5)->get(['id', 'name', 'type', 'status', 'created_at']),
        ];
    }

    /** Messages sent per day for the last 14 days (dashboard bar chart). */
    protected function daily(): array
    {
        $counts = MessageLog::where('status', 'sent')->where('sent_at', '>=', now()->subDays(13)->startOfDay())
            ->get(['sent_at'])->groupBy(fn ($l) => $l->sent_at->toDateString())->map->count();

        return collect(range(13, 0))->map(fn ($i) => $counts[now()->subDays($i)->toDateString()] ?? 0)->all();
    }
}
