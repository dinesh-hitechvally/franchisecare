<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Scopes every query to the authenticated user's company and stamps
 * company_id on create, so tenants can never see each other's data.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $q) {
            if ($user = Auth::user()) {
                $q->where($q->getModel()->getTable().'.company_id', $user->company_id);
            }
        });

        static::creating(function ($model) {
            if (! $model->company_id && ($user = Auth::user())) {
                $model->company_id = $user->company_id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
