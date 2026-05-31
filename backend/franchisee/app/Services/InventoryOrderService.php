<?php

namespace App\Services;

use App\Contracts\Repositories\InventoryOrderRepositoryInterface;
use App\Contracts\Services\InventoryOrderServiceInterface;
use App\Models\InventoryOrder;
use Illuminate\Support\Facades\Auth;

class InventoryOrderService implements InventoryOrderServiceInterface
{
    public function __construct(
        protected InventoryOrderRepositoryInterface $repository
    ) {}

    public function paginate(array $filters, int $perPage): array
    {
        $companyId = Auth::user()->company_id;
        $paginator = $this->repository->paginate($companyId, $filters, $perPage);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function create(array $data): InventoryOrder
    {
        $data['company_id'] = Auth::user()->company_id;
        $data['user_id'] = Auth::id();

        return $this->repository->create($data);
    }

    public function update(InventoryOrder $order, array $data): array
    {
        $order = $this->repository->update($order, $data);

        return [
            'message' => 'Order updated successfully',
            'data' => $order,
        ];
    }

    public function delete(InventoryOrder $order): bool
    {
        if ($order->status !== 'pending') {
            return false;
        }

        $this->repository->delete($order);
        return true;
    }
}
