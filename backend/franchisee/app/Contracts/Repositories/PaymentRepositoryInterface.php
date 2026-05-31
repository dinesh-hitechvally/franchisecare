<?php

namespace App\Contracts\Repositories;

use App\Models\PaymentTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    public function getHistory(int $companyId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function findById(int $id): ?PaymentTransaction;

    public function findByIdOrFail(int $id): PaymentTransaction;

    public function create(array $data): PaymentTransaction;

    public function update(PaymentTransaction $transaction, array $data): PaymentTransaction;
}
