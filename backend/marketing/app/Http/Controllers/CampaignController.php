<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\Person;
use App\Models\PeopleList;
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
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('search'), fn ($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when($request->query('status') === 'scheduled', fn ($q) => $q->orderBy('scheduled_at'), fn ($q) => $q->latest())
            ->paginate(20);
        $page->getCollection()->each->logTotals(); // the counts of each campaign's own channel (emails or texts)

        return $page;
    }

    /** Cancel a scheduled send: back to draft, keeping the content. */
    public function unschedule(Campaign $campaign)
    {
        abort_unless($campaign->status === 'scheduled', 422, 'Campaign is not scheduled.');
        $campaign->forceFill(['status' => 'draft', 'scheduled_at' => null])->save();

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
        abort_unless(in_array($campaign->status, ['draft', 'scheduled']), 422, 'Only draft or scheduled campaigns can be edited.');
        $campaign->update($this->validated($request));

        return $campaign;
    }

    public function destroy(Campaign $campaign)
    {
        abort_if($campaign->status === 'sending', 422, 'Campaign is currently sending.');
        $campaign->delete();

        return response()->noContent();
    }

    /** Send now, or schedule if scheduled_at is in the future. */
    public function send(Campaign $campaign)
    {
        abort_unless(in_array($campaign->status, ['draft', 'scheduled']), 422, 'Campaign already sent.');

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            $campaign->forceFill(['status' => 'scheduled'])->save();
        } else {
            $campaign->forceFill(['status' => 'sending'])->save();
            SendCampaignJob::dispatch($campaign->id);
        }

        return $campaign->fresh();
    }

    /** Stop a campaign that is sending: what has gone out stays sent, everything still waiting is skipped. */
    public function cancel(Campaign $campaign)
    {
        abort_unless($campaign->status === 'sending', 422, 'Only a campaign that is sending can be stopped.');

        $campaign->logModel()::where('campaign_id', $campaign->id)->where('status', 'queued')
            ->update(['status' => 'skipped', 'error' => 'Sending was stopped before this was sent.']);
        $campaign->forceFill(['status' => 'cancelled', 'completed_at' => now()])->save();

        return ApiResponse::ok($campaign->fresh()->loadCount(Campaign::logCountSpec())->logTotals(), 'Sending stopped. What already went out stays sent; the rest was skipped.');
    }

    /** A copy to start from: same content and audience, back to draft with no schedule. */
    public function duplicate(Campaign $campaign)
    {
        $copy = $campaign->replicate(['scheduled_at', 'started_at', 'completed_at']);
        $copy->name = mb_substr('Copy of '.$campaign->name, 0, 255);
        $copy->forceFill(['status' => 'draft'])->save();

        return response($copy, 201);
    }

    /** What sending would do, and anything worth fixing first. */
    public function check(Campaign $campaign)
    {
        $isEmail = $campaign->type === 'email';
        $col = $isEmail ? 'people.email' : 'people.phone';
        $base = fn () => $campaign->audienceQuery();

        $total = $base()->count();
        $reach = $base()->where('people.is_subscribed', true)->whereNotNull($col)->where($col, '!=', '')->count();
        $unsub = $base()->where('people.is_subscribed', false)->count();

        $warnings = [];
        if ($isEmail) {
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
            $warnings[] = 'Nobody in this audience can receive it: they are unsubscribed or have no '.($isEmail ? 'email address' : 'phone number').'.';
        }

        return ['audience' => $total, 'reach' => $reach, 'unsubscribed' => $unsub, 'skipped' => $total - $reach, 'warnings' => $warnings];
    }

    /** Sends a single test message synchronously to the given address/number. */
    public function test(Request $request, Campaign $campaign, MessageRenderer $renderer, SmsManager $sms)
    {
        $data = $request->validate(['to' => 'required|string']);
        $company = $request->user()->company;
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
        $data = $request->validate(['type' => 'required|in:email,sms', 'subject' => 'nullable|string', 'body' => 'required|string']);
        $company = $request->user()->company;
        $sample = new Person(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com', 'phone' => '+61400000000']);
        $sample->unsubscribe_token = 'preview';

        if ($data['type'] === 'email') {
            [$subject, $html] = $renderer->renderEmail($data['subject'] ?? '', $data['body'], $company, $sample);

            return compact('subject', 'html');
        }

        return ['text' => $renderer->renderSms($data['body'], $company, $sample)];
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms',
            'template_id' => 'nullable|integer',
            'people_list_id' => 'nullable|integer',
            'segment_id' => 'nullable|integer',
            'tag_id' => 'nullable|integer',
            'subject' => 'nullable|string|max:255|required_if:type,email',
            'body' => 'required|string',
            'design' => 'nullable|array',
            'attachments' => 'nullable|array|max:10',
            'attachments.*.token' => 'required|string|size:32',
            'scheduled_at' => 'nullable|date',
        ]);

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
