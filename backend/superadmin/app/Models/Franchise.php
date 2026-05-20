<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Franchise extends Model
{
    protected $fillable = [
        'name',
        'code',
        'owner_name',
        'email',
        'phone',
        'mobile',
        'address',
        'suburb',
        'state',
        'postcode',
        'abn',
        'status',
        'logo',
        'franchise_fee',
        'royalty_percentage',
        'marketing_fee',
        'start_date',
        'end_date',
        'contract_length',
        'territory',
        'notes',
    ];

    protected $casts = [
        'franchise_fee' => 'decimal:2',
        'royalty_percentage' => 'decimal:2',
        'marketing_fee' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(FranchiseUser::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(FranchiseService::class);
    }

    public function suburbs(): HasMany
    {
        return $this->hasMany(FranchiseSuburb::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FranchisePayment::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(FranchiseAudit::class);
    }
}
