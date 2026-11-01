<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    protected $guarded = ['id', 'company_id', 'status'];

    protected function casts(): array
    {
        return ['trigger_config' => 'array', 'steps' => 'array'];
    }

    public function runs()
    {
        return $this->hasMany(WorkflowRun::class);
    }
}
