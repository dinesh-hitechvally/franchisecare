<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    protected $guarded = ['id', 'company_id', 'slug', 'views'];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    protected function casts(): array
    {
        return ['design' => 'array', 'published' => 'boolean'];
    }
}
