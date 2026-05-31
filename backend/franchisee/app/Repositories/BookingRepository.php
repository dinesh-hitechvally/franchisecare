<?php

namespace App\Repositories;

use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Single Responsibility Principle (SRP):
 * This repository handles ONLY data access operations for Booking.
 * It does not contain business logic - that belongs in the Service layer.
 * 
 * Open/Closed Principle (OCP):
 * This class implements an interface, making it open for extension
 * (create a new implementation) but closed for modification.
 * 
 * Liskov Substitution Principle (LSP):
 * Any class implementing BookingRepositoryInterface can be substituted
 * for this class without breaking the application.
 * 
 * Dependency Inversion Principle (DIP):
 * High-level modules (Services, Controllers) depend on the interface,
 * not this concrete implementation.
 */
class BookingRepository implements BookingRepositoryInterface
{
    /**
     * Default relations to eager load.
     */
    protected array $defaultRelations = ['customer', 'details.item', 'details.service'];

    /**
     * Get all bookings with optional filters.
     *
     * @param array $filters
     * @return Collection
     */
    public function getAll(array $filters = []): Collection
    {
        $query = $this->buildQuery($filters);
        return $query->latest()->get();
    }

    /**
     * Get paginated bookings with optional filters.
     *
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $query = $this->buildQuery($filters);
        return $query->latest()->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a booking by ID.
     *
     * @param int $id
     * @param array $relations
     * @return Booking|null
     */
    public function findById(int $id, array $relations = []): ?Booking
    {
        $relations = empty($relations) ? $this->defaultRelations : $relations;
        return Booking::with($relations)->find($id);
    }

    /**
     * Find a booking by ID or fail.
     *
     * @param int $id
     * @param array $relations
     * @return Booking
     */
    public function findByIdOrFail(int $id, array $relations = []): Booking
    {
        $relations = empty($relations) ? $this->defaultRelations : $relations;
        return Booking::with($relations)->findOrFail($id);
    }

    /**
     * Create a new booking.
     *
     * @param array $data
     * @return Booking
     */
    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    /**
     * Update an existing booking.
     *
     * @param Booking $booking
     * @param array $data
     * @return Booking
     */
    public function update(Booking $booking, array $data): Booking
    {
        $booking->update($data);
        return $booking;
    }

    /**
     * Delete a booking.
     *
     * @param Booking $booking
     * @return bool
     */
    public function delete(Booking $booking): bool
    {
        return $booking->delete();
    }

    /**
     * Create booking details for a booking.
     *
     * @param Booking $booking
     * @param array $services
     * @param int $companyId
     * @return void
     */
    public function createBookingDetails(Booking $booking, array $services, int $companyId): void
    {
        $serviceIds = collect($services)->pluck('service_id')->unique()->toArray();
        $serviceModels = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

        foreach ($services as $serviceItem) {
            $serviceId = $serviceItem['service_id'];
            $booking->details()->create([
                'company_id' => $companyId,
                'item_id' => $serviceItem['item_id'],
                'service_id' => $serviceId,
                'price' => $serviceItem['service_price'],
                'duration' => $serviceModels->has($serviceId) ? $serviceModels[$serviceId]->duration : 0,
            ]);
        }
    }

    /**
     * Update booking details (delete and recreate).
     *
     * @param Booking $booking
     * @param array $services
     * @return void
     */
    public function updateBookingDetails(Booking $booking, array $services): void
    {
        $serviceIds = collect($services)->pluck('service_id')->unique()->toArray();
        $serviceModels = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

        $booking->details()->delete();

        foreach ($services as $serviceItem) {
            $serviceId = $serviceItem['service_id'];
            $booking->details()->create([
                'company_id' => $booking->company_id,
                'item_id' => $serviceItem['item_id'],
                'service_id' => $serviceId,
                'price' => $serviceItem['service_price'],
                'duration' => $serviceModels->has($serviceId) ? $serviceModels[$serviceId]->duration : 0,
            ]);
        }
    }

    /**
     * Get booking with all relations loaded.
     *
     * @param Booking $booking
     * @return Booking
     */
    public function loadFullRelations(Booking $booking): Booking
    {
        return $booking->fresh($this->defaultRelations);
    }

    /**
     * Build the query with filters applied.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Booking::with($this->defaultRelations);

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

        // Filter by date range
        if (!empty($filters['dateFrom'])) {
            $query->where('start_date', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('start_date', '<=', $filters['dateTo']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';

            $query->where(function ($q) use ($term) {
                $q->where('notes', 'like', $term);

                $q->orWhereHas('customer', function ($customerQuery) use ($term) {
                    $customerQuery->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$term]);
                });

                $q->orWhereHas('details.service', function ($serviceQuery) use ($term) {
                    $serviceQuery->where('name', 'like', $term);
                });

                $q->orWhereHas('details.item', function ($itemQuery) use ($term) {
                    $itemQuery->where('name', 'like', $term);
                });
            });
        }

        return $query;
    }
}
