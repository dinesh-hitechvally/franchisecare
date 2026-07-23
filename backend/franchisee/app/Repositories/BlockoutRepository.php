<?php

namespace App\Repositories;

use App\Contracts\Repositories\BlockoutRepositoryInterface;
use App\Models\Blockout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BlockoutRepository implements BlockoutRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        return $this->buildQuery($filters)->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->paginate($perPage);
    }

    public function findById(int $id): ?Blockout
    {
        return Blockout::find($id);
    }

    public function findByIdOrFail(int $id): Blockout
    {
        return Blockout::findOrFail($id);
    }

    public function create(array $data): Blockout
    {
        return Blockout::create($data);
    }

    public function update(Blockout $blockout, array $data): Blockout
    {
        $blockout->update($data);
        return $blockout->fresh();
    }

    public function delete(Blockout $blockout): bool
    {
        return $blockout->delete();
    }

    public function deleteByRecurringId(int $recurringId): int
    {
        return Blockout::where('recurring_id', $recurringId)->delete();
    }

    private function buildQuery(array $filters = []): Builder
    {
        $query = Blockout::query();

        // Filter by company_id
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Filter by recurring
        if (isset($filters['is_recurring'])) {
            if ($filters['is_recurring']) {
                $query->whereNotNull('recurring_id');
            } else {
                $query->whereNull('recurring_id');
            }
        }

        // Filter by active status
        if (isset($filters['active'])) {
            $query->where('active', (bool) $filters['active']);
        }

        // Filter by date range (overlap: blockout spans any part of [dateFrom, dateTo])
        if (!empty($filters['dateFrom'])) {
            $query->where('end_date', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('start_date', '<=', $filters['dateTo']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->latest();
    }
}
