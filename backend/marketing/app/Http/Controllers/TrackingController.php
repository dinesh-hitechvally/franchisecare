<?php

namespace App\Http\Controllers;

use App\Models\ClickEvent;
use App\Models\Contact;
use App\Models\EmailLog;
use App\Services\MessageRenderer;
use App\Services\Subscriptions;
use Illuminate\Http\Request;

/** Public endpoints: open pixel, click redirect, unsubscribe page. */
class TrackingController extends Controller
{
    public function open(string $token)
    {
        EmailLog::withoutGlobalScope('company')->where('track_token', $token)->whereNull('opened_at')->update(['opened_at' => now()]);

        // 1x1 transparent gif
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
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
            $log->opened_at ?? $log->update(['opened_at' => now()]);
            ClickEvent::create(['company_id' => $log->company_id, 'email_log_id' => $log->id, 'campaign_id' => $log->campaign_id, 'url' => mb_substr($url, 0, 2000)]);
        }

        return redirect()->away($url);
    }

    public function unsubscribe(Request $request, string $token)
    {
        $contact = Contact::withoutGlobalScope('company')->where('unsubscribe_token', $token)->first();
        $channel = $request->query('channel') === 'sms' ? 'sms' : 'email';

        if ($contact) {
            Subscriptions::set($contact, $channel, false);
        }

        return response('<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><body style="font-family:Arial,sans-serif;text-align:center;padding:64px 16px;color:#111"><h2>You\'ve been unsubscribed</h2><p>You will no longer receive '.$channel.' messages from us.</p></body>');
    }
}
