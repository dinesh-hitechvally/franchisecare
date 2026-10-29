<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class ContactList extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id', 'business_id'];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_contact_list');
    }
}
