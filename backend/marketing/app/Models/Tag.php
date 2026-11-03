<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    protected $guarded = ['id', 'company_id'];

    public function people()
    {
        return $this->belongsToMany(Person::class, 'person_tag')->using(PersonTag::class);
    }
}
