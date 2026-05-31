<?php

namespace App\Repositories;

use App\Contracts\Repositories\WaitlistRepositoryInterface;
use App\Models\Waitlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WaitlistRepository implements WaitlistRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        return $this->buildQuery($filters)->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->paginate($perPage);
    }

    public function findById(int $id, array $relations = []): ?Waitlist
    {
        $query = Waitlist::query();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->find($id);
    }

    public function findByIdOrFail(int $id, array $relations = []): Waitlist
    {
        $query = Waitlist::query();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->findOrFail($id);
    }

    public function create(array $data): Waitlist
    {
        return Waitlist::create($data);
    }

    public function update(Waitlist $waitlist, array $data): Waitlist
    {
        $waitlist->update($data);
        return $waitlist->fresh();
    }

    public function delete(Waitlist $waitlist): bool
    {
        return $waitlist->delete();
    }

    private function buildQuery(array $filters = []): Builder
    {
        $query = Waitlist::with(['customer', 'details.item', 'details.service']);

        // Filter by company_id
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by customer_id
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Date filters
        if (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('start_date', '<=', $filters['date_to']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('notes', 'like', $term)
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", [$term]);
                    })
                    ->orWhereHas('details.service', function ($sq) use ($term) {
                        $sq->where('name', 'like', $term);
                    })
                    ->orWhereHas('details.item', function ($iq) use ($term) {
                        $iq->where('name', 'like', $term);
                    });
            });
        }

        return $query->latest();
    }
}
