<?php

namespace App\Repositories;

use App\Contracts\Repositories\BookingRecurringRepositoryInterface;
use App\Models\BookingRecurring;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BookingRecurringRepository implements BookingRecurringRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        return $this->buildQuery($filters)->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->paginate($perPage);
    }

    public function findById(int $id, array $relations = []): ?BookingRecurring
    {
        $query = BookingRecurring::query();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->find($id);
    }

    public function findByIdOrFail(int $id, array $relations = []): BookingRecurring
    {
        $query = BookingRecurring::query();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->findOrFail($id);
    }

    public function create(array $data): BookingRecurring
    {
        return BookingRecurring::create($data);
    }

    public function update(BookingRecurring $bookingRecurring, array $data): BookingRecurring
    {
        $bookingRecurring->update($data);
        return $bookingRecurring->fresh();
    }

    public function delete(BookingRecurring $bookingRecurring): bool
    {
        return $bookingRecurring->delete();
    }

    public function createDetail(BookingRecurring $bookingRecurring, array $data): void
    {
        $bookingRecurring->details()->create($data);
    }

    public function deleteDetails(BookingRecurring $bookingRecurring): void
    {
        $bookingRecurring->details()->delete();
    }

    private function buildQuery(array $filters = []): Builder
    {
        $query = BookingRecurring::with(['customer', 'details.item', 'details.service', 'bookings']);

        // Filter by company_id
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('notes', 'like', $term)
                    ->orWhereHas('customer', function ($customerQuery) use ($term) {
                        $customerQuery->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$term]);
                    })
                    ->orWhereHas('details.service', function ($serviceQuery) use ($term) {
                        $serviceQuery->where('name', 'like', $term);
                    });
            });
        }

        // Hide expired filter
        if (!empty($filters['hide_expired']) && ($filters['status'] ?? null) !== 'cancelled') {
            $query->whereNotNull('repeat_until')
                ->whereDate('repeat_until', '>=', now()->toDateString());
        }

        return $query->latest();
    }
}
