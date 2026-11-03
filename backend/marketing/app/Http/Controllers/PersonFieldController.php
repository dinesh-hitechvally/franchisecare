<?php

namespace App\Http\Controllers;

use App\Models\PersonField;
use App\Services\FormFields;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Manage the custom data fields a company keeps on its people (beyond name, email and phone). */
class PersonFieldController extends Controller
{
    public function index()
    {
        return PersonField::withCount(['values' => fn ($q) => $q->whereHas('person')])->orderBy('label')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'type' => ['required', Rule::in(PersonField::TYPES)],
            'options' => 'nullable|array|max:30',
            'options.*' => 'nullable|string|max:100',
        ]);
        $data['options'] = $this->options($data);

        // the key is the merge-tag name, so it is generated from the label and then never changes
        $key = FormFields::keyFor($data['label'], PersonField::pluck('key')->all());
        $field = (new PersonField)->forceFill($data + ['key' => $key]);
        $field->save();

        return response($field->loadCount(['values' => fn ($q) => $q->whereHas('person')]), 201);
    }

    /** Only the label and the choices can change: the key (merge tag) and type stay, so existing answers keep their meaning. */
    public function update(Request $request, PersonField $field)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'options' => 'nullable|array|max:30',
            'options.*' => 'nullable|string|max:100',
        ]);
        $data['options'] = $this->options($data + ['type' => $field->type]);
        $field->update($data);

        return $field->loadCount(['values' => fn ($q) => $q->whereHas('person')]);
    }

    public function destroy(PersonField $field)
    {
        $field->delete(); // the answers stay stored (hidden) so the field can come back with its data

        return response()->noContent();
    }

    /** dropdown / radio need at least one choice; every other type has none. */
    protected function options(array $data): ?array
    {
        if (! in_array($data['type'], ['dropdown', 'radio'], true)) {
            return null;
        }
        $options = array_values(array_unique(array_filter(array_map(fn ($o) => trim((string) $o), $data['options'] ?? []), 'strlen')));
        abort_if(! $options, 422, 'Add at least one option.');

        return $options;
    }
}
