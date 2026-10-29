<?php

namespace App\Http\Controllers;

use App\Models\ContactList;
use Illuminate\Http\Request;

class ContactListController extends Controller
{
    public function index()
    {
        return ContactList::withCount('contacts')->latest()->get();
    }

    public function store(Request $request)
    {
        return ContactList::create($request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']));
    }

    public function update(Request $request, ContactList $list)
    {
        $list->update($request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']));

        return $list;
    }

    public function destroy(ContactList $list)
    {
        $list->delete();

        return response()->noContent();
    }
}
