<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactList;
use App\Services\MessageRenderer;
use App\Services\Sms\SmsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CampaignController extends Controller
{
    public function index()
    {
        return Campaign::with('list:id,name')->withCount([
            'logs', 'logs as sent_count' => fn ($q) => $q->where('status', 'sent'),
            'logs as failed_count' => fn ($q) => $q->where('status', 'failed'),
            'logs as opened_count' => fn ($q) => $q->whereNotNull('opened_at'),
        ])->latest()->paginate(20);
    }

    public function show(Campaign $campaign)
    {
        return $campaign->load('list:id,name')->loadCount([
            'logs', 'logs as sent_count' => fn ($q) => $q->where('status', 'sent'),
            'logs as failed_count' => fn ($q) => $q->where('status', 'failed'),
            'logs as skipped_count' => fn ($q) => $q->where('status', 'skipped'),
            'logs as opened_count' => fn ($q) => $q->whereNotNull('opened_at'),
        ]);
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

    /** Sends a single test message synchronously to the given address/number. */
    public function test(Request $request, Campaign $campaign, MessageRenderer $renderer, SmsManager $sms)
    {
        $data = $request->validate(['to' => 'required|string']);
        $business = $request->user()->business;
        $sample = new Contact(['first_name' => 'Test', 'last_name' => 'User', 'email' => $data['to']]);
        $sample->unsubscribe_token = 'test';

        if ($campaign->type === 'email') {
            $request->validate(['to' => 'email']);
            [$subject, $html] = $renderer->renderEmail('[TEST] '.$campaign->subject, $campaign->body, $business, $sample);
            Mail::html($html, function ($m) use ($data, $business, $subject) {
                $m->to($data['to'])->subject($subject);
                if ($business->from_email) {
                    $m->from($business->from_email, $business->from_name ?: $business->name);
                }
            });
        } else {
            $sms->forBusiness($business)->send($data['to'], $renderer->renderSms($campaign->body, $business, $sample));
        }

        return ['ok' => true];
    }

    /** Live preview of a body using sample data. */
    public function preview(Request $request, MessageRenderer $renderer)
    {
        $data = $request->validate(['type' => 'required|in:email,sms', 'subject' => 'nullable|string', 'body' => 'required|string']);
        $business = $request->user()->business;
        $sample = new Contact(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com', 'phone' => '+61400000000']);
        $sample->unsubscribe_token = 'preview';

        if ($data['type'] === 'email') {
            [$subject, $html] = $renderer->renderEmail($data['subject'] ?? '', $data['body'], $business, $sample);

            return compact('subject', 'html');
        }

        return ['text' => $renderer->renderSms($data['body'], $business, $sample)];
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms',
            'template_id' => 'nullable|integer',
            'contact_list_id' => 'nullable|integer',
            'subject' => 'nullable|string|max:255|required_if:type,email',
            'body' => 'required|string',
            'design' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
        ]);

        if (! empty($data['contact_list_id'])) {
            abort_unless(ContactList::whereKey($data['contact_list_id'])->exists(), 422, 'Invalid list.');
        }

        return $data;
    }
}
