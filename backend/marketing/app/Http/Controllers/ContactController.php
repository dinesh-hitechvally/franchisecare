<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactField;
use App\Models\Segment;
use App\Services\Automation;
use App\Services\SegmentQuery;
use App\Services\Subscriptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $q = $this->filtered($request)->latest('contacts.id');

        $page = $q->paginate(min((int) $request->query('per_page', 25), 100));
        $page->getCollection()->each(fn ($c) => $this->present($c));

        return $page;
    }

    /** The contacts matching the list's filters (search, list, tag, segment, subscription). */
    protected function filtered(Request $request)
    {
        $q = $request->query('segment_id') && ($seg = Segment::find($request->query('segment_id')))
            ? SegmentQuery::forSegment($seg)
            : Contact::query();

        $q->with(['lists:id,name', 'tags:id,name,color', 'fieldValues.field:id,key']);

        if ($s = $request->query('search')) {
            $q->where(fn ($w) => $w->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"));
        }
        if ($list = $request->query('list_id')) {
            $q->whereHas('lists', fn ($w) => $w->where('contact_lists.id', $list));
        }
        match ($request->query('status')) {
            'subscribed' => $q->where('contacts.is_subscribed', true),
            'unsubscribed' => $q->where('contacts.is_subscribed', false),
            default => null,
        };
        if ($tag = $request->query('tag_id')) {
            $q->whereHas('tags', fn ($w) => $w->where('tags.id', $tag));
        }

        return $q;
    }

    /** Everyone matching the same filters as the list, as a CSV file (also usable as a backup or to move to another tool). */
    public function export(Request $request)
    {
        $fields = ContactField::orderBy('label')->get(['id', 'key', 'label']);

        return response()->streamDownload(function () use ($request, $fields) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['First name', 'Last name', 'Email', 'Phone', 'Subscribed', 'Subscribed on', 'Source', 'Lists', 'Tags', 'Added on'], $fields->pluck('label')->map(fn ($l) => self::safe($l))->all()));

            $this->filtered($request)->chunkById(500, function ($rows) use ($out, $fields) {
                foreach ($rows as $c) {
                    $custom = $c->customMap();
                    fputcsv($out, array_merge([
                        self::safe((string) $c->first_name), self::safe((string) $c->last_name), self::safe((string) $c->email), self::safe((string) $c->phone),
                        $c->is_subscribed ? 'yes' : 'no', $c->subscribed_at, $c->source,
                        self::safe($c->lists->pluck('name')->implode('; ')), self::safe($c->tags->pluck('name')->implode('; ')), $c->created_at,
                    ], $fields->map(fn ($f) => self::safe(is_scalar($custom[$f->key] ?? null) ? (string) $custom[$f->key] : ''))->all()));
                }
            });
            fclose($out);
        }, 'contacts-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** A spreadsheet runs anything starting with = + - @ as a formula: write what people typed as plain text instead. */
    protected static function safe(string $v): string
    {
        return $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'".$v : $v;
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $custom = $this->customAnswers($request);
        $contact = Contact::create($data + ['source' => 'manual']);
        $contact->setCustom($custom);
        [$lists, $tags] = $this->syncRelations($request, $contact);
        Automation::contactChanged($contact, true, $lists, $tags);

        return $this->present($contact->load('lists:id,name', 'tags:id,name,color'));
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $this->validated($request, $contact);
        $custom = $this->customAnswers($request);
        $subscribed = $data['is_subscribed'] ?? $contact->is_subscribed;
        unset($data['is_subscribed']);

        $contact->update($data);
        if ($request->has('custom')) {
            $contact->setCustom($custom);
        }
        Subscriptions::set($contact, (bool) $subscribed);
        [$lists, $tags] = $this->syncRelations($request, $contact);
        Automation::contactChanged($contact, false, $lists, $tags);

        return $this->present($contact->load('lists:id,name', 'tags:id,name,color'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->noContent();
    }

    /** Bulk actions on selected contacts: add/remove a tag, add to a list, or delete. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:1000',
            'ids.*' => 'integer',
            'action' => 'required|in:add_tag,remove_tag,add_list,delete',
            'tag_id' => 'required_if:action,add_tag,remove_tag|nullable|integer',
            'list_id' => 'required_if:action,add_list|nullable|integer',
        ]);

        $contacts = Contact::whereIn('id', $data['ids'])->get(); // tenant-scoped
        foreach ($contacts as $c) {
            match ($data['action']) {
                'add_tag' => $this->attachTag($c, (int) $data['tag_id']),
                'remove_tag' => $c->tags()->detach($data['tag_id']) ? Automation::fire('tag_removed', $c, ['id' => (int) $data['tag_id']]) : null,
                'add_list' => $this->attachList($c, (int) $data['list_id']),
                'delete' => $c->delete(),
            };
        }

        return ['affected' => $contacts->count()];
    }

    /** CSV import: header row with first_name,last_name,email,phone. Optional list_id / tag_id. */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt', 'list_id' => 'nullable|integer', 'tag_id' => 'nullable|integer']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF")), fgetcsv($handle) ?: []);
        $created = $updated = $skipped = 0;
        $listId = $request->input('list_id');
        $tagId = $request->input('tag_id');

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                $skipped++;
                continue;
            }
            $r = array_combine($header, array_map('trim', $row));
            $email = filter_var($r['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null;
            $phone = $r['phone'] ?? null ?: null;
            if (! $email && ! $phone) {
                $skipped++;
                continue;
            }

            $fields = ['first_name' => $r['first_name'] ?? null, 'last_name' => $r['last_name'] ?? null, 'phone' => $phone];
            $contact = $email ? Contact::where('email', $email)->first() : Contact::where('phone', $phone)->first();
            $isNew = ! $contact;
            if ($contact) {
                $contact->update(array_filter($fields));
                $updated++;
            } else {
                $contact = Contact::create($fields + ['email' => $email, 'source' => 'import']);
                $created++;
            }
            $newLists = $listId && ! $contact->lists()->where('contact_lists.id', $listId)->exists() ? [(int) $listId] : [];
            $newTags = $tagId && ! $contact->tags()->where('tags.id', $tagId)->exists() ? [(int) $tagId] : [];
            if ($listId) {
                $contact->lists()->syncWithoutDetaching([$listId]);
            }
            if ($tagId) {
                $contact->tags()->syncWithoutDetaching([$tagId]);
            }
            Automation::contactChanged($contact, $isNew, $newLists, $newTags);
        }
        fclose($handle);

        return compact('created', 'updated', 'skipped');
    }

    protected function attachTag(Contact $c, int $tagId): void
    {
        $new = ! $c->tags()->where('tags.id', $tagId)->exists();
        $c->tags()->syncWithoutDetaching([$tagId]);
        $new && Automation::contactChanged($c, false, [], [$tagId]);
    }

    protected function attachList(Contact $c, int $listId): void
    {
        $new = ! $c->lists()->where('contact_lists.id', $listId)->exists();
        $c->lists()->syncWithoutDetaching([$listId]);
        $new && Automation::contactChanged($c, false, [$listId], []);
    }

    /** Syncs list_ids / tag_ids when sent, and returns the ids that were newly attached (for triggers). */
    protected function syncRelations(Request $request, Contact $contact): array
    {
        $new = [];
        foreach (['list' => 'lists', 'tag' => 'tags'] as $key => $relation) {
            $new[$key] = [];
            if ($request->has("{$key}_ids")) {
                $ids = array_map('intval', $request->input("{$key}_ids", []));
                $before = $contact->{$relation}()->pluck($relation === 'lists' ? 'contact_lists.id' : 'tags.id')->all();
                $contact->{$relation}()->sync($ids);
                $new[$key] = array_values(array_diff($ids, $before));
                foreach (array_diff($before, $ids) as $gone) { // taken off: the "tag is removed" / "leaves a list" triggers
                    Automation::fire($key === 'list' ? 'list_left' : 'tag_removed', $contact, ['id' => $gone]);
                }
            }
        }

        return [$new['list'], $new['tag']];
    }

    protected function validated(Request $request, ?Contact $contact = null): array
    {
        $biz = $request->user()->company_id;

        $data = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => ['nullable', 'email', 'required_without:phone',
                Rule::unique('contacts')->where('company_id', $biz)->whereNull('deleted_at')->ignore($contact?->id)],
            'phone' => 'nullable|string|max:30|required_without:email',
            'is_subscribed' => 'boolean',
            'custom' => 'nullable|array',
            'custom.*' => 'nullable|string|max:2000',
        ]);
        unset($data['custom']); // handled separately by customAnswers()

        return $data;
    }

    /** Custom field answers from the request as [key => value], checked against the company's field definitions. */
    protected function customAnswers(Request $request): array
    {
        $answers = (array) $request->input('custom', []);
        $defs = ContactField::whereIn('key', array_keys($answers))->get()->keyBy('key');
        foreach ($answers as $key => $value) {
            abort_unless(isset($defs[$key]), 422, "Unknown custom field \"{$key}\".");
            $problem = trim((string) $value) === '' ? null : $defs[$key]->problemWith(trim((string) $value));
            abort_if($problem, 422, $problem);
        }

        return $answers;
    }

    /** Adds the custom answers to a contact for the API response, as a {key: value} object. */
    protected function present(Contact $contact): Contact
    {
        $contact->setAttribute('custom', (object) $contact->customMap());
        $contact->unsetRelation('fieldValues');

        return $contact;
    }
}
