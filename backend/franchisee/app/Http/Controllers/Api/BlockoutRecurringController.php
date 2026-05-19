<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blockout;
use App\Models\BlockoutRecurring;
use App\Models\BlockoutRecurringAudit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BlockoutRecurringController extends Controller
{
    public function index(Request $request)
    {
        $query = BlockoutRecurring::query();

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($request->input('per_page', 25));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_date' => 'required|date',
            'end_time' => 'required|string',
            'repeat_every' => 'required|string',
            'repeat_on' => 'required|string',
            'repeat_until' => 'required|date',
            'notes' => 'nullable|string',
            'active' => 'boolean',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        if (empty($validated['company_id']) && Auth::check()) {
            $validated['company_id'] = Auth::user()?->company_id;
        }

        if (empty($validated['company_id'])) {
            return response()->json(['message' => 'Company information is required'], 422);
        }

        $recurring = DB::transaction(function () use ($validated) {
            $recurring = BlockoutRecurring::create($validated);
            $this->regenerateBlockouts($recurring);
            return $recurring->fresh();
        });

        return response()->json($recurring, 201);
    }

    public function show(BlockoutRecurring $blockoutRecurring)
    {
        return response()->json($blockoutRecurring);
    }

    public function update(Request $request, BlockoutRecurring $blockoutRecurring)
    {
        $input = $request->all();
        $keyMap = [
            'startDate' => 'start_date',
            'startTime' => 'start_time',
            'endDate' => 'end_date',
            'endTime' => 'end_time',
            'repeatEvery' => 'repeat_every',
            'repeatOn' => 'repeat_on',
            'repeatUntil' => 'repeat_until',
        ];
        foreach ($keyMap as $camel => $snake) {
            if (array_key_exists($camel, $input) && !array_key_exists($snake, $input)) {
                $input[$snake] = $input[$camel];
                unset($input[$camel]);
            }
        }
        $request->replace($input);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|string',
            'end_date' => 'sometimes|date',
            'end_time' => 'sometimes|string',
            'repeat_every' => 'sometimes|string',
            'repeat_on' => 'sometimes|string',
            'repeat_until' => 'sometimes|date',
            'notes' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($blockoutRecurring, $validated) {
            $blockoutRecurring->update($validated);
            $this->regenerateBlockouts($blockoutRecurring->fresh());
        });

        return response()->json($blockoutRecurring->fresh());
    }

    public function destroy(BlockoutRecurring $blockoutRecurring)
    {
        DB::transaction(function () use ($blockoutRecurring) {
            Blockout::where('recurring_id', $blockoutRecurring->id)->delete();
            $blockoutRecurring->delete();
        });

        return response()->json(null, 204);
    }

    public function getHistory(BlockoutRecurring $blockoutRecurring)
    {
        $history = BlockoutRecurringAudit::where('blockout_recurring_id', $blockoutRecurring->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate(10);

        $userIds = $history->getCollection()->pluck('performed_by')->filter()->unique()->values();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'first_name', 'last_name'])->keyBy('id');

        $history->setCollection($history->getCollection()->map(function ($audit) use ($users) {
            $user = $users->get($audit->performed_by);
            if (! $user) {
                return $audit;
            }

            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $audit->performed_by_name = $fullName !== '' ? $fullName : ($user->name ?? null);

            return $audit;
        }));

        return response()->json($history);
    }

    private function regenerateBlockouts(BlockoutRecurring $recurring): void
    {
        Blockout::where('recurring_id', $recurring->id)->delete();

        $baseStartDate = Carbon::parse($recurring->start_date);
        $baseEndDate = Carbon::parse($recurring->end_date);
        $durationDays = max(0, $baseStartDate->diffInDays($baseEndDate, false));

        $repeatEvery = max(1, (int) ($recurring->repeat_every ?: 1));
        $repeatUntil = Carbon::parse($recurring->repeat_until);

        $dayMap = [
            'Sunday' => 0,
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
        ];

        $targetDay = $dayMap[$recurring->repeat_on] ?? $baseStartDate->dayOfWeek;
        $cursor = $baseStartDate->copy();

        if ($cursor->dayOfWeek !== $targetDay) {
            $daysToAdd = ($targetDay - $cursor->dayOfWeek + 7) % 7;
            $cursor->addDays($daysToAdd === 0 ? 7 : $daysToAdd);
        }

        while ($cursor->lte($repeatUntil)) {
            $generatedEndDate = $cursor->copy()->addDays($durationDays);

            Blockout::create([
                'title' => $recurring->title,
                'location' => $recurring->location,
                'start_date' => $cursor->toDateString(),
                'start_time' => $recurring->start_time,
                'end_date' => $generatedEndDate->toDateString(),
                'end_time' => $recurring->end_time,
                'recurring_id' => $recurring->id,
                'repeat_every' => $recurring->repeat_every,
                'repeat_on' => $recurring->repeat_on,
                'repeat_until' => $recurring->repeat_until,
                'notes' => $recurring->notes,
                'active' => (bool) $recurring->active,
                'company_id' => $recurring->company_id,
            ]);

            $cursor->addWeeks($repeatEvery);
        }
    }
}
