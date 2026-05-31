<?php

namespace App\Contracts\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;

interface DocumentServiceInterface
{
    public function listDocuments(array $filters = []): Collection;

    public function getDocument(int $id): Document;

    public function createDocument(array $data, $file = null): Document;

    public function updateDocument(Document $document, array $data, $file = null): Document;

    public function deleteDocument(Document $document): bool;
}
