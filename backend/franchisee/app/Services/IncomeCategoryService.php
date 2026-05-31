<?php

namespace App\Services;

use App\Contracts\Repositories\IncomeCategoryRepositoryInterface;
use App\Contracts\Services\IncomeCategoryServiceInterface;
use App\Models\IncomeCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IncomeCategoryService implements IncomeCategoryServiceInterface
{
    public function __construct(
        protected IncomeCategoryRepositoryInterface $repository
    ) {}

    public function all(): Collection
    {
        $companyId = Auth::user()->company_id;
        return $this->repository->all($companyId);
    }

    public function create(array $data): IncomeCategory
    {
        $data['company_id'] = Auth::user()->company_id;
        $data['is_system'] = false;

        $category = $this->repository->create($data);

        return $category->loadCount('incomes');
    }

    public function update(IncomeCategory $category, array $data): IncomeCategory
    {
        if ($category->is_system) {
            throw new HttpException(403, 'System categories cannot be modified.');
        }

        $this->repository->update($category, $data);

        return $category->loadCount('incomes');
    }

    public function delete(IncomeCategory $category): void
    {
        if ($category->is_system) {
            throw new HttpException(403, 'System categories cannot be deleted.');
        }

        $this->repository->delete($category);
    }
}
