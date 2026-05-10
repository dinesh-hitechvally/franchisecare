<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTake extends Model
{
    use HasFactory;

    protected $table = 'stock_take';
    protected $primaryKey = 'id';
    protected $fillable = ['appID', 'companyID', 'inventory_category_id', 'updated_at'];
    public $timestamps = true;

    protected $dates = ['created_at', 'updated_at'];
}
