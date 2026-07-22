<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportDepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(SupportDepartment::orderBy('id', 'asc')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:support_departments,name',
        ]);

        $code = Str::slug($validated['name']);

        // Prevent duplicate codes
        $originalCode = $code;
        $counter = 1;
        while (SupportDepartment::where('code', $code)->exists()) {
            $code = $originalCode . '-' . $counter;
            $counter++;
        }

        $department = SupportDepartment::create([
            'name' => $validated['name'],
            'code' => $code,
        ]);

        return response()->json($department, 201);
    }
}
