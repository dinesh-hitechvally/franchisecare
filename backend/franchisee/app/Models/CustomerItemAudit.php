<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerItemAudit extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'item_id',
        'action_type',
        'name',
        'gender',
        'birth_date',
        'breed',
        'size',
        'image',
        'allergies',
        'notes',
        'signature',
        'is_active',
    ];

    public function item()
    {
        return $this->belongsTo(CustomerItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
