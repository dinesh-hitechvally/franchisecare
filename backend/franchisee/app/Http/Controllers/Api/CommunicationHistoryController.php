<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\CommunicationServiceInterface;
use App\Http\Requests\Communication\SendEmailRequest;
use App\Http\Requests\Communication\SendBulkEmailRequest;
use App\Models\SmsHistory;
use App\Models\EmailHistory;
use App\Models\Customer;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CommunicationHistoryController extends Controller
{
    public function __construct(
        private CommunicationServiceInterface $communicationService
    ) {}

    // ─── SMS History ────────────────────────────────────────────────────────────

    public function smsIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 25);
        $filters = [
            'status' => $request->input('status'),
            'company_id' => $request->user()?->company_id,
        ];

        $data = $this->communicationService->listSmsHistory(array_filter($filters), $perPage);

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ],
        ]);
    }

    public function smsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_number'        => 'required|string|max:30',
            'customer_name'    => 'nullable|string|max:255',
            'message'          => 'required|string',
            'status'           => 'nullable|in:queued,sent,failed',
            'gateway_response' => 'nullable|string|max:500',
            'sent_at'          => 'nullable|date',
        ]);

        $validated['company_id'] = $request->user()?->company_id;
        $validated['status'] = $validated['status'] ?? 'queued';

        $record = $this->communicationService->createSms($validated);

        return response()->json($record, 201);
    }

    public function smsShow(SmsHistory $smsHistory): JsonResponse
    {
        return response()->json($smsHistory);
    }

    public function smsDestroy(SmsHistory $smsHistory): JsonResponse
    {
        $this->communicationService->deleteSms($smsHistory);
        return response()->json(null, 204);
    }

    // ─── Email History ───────────────────────────────────────────────────────────

    public function emailIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 25);
        $filters = [
            'status' => $request->input('status'),
            'company_id' => $request->user()?->company_id,
        ];

        $data = $this->communicationService->listEmailHistory(array_filter($filters), $perPage);

        return response()->json([
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ],
        ]);
    }

    public function emailStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_email'      => 'required|email|max:255',
            'to_email'        => 'required|email|max:255',
            'subject'         => 'required|string|max:500',
            'body'            => 'nullable|string',
            'status'          => 'nullable|in:queued,sent,failed',
            'mailer_response' => 'nullable|string|max:500',
            'sent_at'         => 'nullable|date',
        ]);

        $validated['company_id'] = $request->user()?->company_id;
        $validated['status'] = $validated['status'] ?? 'queued';

        $record = $this->communicationService->createEmail($validated);

        return response()->json($record, 201);
    }

    public function emailShow(EmailHistory $emailHistory): JsonResponse
    {
        return response()->json($emailHistory);
    }

    public function emailDestroy(EmailHistory $emailHistory): JsonResponse
    {
        $this->communicationService->deleteEmail($emailHistory);
        return response()->json(null, 204);
    }

    // ─── Send Email (Actually delivers the email) ────────────────────────────────

    public function sendEmail(SendEmailRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $fromEmail = $request->user()?->email ?? config('mail.from.address', 'no-reply@example.com');
        $fromName = $validated['from_name'] ?? $request->user()?->name ?? config('mail.from.name', 'System');

        try {
            Mail::html($validated['body'], function ($message) use ($validated, $fromEmail, $fromName) {
                $message->to($validated['to_email'])
                        ->from($fromEmail, $fromName)
                        ->subject($validated['subject']);
            });

            $record = $this->communicationService->createEmail([
                'company_id'      => $request->user()?->company_id,
                'user_id'         => $request->user()?->id,
                'from_email'      => $fromEmail,
                'to_email'        => $validated['to_email'],
                'subject'         => $validated['subject'],
                'body'            => $validated['body'],
                'status'          => 'sent',
                'mailer_response' => 'Email sent successfully',
                'sent_at'         => now(),
            ]);

            return response()->json([
                'message' => 'Email sent successfully',
                'data'    => $record,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());

            $record = $this->communicationService->createEmail([
                'company_id'      => $request->user()?->company_id,
                'user_id'         => $request->user()?->id,
                'from_email'      => $fromEmail,
                'to_email'        => $validated['to_email'],
                'subject'         => $validated['subject'],
                'body'            => $validated['body'],
                'status'          => 'failed',
                'mailer_response' => $e->getMessage(),
                'sent_at'         => now(),
            ]);

            return response()->json([
                'message' => 'Failed to send email',
                'error'   => $e->getMessage(),
                'data'    => $record,
            ], 500);
        }
    }

    public function sendBulkEmail(SendBulkEmailRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $fromEmail = $request->user()?->email ?? config('mail.from.address', 'no-reply@example.com');
        $fromName = $validated['from_name'] ?? $request->user()?->name ?? config('mail.from.name', 'System');

        $customers = Customer::whereIn('id', $validated['customer_ids'])
            ->whereNotNull('email')
            ->get();

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => [],
        ];

        foreach ($customers as $customer) {
            if (empty($customer->email)) {
                $results['skipped']++;
                $results['details'][] = [
                    'customer_id' => $customer->id,
                    'customer_name' => trim($customer->first_name . ' ' . $customer->last_name),
                    'status' => 'skipped',
                    'reason' => 'No email address',
                ];
                continue;
            }

            $customerName = trim($customer->first_name . ' ' . $customer->last_name);
            $personalizedBody = str_replace(
                ['{{customer_name}}', '{{first_name}}', '{{last_name}}'],
                [$customerName, $customer->first_name ?? '', $customer->last_name ?? ''],
                $validated['body']
            );

            try {
                Mail::html($personalizedBody, function ($message) use ($customer, $validated, $fromEmail, $fromName) {
                    $message->to($customer->email)
                            ->from($fromEmail, $fromName)
                            ->subject($validated['subject']);
                });

                $this->communicationService->createEmail([
                    'company_id'      => $request->user()?->company_id,
                    'user_id'         => $request->user()?->id,
                    'from_email'      => $fromEmail,
                    'to_email'        => $customer->email,
                    'subject'         => $validated['subject'],
                    'body'            => $personalizedBody,
                    'status'          => 'sent',
                    'mailer_response' => 'Bulk email sent successfully',
                    'sent_at'         => now(),
                ]);

                $results['sent']++;
                $results['details'][] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customerName,
                    'email' => $customer->email,
                    'status' => 'sent',
                ];

            } catch (\Exception $e) {
                Log::error('Failed to send bulk email to ' . $customer->email . ': ' . $e->getMessage());

                $this->communicationService->createEmail([
                    'company_id'      => $request->user()?->company_id,
                    'user_id'         => $request->user()?->id,
                    'from_email'      => $fromEmail,
                    'to_email'        => $customer->email,
                    'subject'         => $validated['subject'],
                    'body'            => $personalizedBody,
                    'status'          => 'failed',
                    'mailer_response' => $e->getMessage(),
                    'sent_at'         => now(),
                ]);

                $results['failed']++;
                $results['details'][] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customerName,
                    'email' => $customer->email,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $totalRequested = count($validated['customer_ids']);
        $message = "Bulk email completed: {$results['sent']} sent, {$results['failed']} failed, {$results['skipped']} skipped out of {$totalRequested} customers";

        return response()->json([
            'message' => $message,
            'results' => $results,
        ], $results['failed'] > 0 && $results['sent'] === 0 ? 500 : 200);
    }

    public function sendBookingList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_ids'  => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id',
            'date_from'     => 'nullable|date',
            'date_to'       => 'nullable|date|after_or_equal:date_from',
            'include_completed' => 'nullable|boolean',
            'include_cancelled' => 'nullable|boolean',
            'subject'       => 'nullable|string|max:500',
            'intro_message' => 'nullable|string',
        ]);

        $fromEmail = $request->user()?->email ?? config('mail.from.address', 'no-reply@example.com');
        $fromName = $request->user()?->name ?? config('mail.from.name', 'System');
        $subject = $validated['subject'] ?? 'Your Booking Summary';
        $introMessage = $validated['intro_message'] ?? 'Here is a summary of your bookings:';

        $customers = Customer::whereIn('id', $validated['customer_ids'])
            ->whereNotNull('email')
            ->get();

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => [],
        ];

        foreach ($customers as $customer) {
            if (empty($customer->email)) {
                $results['skipped']++;
                $results['details'][] = [
                    'customer_id' => $customer->id,
                    'customer_name' => trim($customer->first_name . ' ' . $customer->last_name),
                    'status' => 'skipped',
                    'reason' => 'No email address',
                ];
                continue;
            }

            $bookingsQuery = Booking::where('customer_id', $customer->id)
                ->with(['details.service', 'details.item']);

            if (!empty($validated['date_from'])) {
                $bookingsQuery->whereDate('start_date', '>=', $validated['date_from']);
            }
            if (!empty($validated['date_to'])) {
                $bookingsQuery->whereDate('start_date', '<=', $validated['date_to']);
            }
            if (empty($validated['include_completed'])) {
                $bookingsQuery->where('status', '!=', 'COMPLETED');
            }
            if (empty($validated['include_cancelled'])) {
                $bookingsQuery->where('status', '!=', 'CANCELLED');
            }

            $bookings = $bookingsQuery->orderBy('start_date')->get();

            if ($bookings->isEmpty()) {
                $results['skipped']++;
                $results['details'][] = [
                    'customer_id' => $customer->id,
                    'customer_name' => trim($customer->first_name . ' ' . $customer->last_name),
                    'status' => 'skipped',
                    'reason' => 'No bookings found',
                ];
                continue;
            }

            $customerName = trim($customer->first_name . ' ' . $customer->last_name);
            $body = $this->buildBookingListHtml($customer, $bookings, $introMessage);

            try {
                Mail::html($body, function ($message) use ($customer, $subject, $fromEmail, $fromName) {
                    $message->to($customer->email)
                            ->from($fromEmail, $fromName)
                            ->subject($subject);
                });

                $this->communicationService->createEmail([
                    'company_id'      => $request->user()?->company_id,
                    'user_id'         => $request->user()?->id,
                    'from_email'      => $fromEmail,
                    'to_email'        => $customer->email,
                    'subject'         => $subject,
                    'body'            => $body,
                    'status'          => 'sent',
                    'mailer_response' => 'Booking list email sent successfully',
                    'sent_at'         => now(),
                ]);

                $results['sent']++;
                $results['details'][] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customerName,
                    'email' => $customer->email,
                    'status' => 'sent',
                    'bookings_count' => $bookings->count(),
                ];

            } catch (\Exception $e) {
                Log::error('Failed to send booking list email to ' . $customer->email . ': ' . $e->getMessage());
                $results['failed']++;
                $results['details'][] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customerName,
                    'email' => $customer->email,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $totalRequested = count($validated['customer_ids']);
        $message = "Booking list emails completed: {$results['sent']} sent, {$results['failed']} failed, {$results['skipped']} skipped out of {$totalRequested} customers";

        return response()->json([
            'message' => $message,
            'results' => $results,
        ], $results['failed'] > 0 && $results['sent'] === 0 ? 500 : 200);
    }

    private function buildBookingListHtml(Customer $customer, $bookings, string $introMessage): string
    {
        $customerName = trim($customer->first_name . ' ' . $customer->last_name);
        $html = "<p>Dear {$customerName},</p><p>{$introMessage}</p><table border='1' cellpadding='5' cellspacing='0'><thead><tr><th>Date</th><th>Time</th><th>Services</th><th>Status</th></tr></thead><tbody>";

        foreach ($bookings as $booking) {
            $date = $booking->start_date;
            $time = $booking->start_time ?? 'TBD';
            $services = $booking->details->map(fn($d) => $d->service?->name ?? 'Unknown')->implode(', ');
            $status = ucfirst($booking->status);
            $html .= "<tr><td>{$date}</td><td>{$time}</td><td>{$services}</td><td>{$status}</td></tr>";
        }

        $html .= "</tbody></table><p>Thank you for your business!</p>";

        return $html;
    }
}
