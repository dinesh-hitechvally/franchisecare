<?php

namespace App\Repositories;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Models\PaymentTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function getHistory(int $companyId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PaymentTransaction::where('company_id', $companyId)
            ->orderBy('created_at', 'desc');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?PaymentTransaction
    {
        return PaymentTransaction::find($id);
    }

    public function findByIdOrFail(int $id): PaymentTransaction
    {
        return PaymentTransaction::findOrFail($id);
    }

    public function create(array $data): PaymentTransaction
    {
        return PaymentTransaction::create($data);
    }

    public function update(PaymentTransaction $transaction, array $data): PaymentTransaction
    {
        $transaction->update($data);
        return $transaction->fresh();
    }
}
