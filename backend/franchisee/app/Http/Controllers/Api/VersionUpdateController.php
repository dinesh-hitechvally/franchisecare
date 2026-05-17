<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VersionUpdate;
use Illuminate\Http\Request;

class VersionUpdateController extends Controller
{
    public function index()
    {
        $versions = VersionUpdate::where('is_published', true)
            ->orderByDesc('year')
            ->orderByDesc('release_date')
            ->orderBy('sort_order')
            ->get();

        // Group by month and year
        $grouped = $versions->groupBy(function ($version) {
            return $version->month . '-' . $version->year;
        })->map(function ($group) {
            $first = $group->first();
            return [
                'month' => $first->month,
                'year' => $first->year,
                'versions' => $group->map(function ($version) {
                    return [
                        'version' => $version->version_number,
                        'changes' => $version->changes,
                        'release_date' => $version->release_date?->format('Y-m-d'),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($grouped);
    }

    public function store(Request $request)
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

        $version = VersionUpdate::create($validated);

        return response()->json($version, 201);
    }

    public function update(Request $request, VersionUpdate $versionUpdate)
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

        $versionUpdate->update($validated);

        return response()->json($versionUpdate);
    }

    public function destroy(VersionUpdate $versionUpdate)
    {
        $versionUpdate->delete();

        return response()->json(null, 204);
    }
}
