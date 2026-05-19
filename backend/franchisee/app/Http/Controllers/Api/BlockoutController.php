<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blockout;
use App\Models\BlockoutAudit;
use App\Models\BlockoutRecurring;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            if ($request->boolean('is_recurring')) {
                $query->whereNotNull('recurring_id');
            } else {
                $query->whereNull('recurring_id');
            }
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
            'recurring_id' => 'nullable|exists:blockout_recurrings,id',
            'repeat_every' => 'nullable|string',
            'repeat_on' => 'nullable|string',
            'repeat_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'active' => 'boolean',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $isRecurring = (bool) ($validated['is_recurring'] ?? false);
        unset($validated['is_recurring']);

        // Use authenticated user's company_id if not provided
        if (empty($validated['company_id']) && Auth::check()) {
            $validated['company_id'] = Auth::user()?->company_id;
        }

        if (empty($validated['company_id'])) {
            return response()->json(['message' => 'Company information is required'], 422);
        }

        $blockout = DB::transaction(function () use ($validated, $isRecurring) {
            $blockout = Blockout::create($validated);

            $this->syncRecurringData($blockout, $isRecurring);

            return $blockout->fresh();
        });

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
        // Normalise camelCase keys sent by the frontend to snake_case
        $input = $request->all();
        $keyMap = [
            'startDate' => 'start_date',
            'startTime' => 'start_time',
            'endDate'   => 'end_date',
            'endTime'   => 'end_time',
            'isRecurring'  => 'is_recurring',
            'recurringId' => 'recurring_id',
            'repeatEvery'  => 'repeat_every',
            'repeatOn'     => 'repeat_on',
            'repeatUntil'  => 'repeat_until',
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
            'is_recurring' => 'sometimes|boolean',
            'recurring_id' => 'nullable|exists:blockout_recurrings,id',
            'repeat_every' => 'nullable|string',
            'repeat_on' => 'nullable|string',
            'repeat_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $isRecurring = array_key_exists('is_recurring', $validated)
            ? (bool) $validated['is_recurring']
            : ($blockout->recurring_id !== null);
        unset($validated['is_recurring']);

        DB::transaction(function () use ($blockout, $validated, $isRecurring) {
            $blockout->update($validated);

            $this->syncRecurringData($blockout->fresh(), $isRecurring);
        });

        return response()->json($blockout);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blockout $blockout)
    {
        if ($blockout->recurring_id && ($blockout->repeat_every || $blockout->repeat_on || $blockout->repeat_until)) {
            Blockout::where('recurring_id', $blockout->recurring_id)->delete();
            BlockoutRecurring::where('id', $blockout->recurring_id)->delete();
            return response()->json(null, 204);
        }

        $blockout->delete();

        return response()->json(null, 204);
    }

    public function getHistory(Blockout $blockout)
    {
        $history = BlockoutAudit::where('blockout_id', $blockout->id)
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

    private function syncRecurringData(Blockout $blockout, bool $isRecurring): void
    {
        if (! $isRecurring) {
            if ($blockout->recurring_id) {
                Blockout::where('recurring_id', $blockout->recurring_id)
                    ->where('id', '!=', $blockout->id)
                    ->delete();
                BlockoutRecurring::where('id', $blockout->recurring_id)->delete();

                $blockout->update([
                    'recurring_id' => null,
                    'repeat_every' => null,
                    'repeat_on' => null,
                    'repeat_until' => null,
                ]);
            }
            return;
        }

        $recurring = $blockout->recurring_id
            ? BlockoutRecurring::find($blockout->recurring_id)
            : null;

        if ($recurring) {
            $recurring->update([
                'company_id' => $blockout->company_id,
                'title' => $blockout->title,
                'location' => $blockout->location,
                'start_date' => $blockout->start_date,
                'start_time' => $blockout->start_time,
                'end_date' => $blockout->end_date,
                'end_time' => $blockout->end_time,
                'repeat_every' => $blockout->repeat_every,
                'repeat_on' => $blockout->repeat_on,
                'repeat_until' => $blockout->repeat_until,
                'notes' => $blockout->notes,
                'active' => (bool) $blockout->active,
            ]);
        } else {
            $recurring = BlockoutRecurring::create([
                'company_id' => $blockout->company_id,
                'title' => $blockout->title,
                'location' => $blockout->location,
                'start_date' => $blockout->start_date,
                'start_time' => $blockout->start_time,
                'end_date' => $blockout->end_date,
                'end_time' => $blockout->end_time,
                'repeat_every' => $blockout->repeat_every,
                'repeat_on' => $blockout->repeat_on,
                'repeat_until' => $blockout->repeat_until,
                'notes' => $blockout->notes,
                'active' => (bool) $blockout->active,
            ]);
        }

        if ((int) $blockout->recurring_id !== (int) $recurring->id) {
            $blockout->update(['recurring_id' => $recurring->id]);
            $blockout = $blockout->fresh();
        }

        $this->regenerateRecurringInstances($blockout, $recurring);
    }

    private function regenerateRecurringInstances(Blockout $blockout, BlockoutRecurring $recurring): void
    {
        Blockout::where('recurring_id', $recurring->id)
            ->where('id', '!=', $blockout->id)
            ->delete();

        if (empty($blockout->repeat_until)) {
            return;
        }

        $baseStartDate = Carbon::parse($blockout->start_date);
        $baseEndDate = Carbon::parse($blockout->end_date);
        $durationDays = max(0, $baseStartDate->diffInDays($baseEndDate, false));

        $repeatEvery = (int) ($blockout->repeat_every ?: 1);
        if ($repeatEvery < 1) {
            $repeatEvery = 1;
        }

        $repeatUntil = Carbon::parse($blockout->repeat_until);

        $dayMap = [
            'Sunday' => 0,
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
        ];

        $targetDay = $dayMap[$blockout->repeat_on] ?? $baseStartDate->dayOfWeek;
        $cursor = $baseStartDate->copy();
        if ($cursor->dayOfWeek !== $targetDay) {
            $daysToAdd = ($targetDay - $cursor->dayOfWeek + 7) % 7;
            $cursor->addDays($daysToAdd === 0 ? 7 : $daysToAdd);
        }

        while ($cursor->lte($repeatUntil)) {
            if ($cursor->toDateString() !== $baseStartDate->toDateString()) {
                $generatedEndDate = $cursor->copy()->addDays($durationDays);

                Blockout::create([
                    'title' => $blockout->title,
                    'location' => $blockout->location,
                    'start_date' => $cursor->toDateString(),
                    'start_time' => $blockout->start_time,
                    'end_date' => $generatedEndDate->toDateString(),
                    'end_time' => $blockout->end_time,
                    'recurring_id' => $recurring->id,
                    'repeat_every' => null,
                    'repeat_on' => null,
                    'repeat_until' => null,
                    'notes' => $blockout->notes,
                    'active' => (bool) $blockout->active,
                    'company_id' => $blockout->company_id,
                ]);
            }

            $cursor->addWeeks($repeatEvery);
        }
    }
}
