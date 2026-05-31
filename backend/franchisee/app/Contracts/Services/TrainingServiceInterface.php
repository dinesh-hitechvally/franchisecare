<?php

namespace App\Contracts\Services;

use Illuminate\Contracts\Auth\Authenticatable;

interface TrainingServiceInterface
{
    public function elearning(?Authenticatable $user): array;

    public function videos(): array;

    public function marketing(): array;

    public function show(?Authenticatable $user, int $id): array;

    public function updateProgress(Authenticatable $user, int $id, array $data): array;

    public function categories(): array;

    public function storeCategory(array $data): array;

    public function updateCategory(int $id, array $data): array;

    public function deleteCategory(int $id): array;

    public function items(array $filters): array;

    public function storeItem(array $data): array;

    public function updateItem(int $id, array $data): array;

    public function deleteItem(int $id): array;
}
