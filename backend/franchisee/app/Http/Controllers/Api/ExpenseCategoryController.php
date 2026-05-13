<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseCategory::withCount('expenses');

        if (auth()->check() && auth()->user()->company_id) {
            $query->where(function ($q) {
                $q->where('company_id', auth()->user()->company_id)
                  ->orWhere('is_system', true);
            });
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'gst_inclusive' => 'boolean',
            'is_active'     => 'boolean',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['is_system']  = false;

        $category = ExpenseCategory::create($validated);

        return response()->json($category->loadCount('expenses'), 201);
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        return response()->json($expenseCategory->loadCount('expenses'));
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        abort_if($expenseCategory->is_system, 403, 'System categories cannot be modified.');

        $validated = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'description'   => 'nullable|string',
            'gst_inclusive' => 'boolean',
            'is_active'     => 'boolean',
        ]);

        $expenseCategory->update($validated);

        return response()->json($expenseCategory->loadCount('expenses'));
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        abort_if($expenseCategory->is_system, 403, 'System categories cannot be deleted.');

        $expenseCategory->delete();

        return response()->json(null, 204);
    }
}
