<?php

namespace App\Services;

use App\Contracts\Repositories\ServicePriceRepositoryInterface;
use App\Contracts\Services\ServicePriceServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ServicePriceService implements ServicePriceServiceInterface
{
    public function __construct(
        protected ServicePriceRepositoryInterface $repository
    ) {}

    protected function getCompanyId(): int
    {
        return Auth::user()->company_id ?? 1;
    }

    public function index(): Collection
    {
        return $this->repository->all($this->getCompanyId());
    }

    public function updateAll(array $services): Collection
    {
        return $this->repository->updateAll($this->getCompanyId(), $services);
    }
}
