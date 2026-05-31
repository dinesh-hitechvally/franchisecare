<?php

namespace App\Contracts\Services;

use App\Models\Blockout;
use Illuminate\Pagination\LengthAwarePaginator;

interface BlockoutServiceInterface
{
    public function listBlockouts(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function getBlockout(int $id): Blockout;

    public function createBlockout(array $data): Blockout;

    public function updateBlockout(Blockout $blockout, array $data): Blockout;

    public function deleteBlockout(Blockout $blockout): bool;

    public function getBlockoutHistory(Blockout $blockout): LengthAwarePaginator;
}
