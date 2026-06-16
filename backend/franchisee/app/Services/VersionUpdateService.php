<?php

namespace App\Services;

use App\Contracts\Services\VersionUpdateServiceInterface;
use App\Models\VersionUpdate;
use Illuminate\Support\Collection;

class VersionUpdateService implements VersionUpdateServiceInterface
{
    public function index(): Collection
    {
        $versions = VersionUpdate::where('is_published', true)
            ->orderByDesc('year')
            ->orderByDesc('release_date')
            ->orderBy('sort_order')
            ->get();

        return $versions->groupBy(function ($version) {
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
    }

    public function store(array $data): VersionUpdate
    {
        return VersionUpdate::create($data);
    }

    public function update(VersionUpdate $versionUpdate, array $data): VersionUpdate
    {
        $versionUpdate->update($data);
        return $versionUpdate;
    }

    public function destroy(VersionUpdate $versionUpdate): void
    {
        $versionUpdate->delete();
    }
}
