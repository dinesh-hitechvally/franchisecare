<?php

namespace App\Contracts\Repositories;

use App\Models\BookingRecurring;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookingRecurringRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findById(int $id, array $relations = []): ?BookingRecurring;

    public function findByIdOrFail(int $id, array $relations = []): BookingRecurring;

    public function create(array $data): BookingRecurring;

    public function update(BookingRecurring $bookingRecurring, array $data): BookingRecurring;

    public function delete(BookingRecurring $bookingRecurring): bool;

    public function createDetail(BookingRecurring $bookingRecurring, array $data): void;

    public function deleteDetails(BookingRecurring $bookingRecurring): void;
}
