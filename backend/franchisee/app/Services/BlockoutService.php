<?php

namespace App\Services;

use App\Contracts\Repositories\BlockoutRepositoryInterface;
use App\Contracts\Services\BlockoutServiceInterface;
use App\Models\Blockout;
use App\Models\BlockoutAudit;
use App\Models\BlockoutRecurring;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BlockoutService implements BlockoutServiceInterface
{
    public function __construct(
        private BlockoutRepositoryInterface $blockoutRepository
    ) {}

    public function listBlockouts(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->blockoutRepository->getPaginated($filters, $perPage);
    }

    public function getBlockout(int $id): Blockout
    {
        return $this->blockoutRepository->findByIdOrFail($id);
    }

    public function createBlockout(array $data): Blockout
    {
        $isRecurring = (bool) ($data['is_recurring'] ?? false);
        unset($data['is_recurring']);

        // Extract repeat fields for recurring
        $repeatData = [
            'repeat_every' => $data['repeat_every'] ?? null,
            'repeat_on' => $data['repeat_on'] ?? null,
            'repeat_until' => $data['repeat_until'] ?? null,
        ];
        unset($data['repeat_every'], $data['repeat_on'], $data['repeat_until']);

        // Use authenticated user's company_id if not provided
        if (empty($data['company_id']) && Auth::check()) {
            $data['company_id'] = Auth::user()?->company_id;
        }

        if (empty($data['company_id'])) {
            throw new \InvalidArgumentException('Company information is required');
        }

        return DB::transaction(function () use ($data, $isRecurring, $repeatData) {
            $blockout = $this->blockoutRepository->create($data);
            $this->syncRecurringData($blockout, $isRecurring, $repeatData);
            return $blockout->fresh();
        });
    }

    public function updateBlockout(Blockout $blockout, array $data): Blockout
    {
        $isRecurring = array_key_exists('is_recurring', $data)
            ? (bool) $data['is_recurring']
            : ($blockout->recurring_id !== null);
        unset($data['is_recurring']);

        // Extract repeat fields for recurring
        $repeatData = [
            'repeat_every' => $data['repeat_every'] ?? null,
            'repeat_on' => $data['repeat_on'] ?? null,
            'repeat_until' => $data['repeat_until'] ?? null,
        ];
        unset($data['repeat_every'], $data['repeat_on'], $data['repeat_until']);

        return DB::transaction(function () use ($blockout, $data, $isRecurring, $repeatData) {
            $blockout = $this->blockoutRepository->update($blockout, $data);
            $this->syncRecurringData($blockout, $isRecurring, $repeatData);
            return $blockout->fresh();
        });
    }

    public function deleteBlockout(Blockout $blockout): bool
    {
        if ($blockout->recurring_id) {
            $this->blockoutRepository->deleteByRecurringId($blockout->recurring_id);
            BlockoutRecurring::where('id', $blockout->recurring_id)->delete();
            return true;
        }

        return $this->blockoutRepository->delete($blockout);
    }

    public function getBlockoutHistory(Blockout $blockout): LengthAwarePaginator
    {
        $history = BlockoutAudit::where('blockout_id', $blockout->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate(10);

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

    private function syncRecurringData(Blockout $blockout, bool $isRecurring, array $repeatData = []): void
    {
        if (!$isRecurring) {
            if ($blockout->recurring_id) {
                Blockout::where('recurring_id', $blockout->recurring_id)
                    ->where('id', '!=', $blockout->id)
                    ->delete();
                BlockoutRecurring::where('id', $blockout->recurring_id)->delete();
                $blockout->update(['recurring_id' => null]);
            }
            return;
        }

        $recurring = $blockout->recurring_id
            ? BlockoutRecurring::find($blockout->recurring_id)
            : null;

        $recurringData = [
            'company_id' => $blockout->company_id,
            'title' => $blockout->title,
            'location' => $blockout->location,
            'start_date' => $blockout->start_date,
            'start_time' => $blockout->start_time,
            'end_date' => $blockout->end_date,
            'end_time' => $blockout->end_time,
            'repeat_every' => $repeatData['repeat_every'] ?? ($recurring->repeat_every ?? null),
            'repeat_on' => $repeatData['repeat_on'] ?? ($recurring->repeat_on ?? null),
            'repeat_until' => $repeatData['repeat_until'] ?? ($recurring->repeat_until ?? null),
            'notes' => $blockout->notes,
            'active' => (bool) $blockout->active,
        ];

        if ($recurring) {
            $recurring->update($recurringData);
        } else {
            $recurring = BlockoutRecurring::create($recurringData);
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

        if (empty($recurring->repeat_until)) {
            return;
        }

        $baseStartDate = Carbon::parse($blockout->start_date);
        $baseEndDate = Carbon::parse($blockout->end_date);
        $durationDays = max(0, $baseStartDate->diffInDays($baseEndDate, false));

        $repeatEvery = (int) ($recurring->repeat_every ?: 1);
        if ($repeatEvery < 1) {
            $repeatEvery = 1;
        }

        $repeatUntil = Carbon::parse($recurring->repeat_until);

        $dayMap = [
            'Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
            'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6,
        ];

        $targetDay = $dayMap[$recurring->repeat_on] ?? $baseStartDate->dayOfWeek;
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
                    'notes' => $blockout->notes,
                    'active' => (bool) $blockout->active,
                    'company_id' => $blockout->company_id,
                ]);
            }

            $cursor->addWeeks($repeatEvery);
        }
    }
}
