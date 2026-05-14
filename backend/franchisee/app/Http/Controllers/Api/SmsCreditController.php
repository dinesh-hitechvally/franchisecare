<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsCredit;
use App\Models\SmsCreditPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsCreditController extends Controller
{
    /**
     * Get available SMS packages and current balance
     */
    public function index()
    {
        $companyId = Auth::user()->company_id;
        
        $credit = SmsCredit::firstOrCreate(
            ['company_id' => $companyId],
            ['balance' => 0, 'total_purchased' => 0, 'total_used' => 0]
        );
        
        $packages = [
            [
                'id' => 'sms_500',
                'title' => 'SMS Credits ($100)',
                'price' => 100.00,
                'quantity' => 500,
                'rate' => 0.20,
            ],
            [
                'id' => 'sms_1000',
                'title' => 'SMS Credits ($180)',
                'price' => 180.00,
                'quantity' => 1000,
                'rate' => 0.18,
            ],
        ];
        
        return response()->json([
            'balance' => $credit->balance,
            'total_purchased' => $credit->total_purchased,
            'total_used' => $credit->total_used,
            'packages' => $packages,
        ]);
    }

    /**
     * Purchase SMS credits
     */
    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|string|in:sms_500,sms_1000',
        ]);
        
        $companyId = Auth::user()->company_id;
        $userId = Auth::id();
        
        // Define packages
        $packages = [
            'sms_500' => ['price' => 100.00, 'quantity' => 500],
            'sms_1000' => ['price' => 180.00, 'quantity' => 1000],
        ];
        
        $package = $packages[$validated['package_id']];
        
        // Create purchase record
        $purchase = SmsCreditPurchase::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'package_id' => $validated['package_id'],
            'quantity' => $package['quantity'],
            'amount' => $package['price'],
            'status' => 'completed', // In real app, this would be 'pending' until payment confirmed
            'purchased_at' => now(),
        ]);
        
        // Update credit balance
        $credit = SmsCredit::firstOrCreate(
            ['company_id' => $companyId],
            ['balance' => 0, 'total_purchased' => 0, 'total_used' => 0]
        );
        
        $credit->increment('balance', $package['quantity']);
        $credit->increment('total_purchased', $package['quantity']);
        
        return response()->json([
            'message' => "Successfully purchased {$package['quantity']} SMS credits",
            'new_balance' => $credit->balance,
            'purchase' => $purchase,
        ]);
    }

    /**
     * Get purchase history
     */
    public function history()
    {
        $companyId = Auth::user()->company_id;
        
        $purchases = SmsCreditPurchase::where('company_id', $companyId)
            ->with('user:id,name')
            ->orderBy('purchased_at', 'desc')
            ->paginate(20);
        
        return response()->json($purchases);
    }
}
