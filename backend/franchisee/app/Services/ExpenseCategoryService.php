<?php

namespace App\Services;

use App\Contracts\Repositories\ExpenseCategoryRepositoryInterface;
use App\Contracts\Services\ExpenseCategoryServiceInterface;
use App\Models\ExpenseCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExpenseCategoryService implements ExpenseCategoryServiceInterface
{
    public function __construct(
        protected ExpenseCategoryRepositoryInterface $repository
    ) {}

    public function all(): Collection
    {
        $companyId = Auth::user()->company_id;
        return $this->repository->all($companyId);
    }

    public function create(array $data): ExpenseCategory
    {
        $data['company_id'] = Auth::user()->company_id;
        $data['is_system'] = false;

        $category = $this->repository->create($data);

        return $category->loadCount('expenses');
    }

    public function update(ExpenseCategory $category, array $data): ExpenseCategory
    {
        if ($category->is_system) {
            throw new HttpException(403, 'System categories cannot be modified.');
        }

        $this->repository->update($category, $data);

        return $category->loadCount('expenses');
    }

    public function delete(ExpenseCategory $category): void
    {
        if ($category->is_system) {
            throw new HttpException(403, 'System categories cannot be deleted.');
        }

        $this->repository->delete($category);
    }
}
