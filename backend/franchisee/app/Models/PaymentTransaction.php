<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'transaction_id',
        'type',              // 'credit_purchase', 'order', 'booking'
        'reference_type',    // 'sms_credit', 'inventory_order', 'booking'
        'reference_id',
        'amount',
        'currency',
        'status',            // 'pending', 'completed', 'failed', 'refunded', 'voided'
        'payment_method',
        'card_last_four',
        'card_brand',
        'authorization_code',
        'response_code',
        'error_message',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
