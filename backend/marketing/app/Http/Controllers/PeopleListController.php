<?php

namespace App\Http\Controllers;

use App\Models\PeopleList;
use Illuminate\Http\Request;

class PeopleListController extends Controller
{
    public function index()
    {
        return PeopleList::withCount('people')->latest()->get();
    }

    public function store(Request $request)
    {
        return PeopleList::create($request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']));
    }

    public function update(Request $request, PeopleList $list)
    {
        $list->update($request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']));

        return $list;
    }

    public function destroy(PeopleList $list)
    {
        $list->delete();

        return response()->noContent();
    }
}
