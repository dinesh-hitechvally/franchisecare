<?php

namespace App\Contracts\Services;

use App\Models\User;

interface WebsiteSettingsServiceInterface
{
    public function show(User $user): array;

    public function update(User $user, array $data): array;
}
