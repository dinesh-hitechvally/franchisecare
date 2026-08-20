<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XeroItem extends Model
{
    protected $fillable = [
        'company_id',
        'reference_type',
        'reference_id',
        'xero_item_id',
        'code',
        'name',
        'is_tracked_as_inventory',
        'inventory_asset_account_code',
        'purchase_account_code',
        'purchase_unit_price',
        'purchase_tax_type',
        'sales_account_code',
        'sales_unit_price',
        'sales_tax_type',
        'quantity_on_hand',
        'status',
        'synced_at',
        'error',
    ];

    protected $casts = [
        'is_tracked_as_inventory' => 'boolean',
        'purchase_unit_price' => 'decimal:2',
        'sales_unit_price' => 'decimal:2',
        'quantity_on_hand' => 'decimal:2',
        'synced_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
