<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryUnitConversion extends Model
{
    protected $fillable = [
        'from_unit_id',
        'to_unit_id',
        'conversion_factor',
        'inventory_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the from unit
     */
    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    /**
     * Get the to unit
     */
    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    /**
     * Get the inventory item
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_id');
    }

    /**
     * Get conversion factor for given units and optional inventory item
     */
    public static function getConversionFactor(string $fromUnitName, string $toUnitName, ?int $inventoryId = null): ?float
    {
        // Get unit IDs from names
        $fromUnit = Unit::where('name', $fromUnitName)->where('is_active', true)->first();
        $toUnit = Unit::where('name', $toUnitName)->where('is_active', true)->first();

        if (!$fromUnit || !$toUnit) {
            return null;
        }

        $query = static::where('from_unit_id', $fromUnit->id)
            ->where('to_unit_id', $toUnit->id)
            ->where('is_active', true);

        // Try to find item-specific conversion first
        if ($inventoryId) {
            $specific = (clone $query)->where('inventory_id', $inventoryId)->first();
            if ($specific) {
                return (float) $specific->conversion_factor;
            }
        }

        // Fall back to general conversion
        $general = $query->whereNull('inventory_id')->first();
        return $general ? (float) $general->conversion_factor : null;
    }

    /**
     * Convert quantity from one unit to another
     */
    public static function convert(float $quantity, string $fromUnitName, string $toUnitName, ?int $inventoryId = null): ?float
    {
        if ($fromUnitName === $toUnitName) {
            return $quantity;
        }

        $factor = static::getConversionFactor($fromUnitName, $toUnitName, $inventoryId);
        return $factor ? $quantity * $factor : null;
    }
}
