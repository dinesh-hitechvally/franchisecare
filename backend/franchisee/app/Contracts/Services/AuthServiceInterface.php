<?php

namespace App\Contracts\Services;

use App\Models\User;

interface AuthServiceInterface
{
    public function login(string $email, string $password): array;
    public function logout(User $user): void;
    public function changePassword(User $user, string $currentPassword, string $newPassword): void;
}
