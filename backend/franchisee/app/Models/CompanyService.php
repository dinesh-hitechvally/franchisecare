<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyService extends Model
{
    protected $table = 'company_services';

    protected $fillable = [
        'company_id',
        'service_id',
        'price',
        'duration',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
