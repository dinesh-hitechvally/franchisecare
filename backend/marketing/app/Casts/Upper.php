<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * An enum column whose database values are UPPERCASE while the app, the API and the frontend keep using lowercase words.
 * Reading gives lowercase, writing stores uppercase. Queries on the column (and anything read through the query builder) use the UPPERCASE value.
 */
class Upper implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : strtolower((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : strtoupper((string) $value);
    }
}
