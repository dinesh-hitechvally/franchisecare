<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $q = Contact::with('lists:id,name')->latest();

        if ($s = $request->query('search')) {
            $q->where(fn ($w) => $w->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"));
        }
        if ($list = $request->query('list_id')) {
            $q->whereHas('lists', fn ($w) => $w->where('contact_lists.id', $list));
        }

        return $q->paginate(25);
    }

    public function store(Request $request)
    {
        $contact = Contact::create($this->validated($request));
        $contact->lists()->sync($request->input('list_ids', []));

        return $contact->load('lists:id,name');
    }

    public function update(Request $request, Contact $contact)
    {
        $contact->update($this->validated($request, $contact));
        if ($request->has('list_ids')) {
            $contact->lists()->sync($request->input('list_ids', []));
        }

        return $contact->load('lists:id,name');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->noContent();
    }

    /** CSV import: header row with first_name,last_name,email,phone. Optional list_id. */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt', 'list_id' => 'nullable|integer']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF")), fgetcsv($handle) ?: []);
        $created = $updated = $skipped = 0;
        $listId = $request->input('list_id');

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
            if ($contact) {
                $contact->update(array_filter($fields));
                $updated++;
            } else {
                $contact = Contact::create($fields + ['email' => $email]);
                $created++;
            }
            if ($listId) {
                $contact->lists()->syncWithoutDetaching([$listId]);
            }
        }
        fclose($handle);

        return compact('created', 'updated', 'skipped');
    }

    protected function validated(Request $request, ?Contact $contact = null): array
    {
        $biz = $request->user()->business_id;

        return $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => ['nullable', 'email', 'required_without:phone',
                Rule::unique('contacts')->where('business_id', $biz)->ignore($contact?->id)],
            'phone' => 'nullable|string|max:30|required_without:email',
            'email_subscribed' => 'boolean',
            'sms_subscribed' => 'boolean',
            'custom_fields' => 'nullable|array',
        ]);
    }
}
