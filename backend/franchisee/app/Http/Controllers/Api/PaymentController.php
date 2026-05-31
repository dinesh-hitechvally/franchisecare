<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\PaymentServiceInterface;
use App\Http\Requests\Payment\PurchaseSmsCreditRequest;
use App\Http\Requests\Payment\PayInventoryOrderRequest;
use App\Http\Requests\Payment\PayBookingRequest;
use App\Models\PaymentTransaction;
use App\Models\SmsCredit;
use App\Models\SmsCreditPurchase;
use App\Models\InventoryOrder;
use App\Services\CyberSourceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentServiceInterface $paymentService,
        private CyberSourceService $cyberSource
    ) {}

    public function config(): JsonResponse
    {
        return response()->json([
            'configured' => $this->cyberSource->isConfigured(),
            'currency' => 'AUD',
            'supported_cards' => ['visa', 'mastercard', 'amex', 'discover'],
        ]);
    }

    public function generateCaptureContext(Request $request): JsonResponse
    {
        $targetOrigins = $request->input('target_origins', []);
        
        if (empty($targetOrigins)) {
            $targetOrigins = [
                config('app.frontend_url', 'http://localhost:5173'),
            ];
        }

        $result = $this->cyberSource->generateCaptureContext($targetOrigins);

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error'],
            ], 400);
        }

        return response()->json($result);
    }

    public function purchaseSmsCredits(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => 'required|string|in:sms_500,sms_1000',
            'card_number' => 'required|string|min:13|max:19',
            'expiration_month' => 'required|string|size:2',
            'expiration_year' => 'required|string|size:4',
            'cvv' => 'required|string|min:3|max:4',
            'billing.first_name' => 'required|string',
            'billing.last_name' => 'required|string',
            'billing.address' => 'nullable|string',
            'billing.city' => 'nullable|string',
            'billing.state' => 'nullable|string',
            'billing.postal_code' => 'nullable|string',
            'billing.country' => 'nullable|string',
            'billing.email' => 'required|email',
        ]);

        $companyId = Auth::user()->company_id;
        $userId = Auth::id();

        $packages = [
            'sms_500' => ['price' => 100.00, 'quantity' => 500],
            'sms_1000' => ['price' => 180.00, 'quantity' => 1000],
        ];

        $package = $packages[$validated['package_id']];
        $orderId = 'SMS-' . $companyId . '-' . time();

        $transaction = PaymentTransaction::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'type' => 'credit_purchase',
            'reference_type' => 'sms_credit',
            'amount' => $package['price'],
            'currency' => 'AUD',
            'status' => 'pending',
            'payment_method' => 'credit_card',
            'card_last_four' => substr($validated['card_number'], -4),
            'metadata' => [
                'package_id' => $validated['package_id'],
                'quantity' => $package['quantity'],
            ],
        ]);

        $paymentResult = $this->cyberSource->processPayment([
            'order_id' => $orderId,
            'amount' => $package['price'],
            'currency' => 'AUD',
            'card_number' => $validated['card_number'],
            'expiration_month' => $validated['expiration_month'],
            'expiration_year' => $validated['expiration_year'],
            'cvv' => $validated['cvv'],
            'billing' => $validated['billing'],
        ]);

        if (!$paymentResult['success']) {
            $transaction->update([
                'status' => 'failed',
                'error_message' => $paymentResult['error'] ?? 'Payment processing failed',
            ]);

            return response()->json([
                'success' => false,
                'error' => $paymentResult['error'] ?? 'Payment processing failed',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $transaction->update([
                'transaction_id' => $paymentResult['transaction_id'],
                'status' => 'completed',
                'authorization_code' => $paymentResult['authorization_code'],
                'response_code' => $paymentResult['response_code'],
                'processed_at' => now(),
            ]);

            $purchase = SmsCreditPurchase::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'package_id' => $validated['package_id'],
                'quantity' => $package['quantity'],
                'amount' => $package['price'],
                'status' => 'completed',
                'purchased_at' => now(),
            ]);

            $transaction->update([
                'reference_id' => $purchase->id,
            ]);

            $credit = SmsCredit::firstOrCreate(
                ['company_id' => $companyId],
                ['balance' => 0, 'total_purchased' => 0, 'total_used' => 0]
            );

            $credit->increment('balance', $package['quantity']);
            $credit->increment('total_purchased', $package['quantity']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully purchased {$package['quantity']} SMS credits",
                'transaction_id' => $paymentResult['transaction_id'],
                'new_balance' => $credit->balance,
                'purchase' => $purchase,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing SMS credit purchase', [
                'error' => $e->getMessage(),
                'transaction_id' => $paymentResult['transaction_id'],
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment was processed but an error occurred. Please contact support.',
                'transaction_id' => $paymentResult['transaction_id'],
            ], 500);
        }
    }

    public function payInventoryOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:inventory_orders,id',
            'card_number' => 'required|string|min:13|max:19',
            'expiration_month' => 'required|string|size:2',
            'expiration_year' => 'required|string|size:4',
            'cvv' => 'required|string|min:3|max:4',
            'billing.first_name' => 'required|string',
            'billing.last_name' => 'required|string',
            'billing.address' => 'nullable|string',
            'billing.city' => 'nullable|string',
            'billing.state' => 'nullable|string',
            'billing.postal_code' => 'nullable|string',
            'billing.country' => 'nullable|string',
            'billing.email' => 'required|email',
        ]);

        $companyId = Auth::user()->company_id;
        $userId = Auth::id();

        $order = InventoryOrder::where('id', $validated['order_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'This order has already been paid',
            ], 400);
        }

        $orderId = 'INV-' . $order->id . '-' . time();
        $amount = $order->total_amount ?? $order->total ?? 0;

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid order amount',
            ], 400);
        }

        $transaction = PaymentTransaction::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'type' => 'order',
            'reference_type' => 'inventory_order',
            'reference_id' => $order->id,
            'amount' => $amount,
            'currency' => 'AUD',
            'status' => 'pending',
            'payment_method' => 'credit_card',
            'card_last_four' => substr($validated['card_number'], -4),
            'metadata' => [
                'order_number' => $order->order_number ?? $order->id,
            ],
        ]);

        $paymentResult = $this->cyberSource->processPayment([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'AUD',
            'card_number' => $validated['card_number'],
            'expiration_month' => $validated['expiration_month'],
            'expiration_year' => $validated['expiration_year'],
            'cvv' => $validated['cvv'],
            'billing' => $validated['billing'],
        ]);

        if (!$paymentResult['success']) {
            $transaction->update([
                'status' => 'failed',
                'error_message' => $paymentResult['error'] ?? 'Payment processing failed',
            ]);

            return response()->json([
                'success' => false,
                'error' => $paymentResult['error'] ?? 'Payment processing failed',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $transaction->update([
                'transaction_id' => $paymentResult['transaction_id'],
                'status' => 'completed',
                'authorization_code' => $paymentResult['authorization_code'],
                'response_code' => $paymentResult['response_code'],
                'processed_at' => now(),
            ]);

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'transaction_id' => $paymentResult['transaction_id'],
                'order' => $order->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing inventory order payment', [
                'error' => $e->getMessage(),
                'transaction_id' => $paymentResult['transaction_id'],
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment was processed but an error occurred. Please contact support.',
                'transaction_id' => $paymentResult['transaction_id'],
            ], 500);
        }
    }

    public function transactionHistory(Request $request): JsonResponse
    {
        $filters = array_filter([
            'type' => $request->input('type'),
            'status' => $request->input('status'),
        ]);

        $perPage = (int) $request->input('per_page', 20);

        $history = $this->paymentService->getHistory($filters, $perPage);

        return response()->json([
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    public function showTransaction(PaymentTransaction $transaction): JsonResponse
    {
        return response()->json($transaction);
    }
}
            'reference_type' => 'booking',
            'reference_id' => $booking->id,
            'amount' => $amount,
            'currency' => 'AUD',
            'status' => 'pending',
            'payment_method' => 'credit_card',
            'card_last_four' => substr($validated['card_number'], -4),
            'metadata' => [
                'booking_date' => $booking->start_date,
                'customer_id' => $booking->customer_id,
            ],
        ]);

        // Process payment with CyberSource
        $paymentResult = $this->cyberSource->processPayment([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'AUD',
            'card_number' => $validated['card_number'],
            'expiration_month' => $validated['expiration_month'],
            'expiration_year' => $validated['expiration_year'],
            'cvv' => $validated['cvv'],
            'billing' => $validated['billing'],
        ]);

        if (!$paymentResult['success']) {
            $transaction->update([
                'status' => 'failed',
                'error_message' => $paymentResult['error'] ?? 'Payment processing failed',
            ]);

            return response()->json([
                'success' => false,
                'error' => $paymentResult['error'] ?? 'Payment processing failed',
            ], 400);
        }

        // Payment successful - update records
        try {
            DB::beginTransaction();

            // Update transaction record
            $transaction->update([
                'transaction_id' => $paymentResult['transaction_id'],
                'status' => 'completed',
                'authorization_code' => $paymentResult['authorization_code'],
                'response_code' => $paymentResult['response_code'],
                'processed_at' => now(),
            ]);

            // Update booking payment status
            $booking->update([
                'payment_status' => 'paid',
                'paid_amount' => ($booking->paid_amount ?? 0) + $amount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking payment processed successfully',
                'transaction_id' => $paymentResult['transaction_id'],
                'booking' => $booking->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing booking payment', [
                'error' => $e->getMessage(),
                'transaction_id' => $paymentResult['transaction_id'],
                'booking_id' => $booking->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment was processed but an error occurred. Please contact support.',
                'transaction_id' => $paymentResult['transaction_id'],
            ], 500);
        }
    }

    /**
     * Get transaction history
     */
    public function history(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $query = PaymentTransaction::where('company_id', $companyId)
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $transactions = $query->paginate($request->input('per_page', 20));

        return response()->json($transactions);
    }

    /**
     * Get single transaction details
     */
    public function show(PaymentTransaction $transaction)
    {
        $companyId = Auth::user()->company_id;

        if ($transaction->company_id !== $companyId) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($transaction);
    }

    /**
     * Refund a transaction
     */
    public function refund(Request $request, PaymentTransaction $transaction)
    {
        $companyId = Auth::user()->company_id;

        if ($transaction->company_id !== $companyId) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($transaction->status !== 'completed') {
            return response()->json([
                'error' => 'Only completed transactions can be refunded',
            ], 400);
        }

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01|max:' . $transaction->amount,
        ]);

        $refundAmount = $validated['amount'] ?? $transaction->amount;

        $result = $this->cyberSource->refundPayment(
            $transaction->transaction_id,
            $refundAmount,
            $transaction->currency
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Refund failed',
            ], 400);
        }

        $transaction->update([
            'status' => 'refunded',
            'metadata' => array_merge($transaction->metadata ?? [], [
                'refund_id' => $result['refund_id'],
                'refund_amount' => $refundAmount,
                'refund_date' => now()->toISOString(),
            ]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully',
            'refund_id' => $result['refund_id'],
            'transaction' => $transaction->fresh(),
        ]);
    }
}
