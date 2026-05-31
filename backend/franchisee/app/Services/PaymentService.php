<?php

namespace App\Services;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Contracts\Services\PaymentServiceInterface;
use App\Models\PaymentTransaction;
use App\Models\SmsCredit;
use App\Services\CyberSourceService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private CyberSourceService $cyberSourceService
    ) {}

    public function getConfig(): array
    {
        return [
            'cybersourceEnabled' => config('services.cybersource.enabled', false),
            'merchantId' => config('services.cybersource.merchant_id'),
        ];
    }

    public function generateCaptureContext(array $targetOrigins = []): array
    {
        return $this->cyberSourceService->generateCaptureContext($targetOrigins);
    }

    public function purchaseSmsCredits(array $data): array
    {
        $user = Auth::user();
        $companyId = $user->company_id ?? $user->franchise_id;

        // Process payment
        $paymentResult = $this->cyberSourceService->processPayment([
            'transientToken' => $data['transient_token'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'AUD',
        ]);

        if (!$paymentResult['success']) {
            return $paymentResult;
        }

        // Create payment transaction record
        $transaction = $this->paymentRepository->create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'type' => 'sms_credit',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'AUD',
            'status' => 'completed',
            'transaction_id' => $paymentResult['transactionId'],
            'payment_method' => 'cybersource',
            'metadata' => [
                'credits' => $data['credits'],
                'package' => $data['package'] ?? null,
            ],
        ]);

        // Add SMS credits
        $smsCredit = SmsCredit::firstOrCreate(
            ['company_id' => $companyId],
            ['credits' => 0]
        );
        $smsCredit->increment('credits', $data['credits']);

        return [
            'success' => true,
            'message' => 'SMS credits purchased successfully',
            'transaction' => $transaction,
            'new_balance' => $smsCredit->credits,
        ];
    }

    public function payInventoryOrder(array $data): array
    {
        $user = Auth::user();
        $companyId = $user->company_id ?? $user->franchise_id;

        // Process payment
        $paymentResult = $this->cyberSourceService->processPayment([
            'transientToken' => $data['transient_token'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'AUD',
        ]);

        if (!$paymentResult['success']) {
            return $paymentResult;
        }

        // Create payment transaction record
        $transaction = $this->paymentRepository->create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'type' => 'inventory_order',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'AUD',
            'status' => 'completed',
            'transaction_id' => $paymentResult['transactionId'],
            'payment_method' => 'cybersource',
            'metadata' => [
                'order_id' => $data['order_id'],
            ],
        ]);

        return [
            'success' => true,
            'message' => 'Payment processed successfully',
            'transaction' => $transaction,
        ];
    }

    public function payBooking(array $data): array
    {
        $user = Auth::user();
        $companyId = $user->company_id ?? $user->franchise_id;

        // Process payment
        $paymentResult = $this->cyberSourceService->processPayment([
            'transientToken' => $data['transient_token'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'AUD',
        ]);

        if (!$paymentResult['success']) {
            return $paymentResult;
        }

        // Create payment transaction record
        $transaction = $this->paymentRepository->create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'type' => 'booking',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'AUD',
            'status' => 'completed',
            'transaction_id' => $paymentResult['transactionId'],
            'payment_method' => 'cybersource',
            'metadata' => [
                'booking_id' => $data['booking_id'],
                'customer_id' => $data['customer_id'] ?? null,
            ],
        ]);

        return [
            'success' => true,
            'message' => 'Booking payment processed successfully',
            'transaction' => $transaction,
        ];
    }

    public function getHistory(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $user = Auth::user();
        $companyId = $user->company_id ?? $user->franchise_id;

        return $this->paymentRepository->getHistory($companyId, $filters, $perPage);
    }

    public function getTransaction(int $id): PaymentTransaction
    {
        return $this->paymentRepository->findByIdOrFail($id);
    }

    public function refundTransaction(PaymentTransaction $transaction, ?float $amount = null): array
    {
        $refundAmount = $amount ?? $transaction->amount;

        // Process refund via CyberSource
        $refundResult = $this->cyberSourceService->processRefund(
            $transaction->transaction_id,
            $refundAmount,
            $transaction->currency
        );

        if (!$refundResult['success']) {
            return $refundResult;
        }

        // Update transaction status
        $this->paymentRepository->update($transaction, [
            'status' => $amount && $amount < $transaction->amount ? 'partially_refunded' : 'refunded',
            'refunded_amount' => ($transaction->refunded_amount ?? 0) + $refundAmount,
        ]);

        return [
            'success' => true,
            'message' => 'Refund processed successfully',
        ];
    }
}
