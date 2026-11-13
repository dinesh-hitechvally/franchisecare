<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\Person;
use App\Models\PeopleList;
use App\Models\PushDevice;
use App\Models\PushLog;
use App\Services\Push\PushException;
use App\Services\Push\PushManager;
use Illuminate\Support\Str;
use App\Services\Attachments;
use App\Services\CompanyMailer;
use App\Services\SendLog;
use App\Services\MessageRenderer;
use App\Services\Sms\SmsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $page = Campaign::with('list:id,name', 'segment:id,name', 'tag:id,name')->withLogCounts()
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', strtoupper($v)))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', strtoupper($v)))
            ->when($request->query('search'), fn ($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when(strtoupper((string) $request->query('status')) === 'SCHEDULED', fn ($q) => $q->orderBy('scheduled_at'), fn ($q) => $q->latest())
            ->paginate(20);
        $page->getCollection()->each->logTotals(); // the counts of each campaign's own channel (emails or texts)

        return $page;
    }

    /** Cancel a scheduled send: back to draft, keeping the content. */
    public function unschedule(Campaign $campaign)
    {
        abort_unless($campaign->status === 'SCHEDULED', 422, 'Campaign is not scheduled.');
        $campaign->forceFill(['status' => 'DRAFT', 'scheduled_at' => null])->save();

        return $campaign;
    }

    public function show(Campaign $campaign)
    {
        return $campaign->load('list:id,name', 'segment:id,name')->loadCount(Campaign::logCountSpec())->logTotals();
    }

    public function store(Request $request)
    {
        return Campaign::create($this->validated($request));
    }

    public function update(Request $request, Campaign $campaign)
    {
        abort_unless(in_array($campaign->status, ['DRAFT', 'SCHEDULED']), 422, 'Only draft or scheduled campaigns can be edited.');
        $campaign->update($this->validated($request));

        return $campaign;
    }

    public function destroy(Campaign $campaign)
    {
        abort_if($campaign->status === 'SENDING', 422, 'Campaign is currently sending.');
        $campaign->delete();

        return response()->noContent();
    }

    /** Send now, or schedule if scheduled_at is in the future. */
    public function send(Campaign $campaign)
    {
        abort_unless(in_array($campaign->status, ['DRAFT', 'SCHEDULED']), 422, 'Campaign already sent.');

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            $campaign->forceFill(['status' => 'SCHEDULED'])->save();
        } else {
            $campaign->forceFill(['status' => 'SENDING'])->save();
            SendCampaignJob::dispatch($campaign->id);
        }

        return $campaign->fresh();
    }

    /** Stop a campaign that is sending: what has gone out stays sent, everything still waiting is skipped. */
    public function cancel(Campaign $campaign)
    {
        abort_unless($campaign->status === 'SENDING', 422, 'Only a campaign that is sending can be stopped.');

        $campaign->logModel()::where('campaign_id', $campaign->id)->where('status', 'QUEUED')
            ->update(['status' => 'SKIPPED', 'error' => 'Sending was stopped before this was sent.']);
        $campaign->forceFill(['status' => 'CANCELLED', 'completed_at' => now()])->save();

        return ApiResponse::ok($campaign->fresh()->loadCount(Campaign::logCountSpec())->logTotals(), 'Sending stopped. What already went out stays sent; the rest was skipped.');
    }

    /** A copy to start from: same content and audience, back to draft with no schedule. */
    public function duplicate(Campaign $campaign)
    {
        $copy = $campaign->replicate(['scheduled_at', 'started_at', 'completed_at']);
        $copy->name = mb_substr('Copy of '.$campaign->name, 0, 255);
        $copy->forceFill(['status' => 'DRAFT'])->save();

        return response($copy, 201);
    }

    /** What sending would do, and anything worth fixing first. */
    public function check(Campaign $campaign)
    {
        $isEmail = $campaign->type === 'email';
        $isPush = $campaign->type === 'push';
        $base = fn () => $campaign->audienceQuery();

        if ($campaign->toAllDevices()) {
            return $this->checkAllDevices($campaign);
        }

        $total = $base()->count();
        // push reaches people who have at least one device that accepts notifications; the others have nothing to send to
        $reach = $isPush
            ? $base()->where('people.is_subscribed', true)->whereHas('pushDevices', fn ($q) => $q->where('status', 'ACTIVE'))->count()
            : $campaign->whereAddress($base()->where('people.is_subscribed', true), true)->count();
        $unsub = $base()->where('people.is_subscribed', false)->count();

        $warnings = [];
        if ($isPush) {
            if (! trim((string) $campaign->subject)) {
                $warnings[] = 'The title is empty.';
            } elseif (mb_strlen($campaign->subject) > 50) {
                $warnings[] = 'The title is long ('.mb_strlen($campaign->subject).' characters). Phones and browsers cut it off after about 50.';
            }
            if (mb_strlen((string) $campaign->body) > 150) {
                $warnings[] = 'The message is long ('.mb_strlen($campaign->body).' characters). Most devices show only the first 100 to 150.';
            }
            if (! trim((string) $campaign->push_url)) {
                $warnings[] = 'No link is set, so a tap on the notification only opens your website’s home page or the app.';
            }
            $company = \App\Models\Company::find($campaign->company_id);
            if ($company && \App\Services\PushSettings::mode($company) === 'log') {
                $warnings[] = 'Push is in test mode (Settings → Push notifications): the notifications are written to the log and nothing reaches a device.';
            }
        } elseif ($campaign->isSocial()) {
            $provider = \App\Services\Integrations\Providers::find($campaign->type);
            if (! $campaign->socialAccount()) {
                $warnings[] = 'The account this campaign sends through is not connected. Choose one (Integrations → Connections) before sending.';
            }
            if ($campaign->type === 'whatsapp' && ! trim((string) $campaign->social_template)) {
                $warnings[] = 'No WhatsApp template is set. Without an approved template WhatsApp only delivers to people who wrote to you in the last 24 hours.';
            }
            if ($provider && mb_strlen((string) $campaign->body) > $provider->maxLength()) {
                $warnings[] = 'The message is longer than '.$provider->label().' allows ('.$provider->maxLength().' characters). It will be cut.';
            }
            if ($campaign->type !== 'whatsapp') {
                $warnings[] = ($campaign->type === 'x' ? 'X delivers' : 'Messenger delivers').' only to people who already have a conversation open with you; others show as failed.';
            }
        } elseif ($isEmail) {
            if (! trim((string) $campaign->subject)) {
                $warnings[] = 'The subject line is empty.';
            } elseif (mb_strlen($campaign->subject) > 90) {
                $warnings[] = 'The subject line is long ('.mb_strlen($campaign->subject).' characters). Mail apps cut it off after about 60.';
            }
            if (preg_match('~https?://(localhost|127\.0\.0\.1|[^/"\']*\.test\b)~i', (string) $campaign->body)) {
                $warnings[] = 'The email links to a local address (localhost) that recipients cannot open.';
            }
            $company = \App\Models\Company::find($campaign->company_id);
            if ($company && ! $company->from_email && \App\Services\MailSettings::activeKey($company) === 'default') {
                $warnings[] = 'No sender address is set (Settings → Email sending), so mail may be rejected or marked as spam.';
            }
        } else {
            $len = mb_strlen((string) $campaign->body);
            $unicode = preg_match('/[^\x00-\x7F]/', (string) $campaign->body) === 1;
            $per = $unicode ? 70 : 160;
            if ($len > $per) {
                $warnings[] = "The text is {$len} characters, which is sent as ".(int) ceil($len / ($unicode ? 67 : 153)).' parts and may cost more'.($unicode ? ' (it contains characters like emoji or accents that shorten each part)' : '').'.';
            }
        }
        if ($total > 0 && $reach === 0) {
            $warnings[] = 'Nobody in this audience can receive it: they are unsubscribed or have no '.($isPush ? 'device that accepts notifications' : ($isEmail ? 'email address' : ($campaign->type === 'whatsapp' || $campaign->type === 'sms' ? 'phone number' : 'Messenger / X ID on their profile'))).'.';
        }

        return ['audience' => $total, 'reach' => $reach, 'unsubscribed' => $unsub, 'skipped' => $total - $reach, 'warnings' => $warnings];
    }

    /** "All devices": the numbers are devices, not people. A device of a person who unsubscribed is left out. */
    protected function checkAllDevices(Campaign $campaign): array
    {
        $devices = fn () => PushDevice::where('status', 'ACTIVE')->when($campaign->push_platform, fn ($q, $p) => $q->where('platform', strtoupper($p)));
        $total = $devices()->count();
        $out = $devices()->whereHas('person', fn ($q) => $q->where('people.is_subscribed', false))->count();
        $company = \App\Models\Company::find($campaign->company_id);

        $warnings = [];
        if (! trim((string) $campaign->subject)) {
            $warnings[] = 'The title is empty.';
        }
        if (! trim((string) $campaign->push_url)) {
            $warnings[] = 'No link is set, so a tap on the notification only opens your website’s home page or the app.';
        }
        if ($total > 0 && $total === $out) {
            $warnings[] = 'Every registered device belongs to someone who unsubscribed.';
        }
        if ($company && \App\Services\PushSettings::mode($company) === 'log') {
            $warnings[] = 'Push is in test mode (Settings → Push notifications): the notifications are written to the log and nothing reaches a device.';
        }

        return ['audience' => $total, 'reach' => $total - $out, 'unsubscribed' => $out, 'skipped' => 0, 'warnings' => $warnings, 'devices' => true];
    }

    /** Sends a single test message synchronously to the given address/number. */
    public function test(Request $request, Campaign $campaign, MessageRenderer $renderer, SmsManager $sms)
    {
        $data = $request->validate(['to' => 'required|string']);
        $company = $request->user()->company;
        if ($campaign->type === 'push') {
            return $this->testPush($data['to'], $campaign, $company, $renderer);
        }
        if ($campaign->isSocial()) {
            return $this->testSocial($data['to'], $campaign, $company, $renderer);
        }
        $sample = new Person(['first_name' => 'Test', 'last_name' => 'User', 'email' => $data['to']]);
        $sample->unsubscribe_token = 'test';

        if ($campaign->type === 'email') {
            $request->validate(['to' => 'email']);
            [$subject, $html] = $renderer->renderEmail('[TEST] '.$campaign->subject, $campaign->body, $company, $sample);
            $files = Attachments::in($html.Attachments::marker($campaign->attachments), $company->id);
            // through the service that is in use, like the real send, and written to the send history
            SendLog::run($company, 'email', $data['to'], $subject, $html, 'test', fn () => CompanyMailer::for($company)->html($html, function ($m) use ($data, $company, $subject, $files, $html) {
                foreach ($files as $f) {
                    $m->attach($f['path'], ['as' => $f['name'], 'mime' => $f['mime']]);
                }
                $m->text(MessageRenderer::plainText($html));
                $m->to($data['to'])->subject($subject);
                if ($company->from_email) {
                    $m->from($company->from_email, $company->from_name ?: $company->name);
                }
            }));
        } else {
            $text = $renderer->renderSms($campaign->body, $company, $sample);
            SendLog::run($company, 'sms', $data['to'], null, $text, 'test', fn () => $sms->forCompany($company)->send($data['to'], $text));
        }

        return ApiResponse::ok(['ok' => true], 'Test message sent.');
    }

    /** Live preview of a body using sample data. */
    public function preview(Request $request, MessageRenderer $renderer)
    {
        $data = $request->validate(['type' => 'required|in:email,sms,push,whatsapp,messenger,x', 'subject' => 'nullable|string', 'body' => 'required|string']);
        $company = $request->user()->company;
        $sample = new Person(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com', 'phone' => '+61400000000']);
        $sample->unsubscribe_token = 'preview';

        if ($data['type'] === 'email') {
            [$subject, $html] = $renderer->renderEmail($data['subject'] ?? '', $data['body'], $company, $sample);

            return compact('subject', 'html');
        }

        if ($data['type'] === 'push') {
            return ['title' => $renderer->renderSms($data['subject'] ?? '', $company, $sample), 'text' => $renderer->renderSms($data['body'], $company, $sample)];
        }

        return ['text' => $renderer->renderSms($data['body'], $company, $sample)];
    }

    /** A test goes to the address typed (a phone number for WhatsApp, an id for Messenger / X), through the connected account, and is written to the send history. */
    protected function testSocial(string $to, Campaign $campaign, $company, MessageRenderer $renderer)
    {
        $account = $campaign->socialAccount();
        $provider = $account ? \App\Services\Integrations\Providers::find($account->provider) : null;
        abort_unless($provider instanceof \App\Services\Integrations\MessagingProvider, 422, 'Choose the connected account this campaign sends through first.');

        $text = '[TEST] '.$renderer->renderSms((string) $campaign->body, $company, new Person(['first_name' => 'Test', 'last_name' => 'User']));
        $log = \App\Models\SocialLog::create([
            'company_id' => $company->id, 'platform' => $campaign->type, 'integration_id' => $account->id, 'recipient' => trim($to), 'body' => $text, 'status' => 'QUEUED', 'source' => 'test', 'service' => 'social',
        ]);
        try {
            $id = $provider->deliver($account, trim($to), mb_substr($text, 0, $provider->maxLength()), ['template' => $campaign->social_template]);
            $log->update(['status' => 'SENT', 'sent_at' => now(), 'provider_id' => $id !== '' ? mb_substr($id, 0, 255) : null]);
        } catch (\App\Services\Integrations\IntegrationException $e) {
            $log->update(['status' => 'FAILED', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
            abort(422, $e->getMessage());
        }

        return ApiResponse::ok(['ok' => true], 'Test message sent.');
    }

    /** A test push goes to the devices of the person with that email address, through the service in use, and is written to the send history. */
    protected function testPush(string $to, Campaign $campaign, $company, MessageRenderer $renderer)
    {
        $person = Person::where('email', Str::lower(trim($to)))->first();
        $devices = $person ? PushDevice::where('person_id', $person->id)->where('status', 'ACTIVE')->get() : collect();
        abort_if($devices->isEmpty(), 422, 'No device is registered for that email address. Open your website or app, allow notifications, and register with that email first.');

        $title = '[TEST] '.$renderer->renderSms((string) $campaign->subject, $company, $person);
        $text = $renderer->renderSms((string) $campaign->body, $company, $person);
        $error = null;
        foreach ($devices as $device) {
            $log = PushLog::create([
                'company_id' => $company->id, 'person_id' => $person->id, 'push_device_id' => $device->id, 'token' => Str::random(40), 'recipient' => $device->label ?: ucfirst($device->platform),
                'subject' => $title, 'body' => $text, 'url' => $campaign->push_url, 'status' => 'QUEUED', 'source' => 'test', 'service' => PushManager::service($company, $device->platform),
            ]);
            try {
                $id = PushManager::send($company, $device, ['title' => $title, 'body' => $text, 'url' => $campaign->push_url ? url("/api/push/c/{$log->token}") : null, 'image' => $campaign->push_image]);
                $log->update(['status' => \App\Services\SendLog::deliveredStatus($company, 'push'), 'sent_at' => now(), 'provider_id' => $id ? mb_substr($id, 0, 255) : null]);
            } catch (PushException $e) {
                $log->update(['status' => 'FAILED', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
                $error ??= $e->getMessage();
            }
        }
        abort_if($error && $devices->count() === 1, 422, $error);

        return ApiResponse::ok(['ok' => true], 'Test notification sent to '.$devices->count().' device'.($devices->count() === 1 ? '' : 's').($error ? ' (some failed: '.$error.')' : '').'.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms,push,whatsapp,messenger,x',
            'social_account_id' => 'nullable|integer',
            'social_template' => 'nullable|string|max:120',
            'template_id' => 'nullable|integer',
            'people_list_id' => 'nullable|integer',
            'segment_id' => 'nullable|integer',
            'tag_id' => 'nullable|integer',
            'subject' => 'nullable|string|max:255|required_if:type,email,push', // a push notification's title
            'push_url' => 'nullable|url:http,https|max:1000',
            'push_image' => 'nullable|url:http,https|max:1000',
            'push_all_devices' => 'nullable|boolean',
            'push_platform' => 'nullable|in:web,android,ios', // with all devices: only browsers, only Android apps or only iOS apps
            'body' => 'required|string',
            'design' => 'nullable|array',
            'attachments' => 'nullable|array|max:10',
            'attachments.*.token' => 'required|string|size:32',
            'scheduled_at' => 'nullable|date',
        ]);

        if ($data['type'] === 'push') {
            abort_if(mb_strlen($data['body']) > 500, 422, 'The message is too long for a notification (500 characters at most).');
            $data['push_all_devices'] = (bool) ($data['push_all_devices'] ?? false);
            if ($data['push_all_devices']) {
                $data['people_list_id'] = $data['segment_id'] = $data['tag_id'] = null; // every device: no audience of people
            }
            $data['push_platform'] = $data['push_all_devices'] ? ($data['push_platform'] ?? null) : null; // the kind of device only applies to "all devices"
        } else {
            $data['push_url'] = $data['push_image'] = null;
            $data['push_all_devices'] = false;
            $data['push_platform'] = null;
        }
        if (in_array($data['type'], \App\Models\SocialLog::PLATFORMS, true)) {
            // the connected page / number it goes out through: the one chosen, or the only one connected
            $accounts = \App\Models\Integration::where('provider', $data['type'])->where('status', 'CONNECTED')->get();
            $account = ! empty($data['social_account_id']) ? $accounts->firstWhere('id', (int) $data['social_account_id']) : ($accounts->count() === 1 ? $accounts->first() : null);
            abort_unless($account, 422, $accounts->isEmpty() ? 'Connect a '.\App\Services\Integrations\Providers::find($data['type'])->label().' account first (Integrations → Connections).' : 'Choose which connected account to send through.');
            $data['social_account_id'] = $account->id;
            $data['social_template'] = $data['type'] === 'whatsapp' ? (trim((string) ($data['social_template'] ?? '')) ?: null) : null;
            abort_if(mb_strlen($data['body']) > \App\Services\Integrations\Providers::find($data['type'])->maxLength(), 422, 'The message is too long for '.\App\Services\Integrations\Providers::find($data['type'])->label().'.');
        } else {
            $data['social_account_id'] = $data['social_template'] = null;
        }
        $data['attachments'] = $data['type'] === 'email' ? Attachments::clean($data['attachments'] ?? null, $request->user()->company_id) : null;

        // one audience source at a time: a segment wins over a tag, a tag over a list
        if (! empty($data['segment_id'])) {
            abort_unless(\App\Models\Segment::whereKey($data['segment_id'])->exists(), 422, 'Invalid segment.');
            $data['people_list_id'] = $data['tag_id'] = null;
        }
        if (! empty($data['tag_id'])) {
            abort_unless(\App\Models\Tag::whereKey($data['tag_id'])->exists(), 422, 'Invalid tag.');
            $data['people_list_id'] = null;
        }
        if (! empty($data['people_list_id'])) {
            abort_unless(PeopleList::whereKey($data['people_list_id'])->exists(), 422, 'Invalid list.');
        }

        return $data;
    }
}
