<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    // ─── SMS History ────────────────────────────────────────────────────────────

    public function smsIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 25);
        $status  = $request->input('status'); // 'sent' or 'queued'

        $query = SmsHistory::query()->orderByDesc('created_at');

        if (in_array($status, ['sent', 'queued'], true)) {
            $query->where('status', $status);
        }

        $data = $query->paginate($perPage);

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
        $validated['status']     = $validated['status'] ?? 'queued';

        $record = SmsHistory::create($validated);

        return response()->json($record, 201);
    }

    public function smsShow(SmsHistory $smsHistory): JsonResponse
    {
        return response()->json($smsHistory);
    }

    public function smsDestroy(SmsHistory $smsHistory): JsonResponse
    {
        $smsHistory->delete();

        return response()->json(null, 204);
    }

    // ─── Email History ───────────────────────────────────────────────────────────

    public function emailIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 25);
        $status  = $request->input('status'); // 'sent' or 'queued'

        $query = EmailHistory::query()->orderByDesc('created_at');

        if (in_array($status, ['sent', 'queued'], true)) {
            $query->where('status', $status);
        }

        $data = $query->paginate($perPage);

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
        $validated['status']     = $validated['status'] ?? 'queued';

        $record = EmailHistory::create($validated);

        return response()->json($record, 201);
    }

    public function emailShow(EmailHistory $emailHistory): JsonResponse
    {
        return response()->json($emailHistory);
    }

    public function emailDestroy(EmailHistory $emailHistory): JsonResponse
    {
        $emailHistory->delete();

        return response()->json(null, 204);
    }

    // ─── Send Email (Actually delivers the email) ────────────────────────────────

    /**
     * Send a single email to a customer
     */
    public function sendEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_email'   => 'required|email|max:255',
            'subject'    => 'required|string|max:500',
            'body'       => 'required|string',
            'from_name'  => 'nullable|string|max:255',
        ]);

        $fromEmail = $request->user()?->email ?? config('mail.from.address', 'no-reply@example.com');
        $fromName = $validated['from_name'] ?? $request->user()?->name ?? config('mail.from.name', 'System');

        try {
            Mail::html($validated['body'], function ($message) use ($validated, $fromEmail, $fromName) {
                $message->to($validated['to_email'])
                        ->from($fromEmail, $fromName)
                        ->subject($validated['subject']);
            });

            $record = EmailHistory::create([
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

            $record = EmailHistory::create([
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

    /**
     * Send bulk generic email to multiple customers
     */
    public function sendBulkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id',
            'subject'      => 'required|string|max:500',
            'body'         => 'required|string',
            'from_name'    => 'nullable|string|max:255',
        ]);

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

            // Personalize the email body with customer name
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

                EmailHistory::create([
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

                EmailHistory::create([
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

    /**
     * Send booking list email to customers
     */
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

            // Get bookings for this customer
            $bookingsQuery = Booking::where('customer_id', $customer->id)
                ->with(['details.service', 'details.item'])
                ->orderBy('start_date', 'desc');

            if (!empty($validated['date_from'])) {
                $bookingsQuery->where('start_date', '>=', $validated['date_from']);
            }
            if (!empty($validated['date_to'])) {
                $bookingsQuery->where('start_date', '<=', $validated['date_to']);
            }

            // Filter by status
            $statuses = ['active'];
            if (!empty($validated['include_completed'])) {
                $statuses[] = 'completed';
            }
            if (!empty($validated['include_cancelled'])) {
                $statuses[] = 'cancelled';
            }
            $bookingsQuery->whereIn('status', $statuses);

            $bookings = $bookingsQuery->get();

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

            // Generate HTML email with booking list
            $customerName = trim($customer->first_name . ' ' . $customer->last_name);
            $emailBody = $this->generateBookingListHtml($customer, $bookings, $introMessage);

            try {
                Mail::html($emailBody, function ($message) use ($customer, $subject, $fromEmail, $fromName) {
                    $message->to($customer->email)
                            ->from($fromEmail, $fromName)
                            ->subject($subject);
                });

                EmailHistory::create([
                    'company_id'      => $request->user()?->company_id,
                    'user_id'         => $request->user()?->id,
                    'from_email'      => $fromEmail,
                    'to_email'        => $customer->email,
                    'subject'         => $subject,
                    'body'            => $emailBody,
                    'status'          => 'sent',
                    'mailer_response' => 'Booking list email sent successfully',
                    'sent_at'         => now(),
                ]);

                $results['sent']++;
                $results['details'][] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customerName,
                    'email' => $customer->email,
                    'bookings_count' => $bookings->count(),
                    'status' => 'sent',
                ];

            } catch (\Exception $e) {
                Log::error('Failed to send booking list to ' . $customer->email . ': ' . $e->getMessage());

                EmailHistory::create([
                    'company_id'      => $request->user()?->company_id,
                    'user_id'         => $request->user()?->id,
                    'from_email'      => $fromEmail,
                    'to_email'        => $customer->email,
                    'subject'         => $subject,
                    'body'            => $emailBody,
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
        $message = "Booking list emails completed: {$results['sent']} sent, {$results['failed']} failed, {$results['skipped']} skipped out of {$totalRequested} customers";

        return response()->json([
            'message' => $message,
            'results' => $results,
        ], $results['failed'] > 0 && $results['sent'] === 0 ? 500 : 200);
    }

    /**
     * Generate HTML email body with booking list
     */
    private function generateBookingListHtml(Customer $customer, $bookings, string $introMessage): string
    {
        $customerName = trim($customer->first_name . ' ' . $customer->last_name);
        $currentDate = now()->format('F j, Y');

        $bookingRows = '';
        foreach ($bookings as $booking) {
            $services = $booking->details->map(fn($d) => $d->service?->name)->filter()->implode(', ') ?: 'N/A';
            $pets = $booking->details->map(fn($d) => $d->item?->name)->filter()->unique()->implode(', ') ?: 'N/A';
            $statusColor = match($booking->status) {
                'active' => '#10b981',
                'completed' => '#3b82f6',
                'cancelled' => '#ef4444',
                default => '#6b7280'
            };
            $date = \Carbon\Carbon::parse($booking->start_date)->format('M j, Y');
            $time = $booking->start_time ? \Carbon\Carbon::parse($booking->start_time)->format('g:i A') : 'N/A';
            $total = '$' . number_format($booking->total ?? 0, 2);

            $bookingRows .= "
                <tr>
                    <td style=\"padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #374151;\">{$date}</td>
                    <td style=\"padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #374151;\">{$time}</td>
                    <td style=\"padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #374151;\">{$pets}</td>
                    <td style=\"padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #374151;\">{$services}</td>
                    <td style=\"padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-size: 14px; color: #374151;\">{$total}</td>
                    <td style=\"padding: 12px 16px; border-bottom: 1px solid #e5e7eb;\">
                        <span style=\"display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; color: white; background-color: {$statusColor};\">" . ucfirst($booking->status) . "</span>
                    </td>
                </tr>
            ";
        }

        $totalAmount = '$' . number_format($bookings->sum('total'), 2);
        $bookingsCount = $bookings->count();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Booking Summary</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f6f8; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="700" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">Your Booking Summary</h1>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px 0; color: #374151; font-size: 16px;">Dear <strong>{$customerName}</strong>,</p>
                            
                            <p style="margin: 0 0 30px 0; color: #4b5563; font-size: 15px; line-height: 1.7;">{$introMessage}</p>
                            
                            <!-- Bookings Table -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                                <thead>
                                    <tr style="background-color: #f9fafb;">
                                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Date</th>
                                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Time</th>
                                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Pet(s)</th>
                                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Services</th>
                                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Total</th>
                                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$bookingRows}
                                </tbody>
                            </table>
                            
                            <!-- Summary -->
                            <div style="margin-top: 20px; padding: 16px; background-color: #f0f9ff; border-radius: 8px; border: 1px solid #bae6fd;">
                                <p style="margin: 0; font-size: 14px; color: #0369a1;">
                                    <strong>Total Bookings:</strong> {$bookingsCount} | <strong>Total Amount:</strong> {$totalAmount}
                                </p>
                            </div>
                            
                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                <p style="margin: 0; color: #6b7280; font-size: 14px;">Best regards,</p>
                                <p style="margin: 5px 0 0 0; color: #374151; font-size: 14px; font-weight: 600;">The Team</p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 25px 40px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; text-align: center; color: #9ca3af; font-size: 12px;">
                                This email was sent on {$currentDate}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
