<?php

namespace App\Http\Controllers;

use App\Models\ClickEvent;
use App\Models\Contact;
use App\Models\EmailLog;
use App\Services\Automation;
use App\Services\MessageRenderer;
use App\Services\Subscriptions;
use Illuminate\Http\Request;

/** Public endpoints: open pixel, click redirect, unsubscribe page. */
class TrackingController extends Controller
{
    public function open(string $token)
    {
        // the first open of a message (later opens change nothing): that is the moment the "opens an email" trigger fires
        if (EmailLog::withoutGlobalScope('company')->where('track_token', $token)->whereNull('opened_at')->update(['opened_at' => now()])) {
            $this->fire('email_opened', $token);
        }

        // 1x1 transparent gif
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    /** Starts the workflows listening to this event for the person the message was sent to. */
    protected function fire(string $type, string $token, array $ctx = []): void
    {
        $log = EmailLog::withoutGlobalScope('company')->where('track_token', $token)->first();
        $contact = $log?->contact_id ? Contact::withoutGlobalScope('company')->find($log->contact_id) : null;
        if ($contact) {
            Automation::fire($type, $contact, $ctx + ['campaign_id' => $log->campaign_id]);
        }
    }

    public function click(Request $request, string $token)
    {
        $url = (string) $request->query('u');
        // signature stops this being used as an open redirect
        abort_unless(hash_equals(MessageRenderer::sign($url), (string) $request->query('s')) && preg_match('#^https?://#i', $url), 404);

        $log = EmailLog::withoutGlobalScope('company')->where('track_token', $token)->first();
        if ($log) {
            $log->increment('clicks');
            // a click proves the message was opened, even if the open pixel was blocked
            $firstOpen = ! $log->opened_at;
            $log->opened_at ?? $log->update(['opened_at' => now()]);
            ClickEvent::create(['company_id' => $log->company_id, 'email_log_id' => $log->id, 'campaign_id' => $log->campaign_id, 'url' => mb_substr($url, 0, 2000)]);
            $firstOpen && $this->fire('email_opened', $token);
            $this->fire('link_clicked', $token, ['url' => $url]);
        }

        return redirect()->away($url);
    }

    public function unsubscribe(Request $request, string $token)
    {
        $contact = Contact::withoutGlobalScope('company')->where('unsubscribe_token', $token)->first();
        // links in messages sent earlier still carry ?channel=; it makes no difference any more
        if ($contact) {
            Subscriptions::set($contact, false);
        }

        return response('<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><body style="font-family:Arial,sans-serif;text-align:center;padding:64px 16px;color:#111"><h2>You\'ve been unsubscribed</h2><p>You will no longer receive email or SMS messages from us.</p></body>');
    }
}
