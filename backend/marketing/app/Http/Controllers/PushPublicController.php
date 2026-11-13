<?php

namespace App\Http\Controllers;

use App\Models\PushDevice;
use App\Models\PushLog;
use App\Services\Push\Devices;
use App\Services\Push\PushException;
use App\Services\PushSettings;
use Illuminate\Http\Request;

/**
 * What a website talks to: the script that asks visitors to allow notifications (sdk.js), the service worker that shows them (sw.js),
 * the registration of a browser, and the click link a notification opens. Identified by the company's public site key.
 */
class PushPublicController extends Controller
{
    /** The browser needs the company's public push key to subscribe. */
    public function config(Request $request)
    {
        $company = PushSettings::companyForKey($request->query('key'));
        abort_unless($company, 404, 'Unknown site key.');

        return ['public_key' => PushSettings::webKeys($company)[0]];
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|size:40',
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string|max:2000',
            'subscription.keys.p256dh' => 'required|string|max:200',
            'subscription.keys.auth' => 'required|string|max:100',
            'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:30', 'label' => 'nullable|string|max:300',
            'page_url' => 'nullable|string|max:2000', // the page the visitor was on; anything that is not an http(s) address is ignored
            'device_uid' => 'nullable|string|max:64',  // the browser's own id: it keeps ONE device row across turning notifications off and on
        ]);
        $company = PushSettings::companyForKey($data['key']);
        abort_unless($company, 404, 'Unknown site key.');

        try {
            $device = Devices::register($company, 'web', $data['subscription']['endpoint'], $data['subscription']['keys'], Devices::label($request->userAgent()), ['email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null], $data['page_url'] ?? null, $data['device_uid'] ?? null);
        } catch (PushException $e) {
            abort(422, $e->getMessage());
        }

        return ['ok' => true, 'linked' => (bool) $device->person_id];
    }

    public function unsubscribe(Request $request)
    {
        $data = $request->validate(['key' => 'required|string|size:40', 'endpoint' => 'required|string|max:2000', 'device_uid' => 'nullable|string|max:64']);
        $company = PushSettings::companyForKey($data['key']);
        abort_unless($company, 404, 'Unknown site key.');

        PushDevice::withoutGlobalScope('company')->where('company_id', $company->id)->where(fn ($q) => $q->where('token_hash', PushDevice::hashOf($data['endpoint']))->when(Devices::cleanUid($data['device_uid'] ?? null), fn ($w, $uid) => $w->orWhere('device_uid', $uid)))
            ->get()->each(fn (PushDevice $d) => $d->update(['status' => 'revoked'])); // one by one, so each is written to the device history

        return ['ok' => true];
    }

    /** A tap on a notification: count it, then go on to the real address. */
    public function click(string $token)
    {
        $log = PushLog::withoutGlobalScopes()->where('token', $token)->first();
        abort_unless($log, 404);
        $log->forceFill(['clicks' => $log->clicks + 1, 'clicked_at' => $log->clicked_at ?? now()])->saveQuietly();

        $to = (string) $log->url;

        return preg_match('~^https?://~i', $to) ? redirect()->away($to) : redirect('/');
    }

    /** The worker, served inline: forms and landing pages on this app register it themselves (it controls pages under /api/push/ only). */
    public function worker()
    {
        return response($this->workerSource(), 200, ['Content-Type' => 'application/javascript; charset=utf-8', 'Cache-Control' => 'no-cache']);
    }

    public function sdk()
    {
        return response($this->sdkSource(), 200, ['Content-Type' => 'application/javascript; charset=utf-8', 'Cache-Control' => 'public, max-age=300']);
    }

    /** The service worker: it must be served from the visitor's own site (browsers insist), so the dashboard offers it as a download. */
    public function serviceWorker()
    {
        return response($this->workerSource(), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8', 'Content-Disposition' => 'attachment; filename="mate-push-sw.js"', 'Cache-Control' => 'public, max-age=300',
        ]);
    }

    protected function workerSource(): string
    {
        return <<<'JS'
// Shows the notifications you send from the marketing app. Put this file at the root of your website, so it is reachable as /mate-push-sw.js
self.addEventListener('install', function () { self.skipWaiting() })
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()) })

self.addEventListener('push', function (event) {
  var d = {}
  try { d = event.data ? event.data.json() : {} } catch (x) { d = { title: 'Notification', body: event.data ? event.data.text() : '' } }
  event.waitUntil(self.registration.showNotification(d.title || 'Notification', { body: d.body || '', icon: d.icon, image: d.image, data: { url: d.url || '/' } }))
})

// a tap opens the link (which first counts the click, then goes on to the real page)
self.addEventListener('notificationclick', function (event) {
  event.notification.close()
  event.waitUntil(self.clients.openWindow((event.notification.data && event.notification.data.url) || '/'))
})
JS;
    }

    protected function sdkSource(): string
    {
        return <<<'JS'
/* Marketing app push notifications. Add to your site:
   <script src="https://YOUR-APP/api/push/sdk.js" data-key="YOUR-SITE-KEY"></script>
   and call MatePush.subscribe({ email: 'visitor@example.com' }) when a visitor agrees (for example from a button). */
(function () {
  var script = document.currentScript
  var key = script && script.getAttribute('data-key')
  var api = script && script.src.split('/push/sdk.js')[0]
  var worker = (script && script.getAttribute('data-sw')) || '/mate-push-sw.js'

  function bytes(s) {
    s = s.replace(/-/g, '+').replace(/_/g, '/')
    var raw = atob(s + '='.repeat((4 - (s.length % 4)) % 4)), out = new Uint8Array(raw.length)
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i)
    return out
  }
  function unwrap(j) { return j && j.data !== undefined && j.success !== undefined ? j.data : j } // the server wraps answers as { success, data }
  // this browser's own id, kept in its storage: turning notifications off and on again keeps ONE device
  function uid() {
    try {
      var u = localStorage.getItem('fcpush-uid')
      if (!u) { u = (window.crypto && crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(36) + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2)); localStorage.setItem('fcpush-uid', u) }
      return u
    } catch (e) { return null }
  }
  function call(path, body) {
    return fetch(api + path, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(body) }).then(function (r) {
      return r.json().catch(function () { return {} }).then(function (j) { if (!r.ok) throw new Error(j.message || 'Request failed'); return unwrap(j) })
    })
  }
  function current() {
    return navigator.serviceWorker.getRegistration(worker).then(function (reg) { return reg ? reg.pushManager.getSubscription() : null })
  }

  var MatePush = {
    supported: 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window,
    // asks the visitor to allow notifications and registers the browser. opts: { email, phone } links it to a person you already know.
    subscribe: function (opts) {
      opts = opts || {}
      if (!MatePush.supported) return Promise.reject(new Error('This browser does not support push notifications.'))
      return fetch(api + '/push/config?key=' + encodeURIComponent(key)).then(function (r) { return r.json() }).then(unwrap).then(function (cfg) {
        return navigator.serviceWorker.register(worker).then(function () { return navigator.serviceWorker.ready }).then(function (reg) {
          return Notification.requestPermission().then(function (permission) {
            if (permission !== 'granted') throw new Error('Notifications were not allowed.')
            return reg.pushManager.getSubscription().then(function (sub) {
              return sub || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: bytes(cfg.public_key) })
            })
          })
        })
      }).then(function (sub) {
        return call('/push/subscribe', { key: key, subscription: sub.toJSON(), email: opts.email || null, phone: opts.phone || null, page_url: location.href.split('#')[0], device_uid: uid() })
      })
    },
    // links an already subscribed browser to a person (for example after they log in)
    identify: function (opts) {
      return current().then(function (sub) {
        if (!sub) throw new Error('This browser is not subscribed yet.')
        return call('/push/subscribe', { key: key, subscription: sub.toJSON(), email: (opts || {}).email || null, phone: (opts || {}).phone || null, device_uid: uid() })
      })
    },
    unsubscribe: function () {
      return current().then(function (sub) {
        if (!sub) return { ok: true }
        return call('/push/unsubscribe', { key: key, endpoint: sub.endpoint, device_uid: uid() }).then(function () { return sub.unsubscribe() })
      })
    },
  }
  window.MatePush = MatePush
  if (script && script.getAttribute('data-auto') === 'true') MatePush.subscribe().catch(function () {})
})()
JS;
    }
}
