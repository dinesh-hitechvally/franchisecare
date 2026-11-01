<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AutomationHistory extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $table = 'automation_history';

    const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
