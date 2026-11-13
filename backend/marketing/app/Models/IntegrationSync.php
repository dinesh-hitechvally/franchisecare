<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/** One import run of an Integration: what it fetched and what it did with it. */
class IntegrationSync extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'company_id'];

    protected function casts(): array
    {
        return ['trigger' => \App\Casts\Upper::class, 'status' => \App\Casts\Upper::class, 'full' => 'boolean', 'since' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function integration()
    {
        return $this->belongsTo(Integration::class)->withTrashed();
    }
}
