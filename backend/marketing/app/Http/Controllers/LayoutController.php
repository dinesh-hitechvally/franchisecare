<?php

namespace App\Http\Controllers;

use App\Models\Layout;
use Illuminate\Http\Request;

class LayoutController extends Controller
{
    public function index()
    {
        return Layout::latest()->get();
    }

    public function store(Request $request)
    {
        return Layout::create($request->validate([
            'name' => 'required|string|max:255',
            'design' => 'required|array',
            'design.blocks' => 'present|array',
        ]));
    }

    public function destroy(Layout $layout)
    {
        $layout->delete();

        return response()->noContent();
    }
}
