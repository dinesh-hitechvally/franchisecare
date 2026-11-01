<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\Subscriptions;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /** Summary counts + a filterable contact list for subscription management. */
    public function index(Request $request)
    {
        $channel = $request->query('channel') === 'sms' ? 'sms' : 'email';
        $col = $channel.'_subscribed';
        $has = $channel === 'sms' ? 'phone' : 'email';

        $count = fn ($ch, $on) => Contact::where($ch.'_subscribed', $on)->whereNotNull($ch === 'sms' ? 'phone' : 'email')->count();

        $q = Contact::whereNotNull($has)->where($has, '!=', '');
        match ($request->query('status')) {
            'unsubscribed' => $q->where($col, false),
            'subscribed' => $q->where($col, true),
            default => null,
        };
        if ($s = $request->query('search')) {
            $q->where(fn ($w) => $w->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")->orWhere($has, 'like', "%$s%"));
        }

        return [
            'summary' => [
                'email' => ['subscribed' => $count('email', true), 'unsubscribed' => $count('email', false)],
                'sms' => ['subscribed' => $count('sms', true), 'unsubscribed' => $count('sms', false)],
            ],
            'contacts' => $q->orderByDesc($channel.'_unsubscribed_at')->orderByDesc('id')
                ->paginate(25, ['id', 'first_name', 'last_name', 'email', 'phone', 'email_subscribed', 'sms_subscribed', 'email_unsubscribed_at', 'sms_unsubscribed_at']),
        ];
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:1000',
            'ids.*' => 'integer',
            'channel' => 'required|in:email,sms',
            'subscribed' => 'required|boolean',
        ]);

        $changed = 0;
        foreach (Contact::whereIn('id', $data['ids'])->get() as $c) {
            $changed += Subscriptions::set($c, $data['channel'], (bool) $data['subscribed']) ? 1 : 0;
        }

        return ['changed' => $changed];
    }
}
