<?php

namespace App\Http\Controllers;

use App\Models\ContactField;
use App\Models\ContactList;
use App\Models\Form;
use App\Models\Tag;
use App\Services\Attachments;
use App\Services\FormFields;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormController extends Controller
{
    public function index()
    {
        return Form::latest()->get()->map(fn ($f) => $this->present($f));
    }

    public function show(Form $form)
    {
        return $this->present($form);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        do {
            $slug = Str::lower(Str::random(10));
        } while (Form::withoutGlobalScope('company')->where('slug', $slug)->exists());

        // slug is a guarded attribute (clients must not choose it), so set it explicitly
        $form = (new Form)->forceFill($data + ['slug' => $slug]);
        $form->save();

        return response($this->present($form), 201);
    }

    /** A copy to start from: same fields and settings, its own address, no views or submissions yet. */
    public function duplicate(Form $form)
    {
        do {
            $slug = Str::lower(Str::random(10));
        } while (Form::withoutGlobalScope('company')->where('slug', $slug)->exists());

        $copy = $form->replicate();
        $copy->name = mb_substr('Copy of '.$form->name, 0, 255);
        $copy->forceFill(['slug' => $slug, 'views' => 0, 'submissions' => 0])->save();

        return response($this->present($copy), 201);
    }

    public function update(Request $request, Form $form)
    {
        $form->update($this->validated($request));

        return $this->present($form);
    }

    public function destroy(Form $form)
    {
        $form->delete();

        return response()->noContent();
    }

    /** What the builder and the list need: fields in the current shape, resolved style, public URL. */
    protected function present(Form $form): Form
    {
        $form->setAttribute('fields', $form->builderFields());
        $form->setAttribute('style', $form->resolvedStyle());
        $form->setAttribute('notify_emails', $form->notify_emails ?? []);
        $form->setAttribute('url', url('/f/'.$form->slug));

        return $form;
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'fields' => 'nullable|array|min:1|max:30',
            'fields.*.id' => 'nullable|string|max:40',
            'fields.*.type' => ['required', Rule::in(FormFields::types())],
            'fields.*.key' => ['nullable', 'regex:/^[a-z][a-z0-9_]{0,39}$/'],
            'fields.*.label' => 'required|string|max:1000',
            'fields.*.placeholder' => 'nullable|string|max:120',
            'fields.*.help' => 'nullable|string|max:200',
            'fields.*.required' => 'boolean',
            'fields.*.width' => ['nullable', Rule::in(FormFields::WIDTHS)],
            'fields.*.to_contact' => 'boolean',
            'fields.*.options' => 'nullable|array|max:30',
            'fields.*.options.*' => 'nullable|string|max:100', // blanks are dropped below
            'button_text' => 'nullable|string|max:60',
            'success_message' => 'nullable|string|max:255',
            'existing_message' => 'nullable|string|max:500',
            'redirect_url' => 'nullable|url:http,https|max:500',
            'type' => ['nullable', Rule::in(FormFields::FORM_TYPES)],
            'save_contact' => 'boolean',
            'subscribe' => 'boolean',
            'notify_emails' => 'nullable|array|max:5',
            'notify_emails.*' => 'email|max:255',
            'autoreply' => 'boolean',
            'autoreply_subject' => 'nullable|string|max:255',
            'autoreply_body' => 'nullable|string|max:5000',
            'autoreply_attachments' => 'nullable|array|max:10',
            'autoreply_attachments.*.token' => 'required|string|size:32',
            'style' => 'nullable|array',
            'style.pageBg' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'style.cardBg' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'style.text' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'style.accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'style.btnText' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'style.radius' => 'nullable|integer|min:0|max:30',
            'style.width' => 'nullable|integer|min:320|max:720',
            'style.font' => ['nullable', Rule::in(array_keys(FormFields::FONTS))],
            'list_ids' => 'nullable|array',
            'list_ids.*' => 'integer',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer',
            'active' => 'boolean',
        ]);

        $data['type'] = $data['type'] ?? 'newsletter';
        $data['save_contact'] = $data['save_contact'] ?? true;
        $data['subscribe'] = $data['subscribe'] ?? ($data['type'] === 'newsletter');
        $data['autoreply'] = $data['autoreply'] ?? false;
        $data['notify_emails'] = array_values(array_unique(array_map('strtolower', $data['notify_emails'] ?? [])));
        $data['autoreply_subject'] = trim((string) ($data['autoreply_subject'] ?? '')) ?: null;
        $data['autoreply_body'] = trim((string) ($data['autoreply_body'] ?? '')) ?: null;
        // keep only files this company really uploaded (and only while the auto-reply is on)
        $files = $data['autoreply'] ? Attachments::in('<!--fc-attach:'.implode(',', array_column($data['autoreply_attachments'] ?? [], 'token')).'-->', request()->user()->company_id) : [];
        $data['autoreply_attachments'] = $files ? array_map(fn ($f) => ['token' => $f['token'], 'name' => $f['name'], 'size' => $f['size']], $files) : null;

        $data['fields'] = $this->checkedFields($data['fields'] ?? FormFields::defaults(), $data['save_contact'] || $data['autoreply']); // omitted = a simple first name + email form
        $data['fields'] = $this->linkToContactFields($data['fields'], $data['save_contact']);
        abort_if($data['autoreply'] && (! $data['autoreply_subject'] || ! $data['autoreply_body']), 422, 'Write a subject and a message for the auto-reply, or switch it off.');
        abort_if($data['autoreply'] && ! collect($data['fields'])->contains(fn ($f) => $f['type'] === 'email'), 422, 'An auto-reply needs an Email field so we know where to send it.');
        $data['style'] = FormFields::style($data['style'] ?? null);
        // only ids that belong to this company
        $data['list_ids'] = ContactList::whereIn('id', $data['list_ids'] ?? [])->pluck('id')->all();
        $data['tag_ids'] = Tag::whereIn('id', $data['tag_ids'] ?? [])->pluck('id')->all();
        $data['button_text'] = ($data['button_text'] ?? '') ?: 'Subscribe';
        $data['success_message'] = ($data['success_message'] ?? '') ?: 'Thanks for subscribing!';
        $data['existing_message'] = trim((string) ($data['existing_message'] ?? '')) ?: null; // empty = everyone sees the same thank-you
        $data['redirect_url'] = ($data['redirect_url'] ?? '') ?: null;

        return $data;
    }

    /**
     * Questions marked "save on the contact" become managed custom fields (Audience → Custom Fields).
     * Two forms asking the same thing share one field; if the same name would mean a different kind of
     * answer (say a dropdown vs free text) the question gets its own field instead of corrupting the old one.
     */
    protected function linkToContactFields(array $fields, bool $saveContact): array
    {
        if (! $saveContact) {
            return $fields;
        }
        $existing = ContactField::get()->keyBy('key');

        foreach ($fields as &$f) {
            if (! in_array($f['type'], FormFields::INPUTS, true) || ! $f['to_contact']) {
                continue;
            }
            $field = $existing[$f['key']] ?? null;
            if ($field && $field->type !== $f['type']) {
                $f['key'] = FormFields::keyFor($f['label'], array_merge($existing->keys()->all(), array_column($fields, 'key')));
                $field = null;
            }

            $options = in_array($f['type'], ['dropdown', 'radio'], true) ? $f['options'] : null;
            if (! $field) {
                $field = (new ContactField)->forceFill(['key' => $f['key'], 'label' => $f['label'], 'type' => $f['type'], 'options' => $options]);
                $field->save();
                $existing[$f['key']] = $field;
            } elseif ($options && array_diff($options, $field->options ?? [])) {
                $field->update(['options' => array_values(array_unique(array_merge($field->options ?? [], $options)))]); // keep every choice any form offers valid
            }
        }
        unset($f);

        return $fields;
    }

    /** Rules that need the whole list: unique standard fields, usable custom keys, options, an email/phone field. */
    protected function checkedFields(array $fields, bool $needsContact = true): array
    {
        $out = [];
        $types = [];
        $keys = [];

        foreach ($fields as $i => $f) {
            $n = $i + 1;
            $f = FormFields::field($f);
            $type = $f['type'];

            if (isset(FormFields::STANDARD[$type])) {
                abort_if(in_array($type, $types, true), 422, 'Field '.$n.': you can only add "'.FormFields::STANDARD[$type].'" once.');
                $types[] = $type;
            } elseif (in_array($type, FormFields::INPUTS, true)) {
                // custom answers are stored on the contact under `key`
                $f['key'] = $f['key'] && ! in_array($f['key'], FormFields::RESERVED, true) && ! in_array($f['key'], $keys, true)
                    ? $f['key']
                    : FormFields::keyFor($f['label'], array_merge($keys, array_column($out, 'key')));
                $keys[] = $f['key'];
            } else {
                $f['key'] = null; // heading / paragraph: display only
                $f['required'] = false;
            }

            if (in_array($type, ['dropdown', 'radio'], true)) {
                $f['options'] = array_values(array_unique(array_filter(array_map(fn ($o) => trim((string) $o), $f['options']), 'strlen')));
                abort_if(count($f['options']) < 1, 422, 'Field '.$n.' ("'.$f['label'].'"): add at least one option.');
            } else {
                $f['options'] = [];
            }

            $out[] = $f;
        }

        // only forms that save a contact (or reply to people) must collect a way to reach them
        abort_if($needsContact && ! collect($out)->contains(fn ($f) => in_array($f['type'], ['email', 'phone'], true)), 422, 'Add an email or phone field so you can reach the people who sign up.');

        return $out;
    }
}
