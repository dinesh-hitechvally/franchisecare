<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function index(Request $request)
    {
        $query = AppVersion::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('version', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);

        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logout_required' => 'nullable|boolean',
            'refresh_required' => 'nullable|boolean',
        ]);

        $version = AppVersion::create($validated);

        return response()->json($version, 201);
    }

    public function show(AppVersion $appVersion)
    {
        return response()->json($appVersion);
    }

    public function update(Request $request, AppVersion $appVersion)
    {
        $validated = $request->validate([
            'version' => 'sometimes|string|max:50',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'logout_required' => 'nullable|boolean',
            'refresh_required' => 'nullable|boolean',
        ]);

        $appVersion->update($validated);

        return response()->json($appVersion);
    }

    public function destroy(AppVersion $appVersion)
    {
        $appVersion->delete();

        return response()->json(['message' => 'Version deleted successfully']);
    }
}
