<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactField;
use App\Models\ContactList;
use App\Services\FormFields;
use App\Models\Tag;
use App\Services\Automation;
use App\Services\Subscriptions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Public integration API (/api/v1) — authenticated with an API key from Settings → API Integrations. */
class ApiV1Controller extends Controller
{
    /** Create or update a contact by email (or phone). Lists/tags are matched by name and created when missing. */
    public function upsert(Request $request)
    {
        $data = $request->validate([
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'custom_fields' => 'nullable|array|max:30',
            'custom_fields.*' => ['nullable', 'max:2000', fn ($attr, $v, $fail) => is_array($v) ? $fail('Custom field values must be text or numbers.') : null],
            'lists' => 'nullable|array|max:20',
            'lists.*' => 'string|max:255',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:60',
        ]);
        $email = isset($data['email']) ? Str::lower($data['email']) : null;

        $contact = Contact::where(fn ($q) => $email ? $q->where('email', $email) : $q->where('phone', $data['phone']))->first();
        $created = ! $contact;
        $fields = array_filter(['first_name' => $data['first_name'] ?? null, 'last_name' => $data['last_name'] ?? null, 'phone' => $data['phone'] ?? null]);

        if ($created) {
            $contact = Contact::create($fields + ['email' => $email, 'source' => 'api']);
        } else {
            $contact->update($fields);
        }

        // custom fields: define any that are new (as plain text), then save the answers
        $custom = [];
        foreach ($data['custom_fields'] ?? [] as $label => $value) {
            $key = FormFields::keyFor((string) $label, []);
            // `key` is guarded against mass assignment, so a new field must be built with forceFill
            $field = ContactField::where('key', $key)->first()
                ?? tap((new ContactField)->forceFill(['key' => $key, 'label' => trim((string) $label), 'type' => 'text']))->save();
            $custom[$field->key] = (string) $value;
        }
        $contact->setCustom($custom);

        $newLists = $newTags = [];
        foreach ($data['lists'] ?? [] as $name) {
            $list = ContactList::firstOrCreate(['name' => $name]);
            if (! $contact->lists()->where('contact_lists.id', $list->id)->exists()) {
                $newLists[] = $list->id;
            }
            $contact->lists()->syncWithoutDetaching([$list->id]);
        }
        foreach ($data['tags'] ?? [] as $name) {
            $tag = Tag::firstOrCreate(['name' => $name]);
            if (! $contact->tags()->where('tags.id', $tag->id)->exists()) {
                $newTags[] = $tag->id;
            }
            $contact->tags()->syncWithoutDetaching([$tag->id]);
        }
        Automation::contactChanged($contact, $created, $newLists, $newTags);

        $contact->load('lists:id,name', 'tags:id,name')->setAttribute('custom', (object) $contact->customMap());
        $contact->unsetRelation('fieldValues');

        return response($contact, $created ? 201 : 200);
    }

    public function show(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        return Contact::with('lists:id,name', 'tags:id,name')->where('email', Str::lower($request->query('email')))->firstOrFail();
    }

    public function unsubscribe(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'channel' => 'nullable|in:email,sms']);
        $contact = Contact::where('email', Str::lower($data['email']))->firstOrFail();
        Subscriptions::set($contact, false); // 'channel' is still accepted so existing integrations keep working, but it unsubscribes the contact from the newsletter as a whole

        return ['ok' => true];
    }
}
