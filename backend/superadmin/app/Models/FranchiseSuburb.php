<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FranchiseSuburb extends Model
{
    protected $fillable = [
        'franchise_id',
        'suburb_name',
        'postcode',
        'state',
        'status',
    ];

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }
}
