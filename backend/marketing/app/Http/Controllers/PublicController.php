<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\LandingPage;
use App\Models\EmailLog;
use App\Models\Tag;
use App\Services\Automation;
use App\Jobs\SendFormNotificationJob;
use App\Jobs\SendMessageJob;
use App\Services\FormFields;
use App\Services\FormRenderer;
use App\Services\MessageRenderer;
use App\Services\WebhookDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Unauthenticated: hosted forms, landing pages, and the sign-up submit endpoint. */
class PublicController extends Controller
{
    /** CSP that only lets our own inline script (matching the nonce) run — page HTML written by a company can't execute scripts. */
    protected function html(string $body, string $nonce)
    {
        return response($body, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; img-src * data:; style-src 'unsafe-inline'; font-src *; script-src 'nonce-{$nonce}'; connect-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors *",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function form(string $slug)
    {
        $form = Form::withoutGlobalScope('company')->where('slug', $slug)->where('active', true)->firstOrFail();
        $form->increment('views');
        $nonce = base64_encode(random_bytes(16));

        return $this->html(FormRenderer::page($form, $nonce), $nonce);
    }

    public function page(string $slug)
    {
        $page = LandingPage::withoutGlobalScope('company')->where('slug', $slug)->where('published', true)->firstOrFail();
        $page->increment('views');
        $nonce = base64_encode(random_bytes(16));

        $company = Company::find($page->company_id);
        // merge tags (e.g. the company name) are escaped: a company name containing markup must not become page markup
        $vars = array_map(fn ($v) => e($v), (new MessageRenderer)->vars($company, null, 'email'));
        $html = (new MessageRenderer)->replace($page->html, $vars);

        // Each form section of the page builder leaves <!--fc-form:ID--> (ID 0 = no form chosen). A bare <!--fc-form-->
        // (pages made before forms were chosen per section) means the form attached to the page. A form can appear many times.
        $n = 0;
        $seen = [];
        $formBlock = function (?int $id) use ($page, $nonce, &$n, &$seen): string {
            $form = $id ? Form::withoutGlobalScope('company')->where('company_id', $page->company_id)->where('active', true)->find($id) : null;
            if (! $form) {
                return '';
            }
            $n++;
            if (! isset($seen[$form->id])) {
                $seen[$form->id] = true;
                $form->increment('views');
            }
            $style = $form->resolvedStyle();
            $scope = 'fcs-'.$n;

            // wrapped as a card using the form's own colours and fonts
            return '<style>'.FormRenderer::css($style, '.'.$scope).'</style><div class="'.$scope.'"><div style="max-width:'.$style['width'].'px;margin:0 auto;background:'.$style['cardBg'].';color:'.$style['text'].';padding:28px;border-radius:'.$style['radius'].'px;box-shadow:0 2px 12px rgba(0,0,0,.12);text-align:left">'
                .FormRenderer::fragment($form, $nonce, $n > 1 ? '-'.$n : '').'</div></div>';
        };
        $hadMarker = false;
        $html = preg_replace_callback('/<!--fc-form(?::(\d+))?-->/', function ($m) use ($page, $formBlock, &$hadMarker) {
            $hadMarker = true;

            return $formBlock(isset($m[1]) ? (int) $m[1] : $page->form_id);
        }, $html);
        if (! $hadMarker && ($extra = $formBlock($page->form_id)) !== '') { // old pages: the form goes at the end
            $at = stripos($html, '</body>');
            $html = $at !== false ? substr_replace($html, $extra, $at, 0) : $html.$extra;
        }

        // the "browser tab title" set when publishing wins over the placeholder title in the generated HTML
        if (filled($page->title)) {
            $title = '<title>'.e($page->title).'</title>';
            $html = preg_match('~<title>.*?</title>~is', $html)
                ? preg_replace_callback('~<title>.*?</title>~is', fn () => $title, $html, 1)
                : preg_replace_callback('~<head[^>]*>~i', fn ($m) => $m[0].$title, $html, 1);
        }

        return $this->html($html, $nonce);
    }

    public function submit(Request $request, string $slug)
    {
        $form = Form::withoutGlobalScope('company')->where('slug', $slug)->where('active', true)->firstOrFail();

        // honeypot: pretend success so bots learn nothing
        if (filled($request->input('website'))) {
            return ['message' => $form->success_message];
        }

        // one rule per input field, chosen by its type; headings/paragraphs carry no data
        $fields = collect($form->builderFields())->filter(fn ($f) => FormFields::isInput($f['type']))->values();
        $rules = [];
        $names = [];
        foreach ($fields as $f) {
            $type = $f['type'];
            $rules[$f['key']] = $type === 'checkbox'
                ? ($f['required'] ? ['accepted'] : ['nullable', 'boolean'])
                : array_merge([$f['required'] ? 'required' : 'nullable'], match ($type) {
                    'email' => ['email', 'max:255'],
                    'phone' => ['string', 'max:30'],
                    'textarea' => ['string', 'max:2000'],
                    'number' => ['numeric'],
                    'date' => ['date'],
                    'dropdown', 'radio' => ['string', Rule::in($f['options'])],
                    default => ['string', 'max:255'], // first_name, last_name, text
                });
            $names[$f['key']] = mb_strtolower($f['label']); // error messages read "The email field…" instead of the internal key
        }
        $data = $request->validate($rules, [], $names);

        // what was submitted, in form order: this is what the inbox and the notification email show
        $answers = [];
        foreach ($fields as $f) {
            $v = $data[$f['key']] ?? null;
            $answers[] = ['label' => $f['label'], 'type' => $f['type'], 'value' => $f['type'] === 'checkbox' ? ($v ? 'Yes' : 'No') : (string) ($v ?? '')];
        }

        // questions marked "save on the contact" fill that contact's custom fields (usable as merge tags);
        // everything else stays in the form's inbox only
        $custom = [];
        foreach ($fields as $f) {
            if ($form->save_contact && $f['to_contact'] && ! isset(FormFields::STANDARD[$f['type']]) && isset($data[$f['key']]) && $data[$f['key']] !== '') {
                $custom[$f['key']] = $f['type'] === 'checkbox' ? ($data[$f['key']] ? 'Yes' : 'No') : (string) $data[$f['key']];
            }
        }
        $email = isset($data['email']) ? Str::lower(trim($data['email'])) : null;
        $phone = $data['phone'] ?? null;

        $biz = $form->company_id;
        $contact = null;
        $created = false;
        $newLists = $newTags = [];

        // "Save to contacts" is optional: a contact form can simply be an inbox
        if ($form->save_contact) {
            abort_if(! $email && ! $phone, 422, 'Please enter an email address or phone number.');

            $contact = Contact::withoutGlobalScope('company')->where('company_id', $biz)
                ->where(fn ($q) => $email ? $q->where('email', $email) : $q->where('phone', $phone))->first();
            $created = ! $contact;

            if ($created) {
                // company_id is a guarded attribute, so set it explicitly (this request has no signed-in user to stamp it).
                // email/SMS marketing consent follows the form: a newsletter sign-up opts people in, a contact form does not.
                $contact = (new Contact)->forceFill([
                    'company_id' => $biz, 'email' => $email, 'phone' => $phone, 'source' => 'form',
                    'first_name' => $data['first_name'] ?? null, 'last_name' => $data['last_name'] ?? null,
                    'email_subscribed' => (bool) $form->subscribe, 'sms_subscribed' => (bool) $form->subscribe,
                ]);
                $contact->save();
            } else {
                // fill blanks only; never overwrite what we already know, and never re-subscribe someone who opted out
                $contact->fill(array_filter([
                    'first_name' => $contact->first_name ?: ($data['first_name'] ?? null),
                    'last_name' => $contact->last_name ?: ($data['last_name'] ?? null),
                    'phone' => $contact->phone ?: $phone,
                ]));
                $contact->save();
            }
            $contact->setCustom($custom, blanksOnly: ! $created); // a repeat submission never overwrites what we already know

            $listIds = ContactList::withoutGlobalScope('company')->where('company_id', $biz)->whereIn('id', $form->list_ids ?? [])->pluck('id')->all();
            $tagIds = Tag::withoutGlobalScope('company')->where('company_id', $biz)->whereIn('id', $form->tag_ids ?? [])->pluck('id')->all();
            $newLists = array_diff($listIds, $contact->lists()->pluck('contact_lists.id')->all());
            $newTags = array_diff($tagIds, $contact->tags()->pluck('tags.id')->all());
            $contact->lists()->syncWithoutDetaching($listIds);
            $contact->tags()->syncWithoutDetaching($tagIds);
        }

        $submission = FormSubmission::create(['company_id' => $biz, 'form_id' => $form->id, 'contact_id' => $contact?->id, 'data' => $answers]);
        $form->increment('submissions');

        if ($contact) {
            Automation::contactChanged($contact, $created, $newLists, $newTags);
            Automation::fire('form_submitted', $contact, ['id' => $form->id]);
        }
        WebhookDispatcher::dispatch($biz, 'form.submitted', [
            'form' => $form->name, 'type' => $form->type,
            'contact' => $contact ? Automation::contactPayload($contact) : null, 'answers' => $answers,
        ]);

        // email the submission to the people who should see it (queued, so a slow mail server never slows the visitor)
        if (! empty($form->notify_emails)) {
            SendFormNotificationJob::dispatch($submission->id);
        }

        // confirmation to the person: sent through the normal message pipeline, so it shows up in Message Logs
        if ($form->autoreply && $email) {
            $subject = $form->autoreply_subject;
            $text = $form->autoreply_body;
            if (! $contact) {
                // no contact record to read the name from: fill the personal tags from what they just typed
                $typed = ['first_name' => $data['first_name'] ?? '', 'last_name' => $data['last_name'] ?? '', 'email' => $email, 'phone' => $phone ?? ''];
                $typed['full_name'] = trim($typed['first_name'].' '.$typed['last_name']) ?: 'there';
                $typed['first_name'] = $typed['first_name'] ?: 'there';
                $subject = (new MessageRenderer)->replace($subject, $typed);
                $text = (new MessageRenderer)->replace($text, $typed);
            }
            $log = EmailLog::create([
                'company_id' => $biz, 'contact_id' => $contact?->id, 'source' => 'form', 'recipient' => $email,
                'subject' => $subject, 'body' => nl2br(e($text)).($form->autoreply_attachments ? '<!--fc-attach:'.implode(',', array_column($form->autoreply_attachments, 'token')).'-->' : ''), 'status' => 'queued', // the marker makes SendMessageJob attach the files
            ]);
            SendMessageJob::dispatch($log->id);
        }

        return ['message' => $form->success_message, 'redirect' => $form->redirect_url];
    }
}
