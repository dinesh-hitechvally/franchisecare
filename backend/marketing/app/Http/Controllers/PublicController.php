<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Person;
use App\Models\PeopleList;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\LandingPage;
use App\Models\EmailLog;
use App\Models\Tag;
use App\Services\Automation;
use App\Jobs\SendFormNotificationJob;
use App\Jobs\SendMessageJob;
use App\Services\Captcha;
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

    /** Adds the field's own checks (length, range, pattern, allowed email domains) to its validation rules; one custom message can replace the defaults. */
    protected function withFieldRules(array $rules, array $f, array &$messages): array
    {
        $r = $f['rules'] ?? [];
        if (! $r) {
            return $rules;
        }
        $key = $f['key'];
        $custom = $r['message'] ?? null;
        $say = function (string $rule) use ($custom, $key, &$messages) {
            if ($custom) {
                $messages["$key.$rule"] = $custom;
            }
        };

        if (isset($r['max_length'])) {
            $rules = array_values(array_filter($rules, fn ($x) => ! (is_string($x) && str_starts_with($x, 'max:')))); // replaces the built-in cap
            $rules[] = 'max:'.$r['max_length'];
            $say('max');
        }
        if (isset($r['min_length'])) {
            $rules[] = 'min:'.$r['min_length'];
            $say('min');
        }
        if ($f['type'] === 'number') {
            foreach (['min', 'max'] as $k) {
                if (isset($r[$k])) {
                    $rules[] = $k.':'.$r[$k];
                    $say($k);
                }
            }
        }
        if ($f['type'] === 'date') {
            if (isset($r['min'])) {
                $rules[] = 'after_or_equal:'.$r['min'];
                $say('after_or_equal');
            }
            if (isset($r['max'])) {
                $rules[] = 'before_or_equal:'.$r['max'];
                $say('before_or_equal');
            }
        }
        if (isset($r['pattern'])) {
            $pattern = FormFields::delimited($r['pattern']);
            $rules[] = function ($attribute, $value, $fail) use ($pattern, $custom) {
                if (@preg_match($pattern, (string) $value) !== 1) {
                    $fail($custom ?: 'The :attribute is not in the expected format.');
                }
            };
        }
        if (isset($r['allowed_domains'])) {
            $domains = $r['allowed_domains'];
            $rules[] = function ($attribute, $value, $fail) use ($domains, $custom) {
                $domain = strtolower(substr(strrchr((string) $value, '@') ?: '', 1));
                if (! in_array($domain, $domains, true)) {
                    $fail($custom ?: 'The :attribute must be an address at '.implode(', ', $domains).'.');
                }
            };
        }

        return $rules;
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
        $messages = [];
        foreach ($fields as $f) {
            $type = $f['type'];
            $rules[$f['key']] = in_array($type, ['checkbox', 'subscribe'], true)
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
            if ($type === FormFields::CAPTCHA) {
                $token = (string) $request->input('captcha_token');
                $rules['captcha'] = ['required', function ($attribute, $value, $fail) use ($token) {
                    if ($problem = Captcha::check($token, $value)) {
                        $fail($problem);
                    }
                }];
                $names['captcha'] = 'answer';

                continue;
            }
            $rules[$f['key']] = $this->withFieldRules($rules[$f['key']], $f, $messages);
            $names[$f['key']] = mb_strtolower($f['label']); // error messages read "The email field…" instead of the internal key
        }
        $messages['captcha.required'] = 'Please answer the small sum to show you are human.';
        $data = $request->validate($rules, $messages, $names);
        Captcha::consume($request->input('captcha_token')); // the sum is solved: it cannot be used again

        // what was submitted, in form order: this is what the inbox and the notification email show
        $answers = [];
        foreach ($fields->where('type', '!=', FormFields::CAPTCHA) as $f) { // the captcha is a check, not an answer
            $v = $data[$f['key']] ?? null;
            $answers[] = ['label' => $f['label'], 'type' => $f['type'], 'value' => in_array($f['type'], ['checkbox', 'subscribe'], true) ? ($v ? 'Yes' : 'No') : (string) ($v ?? '')];
        }

        // questions marked "save on the person" fill that person's custom fields (usable as merge tags);
        // everything else stays in the form's inbox only
        $custom = [];
        foreach ($fields as $f) {
            if ($form->save_person && $f['to_person'] && ! isset(FormFields::STANDARD[$f['type']]) && isset($data[$f['key']]) && $data[$f['key']] !== '') {
                $custom[$f['key']] = $f['type'] === 'checkbox' ? ($data[$f['key']] ? 'Yes' : 'No') : (string) $data[$f['key']];
            }
        }
        $email = isset($data['email']) ? Str::lower(trim($data['email'])) : null;
        $phone = $data['phone'] ?? null;

        // with a "Subscribe me to the newsletter" tick box, only people who ticked it are subscribed (it never re-subscribes someone who opted out)
        $hasBox = $fields->contains('type', FormFields::SUBSCRIBE);
        $optedIn = ! $hasBox || ! empty($data[FormFields::SUBSCRIBE]);

        $biz = $form->company_id;
        $person = null;
        $created = false;
        $newLists = $newTags = [];

        // "Save to people" is optional: a contact form can simply be an inbox
        if ($form->save_person) {
            abort_if(! $email && ! $phone, 422, 'Please enter an email address or phone number.');

            $person = Person::withoutGlobalScope('company')->where('company_id', $biz)
                ->where(fn ($q) => $email ? $q->where('email', $email) : $q->where('phone', $phone))->first();
            $created = ! $person;

            if ($created) {
                // company_id is a guarded attribute, so set it explicitly (this request has no signed-in user to stamp it).
                // email/SMS marketing consent follows the form: a newsletter sign-up opts people in, a contact form does not.
                $person = (new Person)->forceFill([
                    'company_id' => $biz, 'email' => $email, 'phone' => $phone, 'source' => 'form',
                    'first_name' => $data['first_name'] ?? null, 'last_name' => $data['last_name'] ?? null,
                    'is_subscribed' => $hasBox ? $optedIn : (bool) $form->subscribe, // the tick box decides; without one the form's own default applies
                ]);
                $person->save();
            } else {
                // fill blanks only; never overwrite what we already know, and never re-subscribe someone who opted out
                $person->fill(array_filter([
                    'first_name' => $person->first_name ?: ($data['first_name'] ?? null),
                    'last_name' => $person->last_name ?: ($data['last_name'] ?? null),
                    'phone' => $person->phone ?: $phone,
                ]));
                $person->save();
            }
            $person->setCustom($custom, blanksOnly: ! $created); // a repeat submission never overwrites what we already know

            $listIds = PeopleList::withoutGlobalScope('company')->where('company_id', $biz)->whereIn('id', $form->list_ids ?? [])->pluck('id')->all();
            $tagIds = Tag::withoutGlobalScope('company')->where('company_id', $biz)->whereIn('id', $form->tag_ids ?? [])->pluck('id')->all();
            $newLists = array_diff($listIds, $person->lists()->pluck('people_lists.id')->all());
            $newTags = array_diff($tagIds, $person->tags()->pluck('tags.id')->all());
            $person->lists()->syncWithoutDetaching($listIds);
            $person->tags()->syncWithoutDetaching($tagIds);
        }

        // whether this person was already a person (null when the form does not save people): shown in the submissions list
        $existed = $person ? ! $created : null;
        $submission = FormSubmission::create(['company_id' => $biz, 'form_id' => $form->id, 'person_id' => $person?->id, 'person_existed' => $existed, 'data' => $answers]);
        $form->increment('submissions');

        if ($person) {
            Automation::personChanged($person, $created, $newLists, $newTags);
            Automation::fire('form_submitted', $person, ['id' => $form->id]);
        }
        WebhookDispatcher::dispatch($biz, 'form.submitted', [
            'form' => $form->name, 'type' => $form->type,
            'person' => $person ? Automation::personPayload($person) : null, 'answers' => $answers,
        ]);

        // email the submission to the people who should see it (queued, so a slow mail server never slows the visitor)
        if (! empty($form->notify_emails)) {
            SendFormNotificationJob::dispatch($submission->id);
        }

        // confirmation to the person: sent through the normal message pipeline, so it shows up in Message Logs
        if ($form->autoreply && $email) {
            $subject = $form->autoreply_subject;
            $text = $form->autoreply_body;
            if (! $person) {
                // no person record to read the name from: fill the personal tags from what they just typed
                $typed = ['first_name' => $data['first_name'] ?? '', 'last_name' => $data['last_name'] ?? '', 'email' => $email, 'phone' => $phone ?? ''];
                $typed['full_name'] = trim($typed['first_name'].' '.$typed['last_name']) ?: 'there';
                $typed['first_name'] = $typed['first_name'] ?: 'there';
                $subject = (new MessageRenderer)->replace($subject, $typed);
                $text = (new MessageRenderer)->replace($text, $typed);
            }
            $log = EmailLog::create([
                'company_id' => $biz, 'person_id' => $person?->id, 'source' => 'form', 'recipient' => $email,
                'subject' => $subject, 'body' => nl2br(e($text)).($form->autoreply_attachments ? '<!--fc-attach:'.implode(',', array_column($form->autoreply_attachments, 'token')).'-->' : ''), 'status' => 'queued', // the marker makes SendMessageJob attach the files
            ]);
            SendMessageJob::dispatch($log->id);
        }

        // Telling a visitor "you are already one of our people" shows anyone who is on the list, so it is only said when the form's owner
        // wrote a message for it. Without one, everybody gets the same thank-you.
        $known = $existed === true && filled($form->existing_message);

        return ['message' => $known ? $form->existing_message : $form->success_message, 'redirect' => $form->redirect_url, 'existing' => $known];
    }
}
