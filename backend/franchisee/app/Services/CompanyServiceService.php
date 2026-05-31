<?php

namespace App\Services;

use App\Contracts\Repositories\CompanyServiceRepositoryInterface;
use App\Contracts\Services\CompanyServiceServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CompanyServiceService implements CompanyServiceServiceInterface
{
    public function __construct(
        protected CompanyServiceRepositoryInterface $repository
    ) {}

    protected function getCompanyId(): int
    {
        return Auth::user()->company_id ?? 1;
    }

    public function all(): Collection
    {
        return $this->repository->all($this->getCompanyId());
    }

    public function updateAll(array $services): Collection
    {
        $companyId = $this->getCompanyId();
        $this->repository->updateAll($companyId, $services);
        return $this->repository->all($companyId);
    }
}
