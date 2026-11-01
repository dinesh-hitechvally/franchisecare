<?php

namespace App\Http\Controllers;

use App\Jobs\SendWebhookJob;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\UrlGuard;
use App\Services\WebhookDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class WebhookController extends Controller
{
    public function index()
    {
        return Webhook::latest()->get()->each(function ($w) {
            $last = WebhookDelivery::where('webhook_id', $w->id)->latest('id')->first(['success', 'status_code', 'created_at']);
            $w->setAttribute('last_delivery', $last);
        });
    }

    public function events()
    {
        return collect(WebhookDispatcher::EVENTS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $secret = 'whsec_'.Str::random(32);
        $hook = Webhook::create($data + ['secret' => $secret]);

        // the signing secret is shown exactly once, here
        return response($hook->makeVisible('secret'), 201);
    }

    public function update(Request $request, Webhook $webhook)
    {
        $webhook->update($this->validated($request));

        return $webhook;
    }

    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return response()->noContent();
    }

    /** Sends a signed "ping" immediately and returns what the receiver answered. */
    public function test(Webhook $webhook)
    {
        return SendWebhookJob::deliver($webhook, 'ping', ['message' => 'Test delivery from FranchiseCare Marketing']);
    }

    public function deliveries(Webhook $webhook)
    {
        return WebhookDelivery::where('webhook_id', $webhook->id)->latest('id')->paginate(30);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'in:*,'.implode(',', array_keys(WebhookDispatcher::EVENTS)),
            'active' => 'boolean',
        ]);

        try {
            UrlGuard::assertPublic($data['url']);
        } catch (Throwable $e) {
            abort(422, 'Webhook URL: '.$e->getMessage());
        }

        return $data;
    }
}
