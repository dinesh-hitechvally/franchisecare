<?php

namespace App\Repositories;

use App\Contracts\Repositories\BlockoutRecurringRepositoryInterface;
use App\Models\Blockout;
use App\Models\BlockoutRecurring;
use App\Models\BlockoutRecurringAudit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BlockoutRecurringRepository implements BlockoutRecurringRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = BlockoutRecurring::query();

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): BlockoutRecurring
    {
        return DB::transaction(function () use ($data) {
            $recurring = BlockoutRecurring::create($data);
            $this->regenerateBlockouts($recurring);
            return $recurring->fresh()->load('blockouts');
        });
    }

    public function update(BlockoutRecurring $recurring, array $data): BlockoutRecurring
    {
        return DB::transaction(function () use ($recurring, $data) {
            $recurring->update($data);
            $this->regenerateBlockouts($recurring->fresh());
            return $recurring->fresh()->load('blockouts');
        });
    }

    public function delete(BlockoutRecurring $recurring): void
    {
        DB::transaction(function () use ($recurring) {
            Blockout::where('recurring_id', $recurring->id)->delete();
            $recurring->delete();
        });
    }

    public function getHistory(BlockoutRecurring $recurring, int $perPage): LengthAwarePaginator
    {
        $history = BlockoutRecurringAudit::where('blockout_recurring_id', $recurring->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $userIds = $history->getCollection()->pluck('performed_by')->filter()->unique()->values();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'first_name', 'last_name'])->keyBy('id');

        $history->setCollection($history->getCollection()->map(function ($audit) use ($users) {
            $user = $users->get($audit->performed_by);
            if (!$user) {
                return $audit;
            }

            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $audit->performed_by_name = $fullName !== '' ? $fullName : ($user->name ?? null);

            return $audit;
        }));

        return $history;
    }

    public function regenerateBlockouts(BlockoutRecurring $recurring): void
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
