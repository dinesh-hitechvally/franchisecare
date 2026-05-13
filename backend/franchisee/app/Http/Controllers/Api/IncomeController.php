<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Income;
use App\Models\RecurringIncome;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Income::with('category')->latest('income_date');

        if (auth()->check() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('income_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('income_date', '<=', $request->date_to);
        }

        if ($request->filled('category_id')) {
            $query->where('income_category_id', $request->category_id);
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage, ['*'], 'page', max(1, (int) $request->input('page', 1)));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'income_category_id' => 'nullable|exists:income_categories,id',
            'booking_id'         => 'nullable|exists:bookings,id',
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'amount'             => 'required|numeric|min:0',
            'income_date'        => 'required|date',
            'is_active'          => 'boolean',
            'is_recurring'       => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'auto_extend_recurring' => 'boolean',
        ]);

        $company_id = auth()->user()->company_id;

        // Handle recurring income first
        if ($request->boolean('is_recurring')) {
            $recurringIncome = RecurringIncome::create([
                'company_id'         => $company_id,
                'income_category_id' => $validated['income_category_id'] ?? null,
                'start_date'         => $validated['income_date'],
                'frequency'          => $request->input('recurring_frequency', 'weekly'),
                'status'             => 'active',
                'auto_extend'        => $request->boolean('auto_extend_recurring', false),
                'total'              => $validated['amount'],
            ]);

            $validated['recurring_income_id'] = $recurringIncome->id;
        }

        // Prepare income data - only include fillable fields
        $incomeData = [
            'company_id'         => $company_id,
            'income_category_id' => $validated['income_category_id'] ?? null,
            'booking_id'         => $validated['booking_id'] ?? null,
            'title'              => $validated['title'],
            'description'        => $validated['description'] ?? null,
            'amount'             => $validated['amount'],
            'income_date'        => $validated['income_date'],
            'is_active'          => $validated['is_active'] ?? true,
            'recurring_income_id' => $validated['recurring_income_id'] ?? null,
        ];

        $income = Income::create($incomeData);

        return response()->json($income->load('category'), 201);
    }

    public function show(Income $income)
    {
        return response()->json($income->load('category'));
    }

    public function update(Request $request, Income $income)
    {
        abort_if($income->booking_id, 403, 'Income linked to a booking must be edited via the booking.');

        $validated = $request->validate([
            'income_category_id' => 'nullable|exists:income_categories,id',
            'title'              => 'sometimes|required|string|max:255',
            'description'        => 'nullable|string',
            'amount'             => 'sometimes|required|numeric|min:0',
            'income_date'        => 'sometimes|required|date',
            'is_active'          => 'boolean',
            'is_recurring'       => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'auto_extend_recurring' => 'boolean',
        ]);

        $company_id = auth()->user()->company_id;

        // Handle recurring income update
        if ($request->boolean('is_recurring')) {
            if (!$income->recurring_income_id) {
                // Create new recurring record if not already created
                $recurringIncome = RecurringIncome::create([
                    'company_id'         => $company_id,
                    'income_category_id' => $validated['income_category_id'] ?? $income->income_category_id,
                    'start_date'         => $validated['income_date'] ?? $income->income_date,
                    'frequency'          => $request->input('recurring_frequency', 'weekly'),
                    'status'             => 'active',
                    'auto_extend'        => $request->boolean('auto_extend_recurring', false),
                    'total'              => $validated['amount'] ?? $income->amount,
                ]);

                $validated['recurring_income_id'] = $recurringIncome->id;
            } else {
                // Update existing recurring record
                $income->recurringIncome->update([
                    'frequency'   => $request->input('recurring_frequency', 'weekly'),
                    'auto_extend' => $request->boolean('auto_extend_recurring', false),
                    'total'       => $validated['amount'] ?? $income->amount,
                ]);
            }
        }

        // Prepare update data - only include fillable fields
        $updateData = [];
        if (isset($validated['income_category_id'])) {
            $updateData['income_category_id'] = $validated['income_category_id'];
        }
        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['amount'])) {
            $updateData['amount'] = $validated['amount'];
        }
        if (isset($validated['income_date'])) {
            $updateData['income_date'] = $validated['income_date'];
        }
        if (isset($validated['is_active'])) {
            $updateData['is_active'] = $validated['is_active'];
        }
        if (isset($validated['recurring_income_id'])) {
            $updateData['recurring_income_id'] = $validated['recurring_income_id'];
        }

        $income->update($updateData);

        return response()->json($income->load('category'));
    }

    public function destroy(Income $income)
    {
        $income->delete();

        return response()->json(null, 204);
    }
}
