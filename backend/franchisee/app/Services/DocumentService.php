<?php

namespace App\Services;

use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Contracts\Services\DocumentServiceInterface;
use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class DocumentService implements DocumentServiceInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository
    ) {}

    public function listDocuments(array $filters = []): Collection
    {
        return $this->documentRepository->getAll($filters);
    }

    public function getDocument(int $id): Document
    {
        return $this->documentRepository->findByIdOrFail($id);
    }

    public function createDocument(array $data, $file = null): Document
    {
        if ($file) {
            $path = $file->store('documents', 'public');
            $data['file_url'] = '/storage/' . $path;
            $data['file_type'] = strtolower($file->getClientOriginalExtension());
        }

        return $this->documentRepository->create($data);
    }

    public function updateDocument(Document $document, array $data, $file = null): Document
    {
        if ($file) {
            // Delete old file if exists
            if ($document->file_url) {
                $oldPath = str_replace('/storage/', '', $document->file_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $file->store('documents', 'public');
            $data['file_url'] = '/storage/' . $path;
            $data['file_type'] = strtolower($file->getClientOriginalExtension());
        }

        return $this->documentRepository->update($document, $data);
    }

    public function deleteDocument(Document $document): bool
    {
        // Delete file if exists
        if ($document->file_url) {
            $path = str_replace('/storage/', '', $document->file_url);
            Storage::disk('public')->delete($path);
        }

        return $this->documentRepository->delete($document);
    }
}
