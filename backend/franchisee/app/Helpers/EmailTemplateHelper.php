<?php

namespace App\Helpers;

use App\Models\Booking;

class EmailTemplateHelper
{
    public static function generateBookingConfirmation(Booking $booking): string
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $customer = $booking->customer;
        $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        $bookingDate = $booking->start_date ? \Carbon\Carbon::parse($booking->start_date)->format('l, jS F Y') : 'N/A';
        $bookingTime = $booking->start_time ?? 'N/A';
        
        $servicesHtml = '';
        $total = 0;
        foreach ($booking->details ?? [] as $detail) {
            $serviceName = $detail->service->name ?? 'Service';
            $petName = $detail->item->name ?? 'Pet';
            $price = number_format($detail->price ?? 0, 2);
            $total += $detail->price ?? 0;
            $servicesHtml .= "<tr>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>{$petName}</td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>{$serviceName}</td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right;'>\${$price}</td>
            </tr>";
        }
        $totalFormatted = number_format($total, 2);

        return self::wrapInTemplate("Booking Confirmation #{$booking->id}", "
            <p style='margin: 0 0 20px 0; color: #374151; font-size: 16px;'>Dear <strong>{$customerName}</strong>,</p>
            
            <p style='color: #4b5563; font-size: 15px; line-height: 1.7; margin-bottom: 25px;'>
                Thank you for your booking! Here are your booking details:
            </p>

            <div style='background-color: #f9fafb; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
                <table role='presentation' width='100%' cellspacing='0' cellpadding='0'>
                    <tr>
                        <td style='padding: 8px 0;'>
                            <strong style='color: #374151;'>Booking ID:</strong>
                            <span style='color: #6b7280; margin-left: 10px;'>#{$booking->id}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0;'>
                            <strong style='color: #374151;'>Date:</strong>
                            <span style='color: #6b7280; margin-left: 10px;'>{$bookingDate}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0;'>
                            <strong style='color: #374151;'>Time:</strong>
                            <span style='color: #6b7280; margin-left: 10px;'>{$bookingTime}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <h3 style='color: #374151; font-size: 16px; margin: 0 0 15px 0;'>Services</h3>
            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>
                <thead>
                    <tr style='background-color: #f3f4f6;'>
                        <th style='padding: 12px; text-align: left; color: #374151; font-weight: 600;'>Pet</th>
                        <th style='padding: 12px; text-align: left; color: #374151; font-weight: 600;'>Service</th>
                        <th style='padding: 12px; text-align: right; color: #374151; font-weight: 600;'>Price</th>
                    </tr>
                </thead>
                <tbody>
                    {$servicesHtml}
                    <tr style='background-color: #f9fafb;'>
                        <td colspan='2' style='padding: 12px; font-weight: 600; color: #374151;'>Total</td>
                        <td style='padding: 12px; text-align: right; font-weight: 600; color: #374151;'>\${$totalFormatted}</td>
                    </tr>
                </tbody>
            </table>

            <p style='color: #4b5563; font-size: 15px; line-height: 1.7; margin-top: 25px;'>
                If you have any questions or need to make changes, please don't hesitate to contact us.
            </p>
        ");
    }

    public static function generateInvoice(Booking $booking): string
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $customer = $booking->customer;
        $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        
        return self::generateBookingConfirmation($booking);
    }

    public static function generateReceipt(Booking $booking): string
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $customer = $booking->customer;
        $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        $bookingDate = $booking->start_date ? \Carbon\Carbon::parse($booking->start_date)->format('l, jS F Y') : 'N/A';
        
        $servicesHtml = '';
        $total = 0;
        foreach ($booking->details ?? [] as $detail) {
            $serviceName = $detail->service->name ?? 'Service';
            $petName = $detail->item->name ?? 'Pet';
            $price = number_format($detail->price ?? 0, 2);
            $total += $detail->price ?? 0;
            $servicesHtml .= "<tr>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>{$petName}</td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb;'>{$serviceName}</td>
                <td style='padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: right;'>\${$price}</td>
            </tr>";
        }
        $totalFormatted = number_format($total, 2);
        $receiptDate = now()->format('l, jS F Y');

        return self::wrapInTemplate("Receipt for Booking #{$booking->id}", "
            <div style='text-align: center; margin-bottom: 30px;'>
                <div style='display: inline-block; background-color: #10b981; color: white; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 600;'>
                    ✓ PAID
                </div>
            </div>

            <p style='margin: 0 0 20px 0; color: #374151; font-size: 16px;'>Dear <strong>{$customerName}</strong>,</p>
            
            <p style='color: #4b5563; font-size: 15px; line-height: 1.7; margin-bottom: 25px;'>
                Thank you for your payment! Here is your receipt:
            </p>

            <div style='background-color: #f9fafb; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
                <table role='presentation' width='100%' cellspacing='0' cellpadding='0'>
                    <tr>
                        <td style='padding: 8px 0;'>
                            <strong style='color: #374151;'>Receipt #:</strong>
                            <span style='color: #6b7280; margin-left: 10px;'>R-{$booking->id}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0;'>
                            <strong style='color: #374151;'>Service Date:</strong>
                            <span style='color: #6b7280; margin-left: 10px;'>{$bookingDate}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0;'>
                            <strong style='color: #374151;'>Payment Date:</strong>
                            <span style='color: #6b7280; margin-left: 10px;'>{$receiptDate}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <h3 style='color: #374151; font-size: 16px; margin: 0 0 15px 0;'>Services Rendered</h3>
            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;'>
                <thead>
                    <tr style='background-color: #f3f4f6;'>
                        <th style='padding: 12px; text-align: left; color: #374151; font-weight: 600;'>Pet</th>
                        <th style='padding: 12px; text-align: left; color: #374151; font-weight: 600;'>Service</th>
                        <th style='padding: 12px; text-align: right; color: #374151; font-weight: 600;'>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    {$servicesHtml}
                    <tr style='background-color: #10b981; color: white;'>
                        <td colspan='2' style='padding: 12px; font-weight: 600;'>Total Paid</td>
                        <td style='padding: 12px; text-align: right; font-weight: 600;'>\${$totalFormatted}</td>
                    </tr>
                </tbody>
            </table>

            <p style='color: #4b5563; font-size: 15px; line-height: 1.7; margin-top: 25px;'>
                Thank you for choosing our services. We look forward to seeing you again!
            </p>
        ");
    }

    public static function generateSmsConfirmation(Booking $booking): string
    {
        $booking->load(['customer', 'details.service']);
        $bookingDate = $booking->start_date ? \Carbon\Carbon::parse($booking->start_date)->format('D, j M') : 'N/A';
        $bookingTime = $booking->start_time ?? 'N/A';
        
        return "Booking confirmed for {$bookingDate} at {$bookingTime}. Booking #{$booking->id}. Thank you!";
    }

    private static function wrapInTemplate(string $title, string $content): string
    {
        $year = date('Y');
        $currentDate = now()->format('l, jS F Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f6f8; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">{$title}</h1>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            {$content}
                            
                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                <p style="margin: 0; color: #6b7280; font-size: 14px;">Best regards,</p>
                                <p style="margin: 5px 0 0 0; color: #374151; font-size: 14px; font-weight: 600;">The Dog Wash Team</p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 25px 40px; border-top: 1px solid #e5e7eb;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0 0 10px 0; color: #9ca3af; font-size: 12px;">
                                            This email was sent on {$currentDate}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <table role="presentation" width="600" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="padding: 20px; text-align: center;">
                            <p style="margin: 0; color: #9ca3af; font-size: 11px;">
                                © {$year} Dog Wash System. All rights reserved.
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
