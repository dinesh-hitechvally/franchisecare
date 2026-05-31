<?php

namespace App\Contracts\Services;

use App\Models\News;
use Illuminate\Contracts\Auth\Authenticatable;

interface NewsServiceInterface
{
    public function index(array $filters): array;

    public function store(Authenticatable $user, array $data): News;

    public function show(News $news): News;

    public function update(News $news, array $data): News;

    public function destroy(News $news): bool;

    public function publish(News $news): News;
}
