<?php

namespace App\Services;

use App\Contracts\Services\SmsCreditServiceInterface;
use App\Models\SmsCredit;
use App\Models\SmsCreditPurchase;
use Illuminate\Contracts\Auth\Authenticatable;

class SmsCreditService implements SmsCreditServiceInterface
{
    protected array $packages = [
        'sms_500' => [
            'id' => 'sms_500',
            'title' => 'SMS Credits ($100)',
            'price' => 100.00,
            'quantity' => 500,
            'rate' => 0.20,
        ],
        'sms_1000' => [
            'id' => 'sms_1000',
            'title' => 'SMS Credits ($180)',
            'price' => 180.00,
            'quantity' => 1000,
            'rate' => 0.18,
        ],
    ];

    public function index(Authenticatable $user): array
    {
        $companyId = $user->company_id;

        $credit = SmsCredit::firstOrCreate(
            ['company_id' => $companyId],
            ['balance' => 0, 'total_purchased' => 0, 'total_used' => 0]
        );

        return [
            'balance' => $credit->balance,
            'total_purchased' => $credit->total_purchased,
            'total_used' => $credit->total_used,
            'packages' => array_values($this->packages),
        ];
    }

    public function purchase(Authenticatable $user, array $data): array
    {
        $companyId = $user->company_id;
        $userId = $user->id;

        $package = $this->packages[$data['package_id']];

        $purchase = SmsCreditPurchase::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'package_id' => $data['package_id'],
            'quantity' => $package['quantity'],
            'amount' => $package['price'],
            'status' => 'completed',
            'purchased_at' => now(),
        ]);

        $credit = SmsCredit::firstOrCreate(
            ['company_id' => $companyId],
            ['balance' => 0, 'total_purchased' => 0, 'total_used' => 0]
        );

        $credit->increment('balance', $package['quantity']);
        $credit->increment('total_purchased', $package['quantity']);

        return [
            'message' => "Successfully purchased {$package['quantity']} SMS credits",
            'new_balance' => $credit->balance,
            'purchase' => $purchase,
        ];
    }

    public function history(Authenticatable $user): array
    {
        $companyId = $user->company_id;

        $purchases = SmsCreditPurchase::where('company_id', $companyId)
            ->with('user:id,name')
            ->orderBy('purchased_at', 'desc')
            ->paginate(20);

        return $purchases->toArray();
    }
}
