<?php

namespace App\Repositories;

use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        return $this->buildQuery($filters)->get();
    }

    public function findById(int $id): ?Document
    {
        return Document::find($id);
    }

    public function findByIdOrFail(int $id): Document
    {
        return Document::findOrFail($id);
    }

    public function create(array $data): Document
    {
        return Document::create($data);
    }

    public function update(Document $document, array $data): Document
    {
        $document->update($data);
        return $document->fresh();
    }

    public function delete(Document $document): bool
    {
        return $document->delete();
    }

    private function buildQuery(array $filters = []): Builder
    {
        $query = Document::query()->latest();

        // Filter by company visibility
        if (!empty($filters['company_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('visibility', 'global')
                    ->orWhere('company_id', $filters['company_id']);
            });
        }

        if (!empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        return $query;
    }
}
