<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\VersionUpdateServiceInterface;
use App\Models\VersionUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionUpdateController extends Controller
{
    public function __construct(
        protected VersionUpdateServiceInterface $versionUpdateService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->versionUpdateService->index());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version_number' => 'required|string',
            'month' => 'required|string',
            'year' => 'required|integer',
            'changes' => 'required|array',
            'release_date' => 'nullable|date',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ]);

        return response()->json($this->versionUpdateService->store($validated), 201);
    }

    public function update(Request $request, VersionUpdate $versionUpdate): JsonResponse
    {
        $validated = $request->validate([
            'version_number' => 'string',
            'month' => 'string',
            'year' => 'integer',
            'changes' => 'array',
            'release_date' => 'nullable|date',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ]);

        return response()->json($this->versionUpdateService->update($versionUpdate, $validated));
    }

    public function destroy(VersionUpdate $versionUpdate): JsonResponse
    {
        $this->versionUpdateService->destroy($versionUpdate);
        return response()->json(null, 204);
    }
}
