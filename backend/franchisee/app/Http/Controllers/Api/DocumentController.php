<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Document::query()->latest();

        $user = $request->user();

        if ($user && $user->company_id) {
            $query->where(function ($q) use ($user) {
                $q->where('visibility', 'global')
                  ->orWhere('company_id', $user->company_id);
            });
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->input('visibility'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $metadata = json_decode((string) $request->input('metadata', '{}'), true);
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $title = $request->input('title', $metadata['title'] ?? null);
        $description = $request->input('description', $metadata['description'] ?? null);
        $visibility = $request->input('visibility', $metadata['visibility'] ?? 'global');
        $category = $request->input('category', $metadata['category'] ?? 'other');

        $validated = validator([
            'title' => $title,
            'description' => $description,
            'visibility' => $visibility,
            'category' => $category,
            'file' => $request->file('file'),
        ], [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:global,franchise',
            'category' => 'required|in:manual,template,other,general',
            'file' => 'nullable|file|max:10240',
        ])->validate();

        $fileUrl = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('documents', 'public');
            $fileUrl = '/storage/' . $path;
            $fileType = strtolower((string) $request->file('file')->getClientOriginalExtension());
        }

        $document = Document::create([
            'company_id' => $request->user()?->company_id,
            'user_id' => $request->user()?->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_url' => $fileUrl,
            'file_type' => $fileType,
            'visibility' => $validated['visibility'],
            'category' => $validated['category'],
        ]);

        return response()->json($document, 201);
    }

    public function show(Document $document): JsonResponse
    {
        return response()->json($document);
    }

    public function update(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'file_url' => 'nullable|string|max:2048',
            'file_type' => 'nullable|string|max:20',
            'visibility' => 'sometimes|required|in:global,franchise',
            'category' => 'sometimes|required|in:manual,template,other,general',
        ]);

        $document->update($validated);

        return response()->json($document);
    }

    public function destroy(Document $document): JsonResponse
    {
        $document->delete();

        return response()->json(null, 204);
    }
}
