<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XeroSetting extends Model
{
    protected $fillable = [
        'company_id',
        'default_supplier_name',
        'bank_account_code',
        'inventory_asset_account_code',
        'inventory_cogs_account_code',
        'inventory_sales_account_code',
        'service_sales_account_code',
        'default_tax_type',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The keys this setting covers. There is no config/.env fallback for these -
     * each company must configure its own values via the Xero settings screen,
     * since account codes and tax types are specific to that company's Xero org.
     */
    public static function defaults(): array
    {
        return [
            'default_supplier_name' => '',
            'bank_account_code' => '',
            'inventory_asset_account_code' => '',
            'inventory_cogs_account_code' => '',
            'inventory_sales_account_code' => '',
            'service_sales_account_code' => '',
            'default_tax_type' => '',
        ];
    }

    /**
     * Resolve the effective settings for a company: its saved values, falling back to
     * an empty string for any field that hasn't been configured yet.
     */
    public static function resolveForCompany(int $companyId): array
    {
        $saved = static::where('company_id', $companyId)->first();
        $defaults = static::defaults();

        if (!$saved) {
            return $defaults;
        }

        $resolved = [];
        foreach ($defaults as $key => $default) {
            $resolved[$key] = $saved->{$key} ?? $default;
        }

        return $resolved;
    }
}
