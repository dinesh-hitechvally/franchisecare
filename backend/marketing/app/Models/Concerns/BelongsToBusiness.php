<?php

namespace App\Models\Concerns;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Scopes every query to the authenticated user's business and stamps
 * business_id on create, so tenants can never see each other's data.
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $q) {
            if ($user = Auth::user()) {
                $q->where($q->getModel()->getTable().'.business_id', $user->business_id);
            }
        });

        static::creating(function ($model) {
            if (! $model->business_id && ($user = Auth::user())) {
                $model->business_id = $user->business_id;
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
