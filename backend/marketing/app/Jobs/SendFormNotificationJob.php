<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\CompanyMailer;
use App\Services\SendLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Emails every address in the form's "notify" list a copy of one submission. */
class SendFormNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $submissionId) {}

    public function handle(): void
    {
        $sub = FormSubmission::withoutGlobalScope('company')->find($this->submissionId);
        $form = $sub ? Form::withoutGlobalScope('company')->find($sub->form_id) : null;
        if (! $form || empty($form->notify_emails)) {
            return;
        }
        $company = Company::findOrFail($form->company_id);

        // the submitter's address becomes Reply-To, so "reply" in your inbox answers them directly
        $replyTo = collect($sub->data)->first(fn ($a) => ($a['type'] ?? '') === 'email' && filter_var($a['value'] ?? '', FILTER_VALIDATE_EMAIL))['value'] ?? null;

        $rows = '';
        foreach ($sub->data as $a) {
            if (($a['value'] ?? '') === '') {
                continue;
            }
            $rows .= '<tr><td style="padding:6px 14px 6px 0;color:#6b7280;vertical-align:top;white-space:nowrap">'.e($a['label']).'</td>'
                .'<td style="padding:6px 0;color:#111;white-space:pre-wrap">'.e($a['value']).'</td></tr>';
        }
        $html = '<div style="font-family:Arial,sans-serif;font-size:15px;color:#111;max-width:560px">'
            .'<h2 style="margin:0 0 4px">New submission: '.e($form->name).'</h2>'
            .'<p style="margin:0 0 16px;color:#6b7280;font-size:13px">'.e($sub->created_at->toDayDateTimeString()).'</p>'
            .'<table style="border-collapse:collapse">'.($rows ?: '<tr><td>No answers were filled in.</td></tr>').'</table></div>';

        foreach ($form->notify_emails as $to) {
            try {
                SendLog::run($company, 'email', $to, 'New submission: '.$form->name, $html, 'notification', fn () => CompanyMailer::for($company)->html($html, function ($m) use ($to, $company, $form, $replyTo) {
                    $m->to($to)->subject('New submission: '.$form->name);
                    if ($company->from_email) {
                        $m->from($company->from_email, $company->from_name ?: $company->name);
                    }
                    if ($replyTo) {
                        $m->replyTo($replyTo);
                    }
                }));
            } catch (Throwable $e) {
                // one bad address must not stop the others; the submission itself is already saved
                Log::warning("Form notification to {$to} failed: ".$e->getMessage());
            }
        }
    }
}
