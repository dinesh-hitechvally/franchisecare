<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class WorkflowRun extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['next_run_at' => 'datetime', 'locked_until' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
