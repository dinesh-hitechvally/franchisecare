<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;

class IncomeCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = IncomeCategory::withCount('incomes');

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

        $category = IncomeCategory::create($validated);

        return response()->json($category->loadCount('incomes'), 201);
    }

    public function show(IncomeCategory $incomeCategory)
    {
        return response()->json($incomeCategory->loadCount('incomes'));
    }

    public function update(Request $request, IncomeCategory $incomeCategory)
    {
        abort_if($incomeCategory->is_system, 403, 'System categories cannot be modified.');

        $validated = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'description'   => 'nullable|string',
            'gst_inclusive' => 'boolean',
            'is_active'     => 'boolean',
        ]);

        $incomeCategory->update($validated);

        return response()->json($incomeCategory->loadCount('incomes'));
    }

    public function destroy(IncomeCategory $incomeCategory)
    {
        abort_if($incomeCategory->is_system, 403, 'System categories cannot be deleted.');

        $incomeCategory->delete();

        return response()->json(null, 204);
    }
}
