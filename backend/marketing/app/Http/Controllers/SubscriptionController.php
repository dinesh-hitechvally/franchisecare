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
        $q = Contact::query();
        match ($request->query('status')) {
            'unsubscribed' => $q->where('is_subscribed', false),
            'subscribed' => $q->where('is_subscribed', true),
            default => null,
        };
        if ($s = $request->query('search')) {
            $q->where(fn ($w) => $w->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"));
        }

        return [
            'summary' => ['subscribed' => Contact::where('is_subscribed', true)->count(), 'unsubscribed' => Contact::where('is_subscribed', false)->count()],
            'contacts' => $q->orderByDesc('id')->paginate(25, ['id', 'first_name', 'last_name', 'email', 'phone', 'is_subscribed', 'subscribed_at']),
        ];
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:1000',
            'ids.*' => 'integer',
            'subscribed' => 'required|boolean',
        ]);

        $changed = 0;
        foreach (Contact::whereIn('id', $data['ids'])->get() as $c) {
            $changed += Subscriptions::set($c, (bool) $data['subscribed']) ? 1 : 0;
        }

        return ['changed' => $changed];
    }
}
