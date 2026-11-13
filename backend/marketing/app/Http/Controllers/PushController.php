<?php

namespace App\Http\Controllers;

use App\Models\PushDevice;
use App\Models\PeopleList;
use App\Models\Person;
use App\Models\PushLog;
use App\Models\Segment;
use App\Models\Tag;
use App\Services\SegmentQuery;
use App\Services\Push\PushManager;
use App\Services\SendLog;
use Illuminate\Support\Str;
use App\Services\Push\Fcm;
use App\Services\Push\PushException;
use App\Services\PushSettings;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/** Settings → Push notifications: how to connect websites and apps, the sending mode, and the registered devices. */
class PushController extends Controller
{
    protected function payload(Request $request): array
    {
        $payload = PushSettings::payload($request->user()->company);

        return $payload + ['api_url' => url('/api')];
    }

    public function show(Request $request)
    {
        return $this->payload($request);
    }

    /** live = notifications are sent; log = test mode, they are only written to the message log. */
    public function update(Request $request)
    {
        $data = $request->validate(['mode' => 'required|in:log,live']);
        PushSettings::setMode($request->user()->company, $data['mode']);

        return $this->payload($request);
    }

    /** A new browser key. Browsers that subscribed with the old key stop working and must allow notifications again. */
    public function rotateKeys(Request $request)
    {
        PushSettings::webKeys($request->user()->company, true);

        return ApiResponse::ok($this->payload($request), 'New browser keys created. Browsers that had allowed notifications must allow them again.');
    }

    /** Saves the Firebase service account (for Android and iOS apps) after proving it works. */
    public function saveFcm(Request $request)
    {
        $data = $request->validate(['service_account' => 'required|string|max:20000']);
        try {
            $account = Fcm::parse($data['service_account']);
            Fcm::check($account);
        } catch (PushException $e) {
            abort(422, $e->getMessage());
        }
        PushSettings::setFcm($request->user()->company, $account);

        return ApiResponse::ok($this->payload($request), 'Firebase connected.');
    }

    public function removeFcm(Request $request)
    {
        PushSettings::setFcm($request->user()->company, null);

        return ApiResponse::ok($this->payload($request), 'Firebase disconnected.');
    }

    /**
     * What a push campaign can be sent to, for the campaign form: every active device, and how many PEOPLE WITH A DEVICE each list, tag and segment has
     * (their own counts include people nobody can notify).
     */
    public function audience()
    {
        $withDevice = fn ($q) => $q->whereHas('pushDevices', fn ($d) => $d->where('status', 'ACTIVE'));

        return [
            'devices' => PushDevice::where('status', 'ACTIVE')->count(),
            'platforms' => collect(['web', 'android', 'ios'])->mapWithKeys(fn ($p) => [$p => PushDevice::where('status', 'ACTIVE')->where('platform', strtoupper($p))->count()]),
            'people' => $withDevice(Person::query())->count(),
            'lists' => PeopleList::get(['id'])->mapWithKeys(fn ($l) => [$l->id => $withDevice($l->people())->count()]),
            'tags' => Tag::get(['id'])->mapWithKeys(fn ($t) => [$t->id => $withDevice($t->people())->count()]),
            'segments' => Segment::get()->mapWithKeys(fn ($s) => [$s->id => $withDevice(SegmentQuery::forSegment($s))->count()]),
        ];
    }

    /** The registered devices, newest first. Tokens are never sent to the browser. */
    public function devices(Request $request)
    {
        $page = PushDevice::with('person:id,first_name,last_name,email')
            ->when(in_array($request->query('platform'), ['web', 'android', 'ios'], true), fn ($q) => $q->where('platform', strtoupper($request->query('platform'))))
            ->when(in_array($request->query('status'), ['active', 'revoked'], true), fn ($q) => $q->where('status', strtoupper($request->query('status'))))
            ->when($request->query('linked') === 'yes', fn ($q) => $q->whereNotNull('person_id'))
            ->when($request->query('linked') === 'no', fn ($q) => $q->whereNull('person_id'))
            ->when($request->query('search'), function ($q, $v) {
                $q->where(fn ($w) => $w->where('label', 'like', "%$v%")->orWhere('page_url', 'like', "%$v%")->orWhereHas('person', fn ($p) => $p->where('email', 'like', "%$v%")->orWhere('first_name', 'like', "%$v%")->orWhere('last_name', 'like', "%$v%")));
            })
            ->latest('id')->paginate(min(50, max(5, (int) $request->query('per_page', 20))));

        $page->getCollection()->transform(fn (PushDevice $d) => [
            'id' => $d->id, 'platform' => $d->platform, 'label' => $d->label, 'page_url' => $d->page_url, 'status' => $d->status, 'last_seen_at' => $d->last_seen_at, 'created_at' => $d->created_at,
            'person' => $d->person ? ['id' => $d->person->id, 'name' => trim($d->person->first_name.' '.$d->person->last_name), 'email' => $d->person->email] : null,
        ]);

        return $page;
    }

    /** Sends a test notification to this one device, through the service in use, and writes it to the message log (as a test: it never counts in statistics). */
    public function testDevice(Request $request, PushDevice $device)
    {
        abort_unless($device->status === 'active', 422, 'This device turned notifications off, so nothing can be sent to it. It becomes active again when it registers again.');
        $company = $request->user()->company;
        $title = 'Test notification';
        $text = 'This is a test from '.$company->name.'. If you can read this, push notifications work on this device.';

        $log = PushLog::create([
            'company_id' => $company->id, 'person_id' => $device->person_id, 'push_device_id' => $device->id, 'token' => Str::random(40), 'recipient' => $device->label ?: ucfirst($device->platform),
            'subject' => $title, 'body' => $text, 'status' => 'QUEUED', 'source' => 'test', 'service' => PushManager::service($company, $device->platform),
        ]);

        try {
            $id = PushManager::send($company, $device, ['title' => $title, 'body' => $text]);
        } catch (PushException $e) {
            $log->update(['status' => 'FAILED', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
            abort(422, $e->getMessage().($e->gone ? ' This device was switched off.' : ''));
        }
        $status = SendLog::deliveredStatus($company, 'push');
        $log->update(['status' => $status, 'sent_at' => now(), 'provider_id' => $id ? mb_substr($id, 0, 255) : null]);

        return ApiResponse::ok(['status' => $status], $status === 'LOGGED'
            ? 'Test mode is on, so the notification was only written to the message log. Switch to Live to deliver it.'
            : 'Test notification sent. It should appear on the device in a few seconds.');
    }

    public function destroyDevice(PushDevice $device)
    {
        $device->delete();

        return response()->noContent();
    }
}
