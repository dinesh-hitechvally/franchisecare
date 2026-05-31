<?php

namespace App\Contracts\Repositories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function findById(int $id): ?Document;

    public function findByIdOrFail(int $id): Document;

    public function create(array $data): Document;

    public function update(Document $document, array $data): Document;

    public function delete(Document $document): bool;
}
