<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id', 'business_id', 'status', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return [
            'design' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function list()
    {
        return $this->belongsTo(ContactList::class, 'contact_list_id');
    }

    public function logs()
    {
        return $this->hasMany(MessageLog::class);
    }
}
