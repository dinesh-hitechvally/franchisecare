<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Layout extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    protected $guarded = ['id', 'company_id'];

    protected function casts(): array
    {
        return ['design' => 'array'];
    }
}
