<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with('uploadedBy:id,name');

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        $perPage = $request->get('per_page', 15);
        
        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:manuals,templates,other',
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
            'status' => 'active',
        ]);

        return response()->json($document->load('uploadedBy:id,name'), 201);
    }

    public function show(Document $document)
    {
        return response()->json($document->load('uploadedBy:id,name'));
    }

    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|in:manuals,templates,other',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $document->update($validated);

        return response()->json($document->load('uploadedBy:id,name'));
    }

    public function destroy(Document $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }

    public function download(Document $document)
    {
        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }
}
