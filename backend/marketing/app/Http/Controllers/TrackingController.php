<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\MessageLog;
use App\Services\MessageRenderer;
use Illuminate\Http\Request;

/** Public endpoints: open pixel, click redirect, unsubscribe page. */
class TrackingController extends Controller
{
    public function open(string $token)
    {
        MessageLog::withoutGlobalScopes()->where('track_token', $token)->whereNull('opened_at')->update(['opened_at' => now()]);

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

        MessageLog::withoutGlobalScopes()->where('track_token', $token)->increment('clicks');

        return redirect()->away($url);
    }

    public function unsubscribe(Request $request, string $token)
    {
        $contact = Contact::withoutGlobalScopes()->where('unsubscribe_token', $token)->first();
        $channel = $request->query('channel') === 'sms' ? 'sms' : 'email';

        if ($contact) {
            $contact->update([$channel.'_subscribed' => false]);
        }

        return response('<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><body style="font-family:Arial,sans-serif;text-align:center;padding:64px 16px;color:#111"><h2>You\'ve been unsubscribed</h2><p>You will no longer receive '.$channel.' messages from us.</p></body>');
    }
}
