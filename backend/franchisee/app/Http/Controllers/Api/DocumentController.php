<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\DocumentServiceInterface;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentServiceInterface $documentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'company_id' => $request->user()?->company_id,
            'visibility' => $request->input('visibility'),
            'category' => $request->input('category'),
            'search' => $request->input('search'),
        ];

        return response()->json($this->documentService->listDocuments(array_filter($filters)));
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'company_id' => $request->user()?->company_id,
            'user_id' => $request->user()?->id,
        ]);

        $document = $this->documentService->createDocument($data, $request->file('file'));

        return response()->json($document, 201);
    }

    public function show(Document $document): JsonResponse
    {
        return response()->json($document);
    }

    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        $document = $this->documentService->updateDocument(
            $document,
            $request->validated(),
            $request->file('file')
        );

        return response()->json($document);
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->documentService->deleteDocument($document);
        return response()->json(null, 204);
    }
}
