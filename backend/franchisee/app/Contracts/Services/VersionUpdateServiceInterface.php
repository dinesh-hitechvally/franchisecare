<?php

namespace App\Contracts\Services;

use App\Models\VersionUpdate;
use Illuminate\Support\Collection;

interface VersionUpdateServiceInterface
{
    public function index(): Collection;

    public function store(array $data): VersionUpdate;

    public function update(VersionUpdate $versionUpdate, array $data): VersionUpdate;

    public function destroy(VersionUpdate $versionUpdate): void;
}
