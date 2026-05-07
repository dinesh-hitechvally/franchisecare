<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blockout;
use Illuminate\Http\Request;

class BlockoutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Blockout::query();

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('is_recurring')) {
            $query->where('is_recurring', $request->boolean('is_recurring'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($request->input('per_page', 25));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_date' => 'required|date',
            'end_time' => 'required|string',
            'is_recurring' => 'boolean',
            'repeat_every' => 'nullable|string',
            'repeat_on' => 'nullable|string',
            'repeat_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'active' => 'boolean',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        // Use authenticated user's company_id if not provided
        if (empty($validated['company_id']) && auth()->check()) {
            $validated['company_id'] = auth()->user()->company_id;
        }

        if (empty($validated['company_id'])) {
            return response()->json(['message' => 'Company information is required'], 422);
        }

        $blockout = Blockout::create($validated);

        return response()->json($blockout, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Blockout $blockout)
    {
        return response()->json($blockout);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blockout $blockout)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|string',
            'end_date' => 'sometimes|date',
            'end_time' => 'sometimes|string',
            'is_recurring' => 'sometimes|boolean',
            'repeat_every' => 'nullable|string',
            'repeat_on' => 'nullable|string',
            'repeat_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $blockout->update($validated);

        return response()->json($blockout);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blockout $blockout)
    {
        $blockout->delete();

        return response()->json(null, 204);
    }
}
