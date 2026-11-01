<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\UrlGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $webhookId, public string $event, public array $data) {}

    public function handle(): void
    {
        self::deliver(Webhook::withoutGlobalScope('company')->findOrFail($this->webhookId), $this->event, $this->data);
    }

    /** Sends one delivery and records the result. Used by the job and by the "test" button. */
    public static function deliver(Webhook $hook, string $event, array $data): WebhookDelivery
    {
        $payload = json_encode(['event' => $event, 'created_at' => now()->toIso8601String(), 'data' => $data]);
        $status = null;
        $ok = false;

        try {
            UrlGuard::assertPublic($hook->url);
            $res = Http::timeout(10)->withoutRedirecting()->withBody($payload, 'application/json')->withHeaders([
                'X-Webhook-Event' => $event,
                'X-Webhook-Signature' => 'sha256='.hash_hmac('sha256', $payload, $hook->secret),
                'User-Agent' => 'FranchiseCareMarketing-Webhook/1.0',
            ])->post($hook->url);
            $status = $res->status();
            $ok = $res->successful();
            $response = mb_substr($res->body(), 0, 500);
        } catch (Throwable $e) {
            $response = mb_substr($e->getMessage(), 0, 500);
        }

        return WebhookDelivery::create([
            'company_id' => $hook->company_id, 'webhook_id' => $hook->id, 'event' => $event,
            'status_code' => $status, 'success' => $ok, 'response' => $response, 'payload' => $payload,
        ]);
    }
}
