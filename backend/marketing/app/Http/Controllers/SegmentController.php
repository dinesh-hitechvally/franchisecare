<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use App\Services\SegmentQuery;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    public function index()
    {
        return Segment::latest()->get()->each(fn ($s) => $s->setAttribute('people_count', SegmentQuery::forSegment($s)->count()));
    }

    /** Field catalogue for the rule builder. */
    public function fields()
    {
        return collect(SegmentQuery::FIELDS)->map(fn ($f, $key) => ['key' => $key, 'label' => $f[0], 'type' => $f[1], 'ops' => $f[2]])->values();
    }

    public function store(Request $request)
    {
        return Segment::create($this->validated($request));
    }

    public function update(Request $request, Segment $segment)
    {
        $segment->update($this->validated($request));

        return $segment;
    }

    public function destroy(Segment $segment)
    {
        $segment->delete();

        return response()->noContent();
    }

    /** Live count + a few sample people for rules that are still being edited. */
    public function preview(Request $request)
    {
        $data = $this->validated($request, false);
        $q = SegmentQuery::query($data['rules'], $data['match'], $request->user()->company_id);

        return [
            'count' => (clone $q)->count(),
            'sample' => $q->orderBy('people.id')->limit(5)->get(['id', 'first_name', 'last_name', 'email', 'phone']),
        ];
    }

    protected function validated(Request $request, bool $named = true): array
    {
        return $request->validate([
            'name' => ($named ? 'required' : 'nullable').'|string|max:255',
            'description' => 'nullable|string',
            'match' => 'required|in:all,any',
            'rules' => 'present|array|max:20',
            'rules.*.field' => 'required|in:'.implode(',', array_keys(SegmentQuery::FIELDS)),
            'rules.*.op' => 'nullable|string|max:20',
            'rules.*.value' => 'nullable',
        ]);
    }
}
