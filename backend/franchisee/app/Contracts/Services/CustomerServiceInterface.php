<?php

namespace App\Contracts\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerServiceInterface
{
    public function listCustomers(array $filters = []): Collection;

    public function getCustomer(int $id): Customer;

    public function createCustomer(array $data): Customer;

    public function updateCustomer(Customer $customer, array $data): Customer;

    public function archiveCustomer(Customer $customer): Customer;

    public function restoreCustomer(int $id): Customer;

    public function getCustomerHistory(int $customerId): LengthAwarePaginator;
}
