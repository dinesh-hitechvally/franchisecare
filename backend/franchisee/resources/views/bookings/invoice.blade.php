<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            color: #333; 
            line-height: 1.5; 
            font-size: 12px;
            padding: 40px;
        }
        .container { 
            max-width: 100%;
            border: 1px solid #e0e0e0;
            padding: 40px;
        }
        .header { 
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .logo-section {
            display: table-cell;
            text-align: right;
            vertical-align: top;
        }
        .logo {
            max-width: 120px;
            max-height: 60px;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .company-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .invoice-details {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        .company-name {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .info-line {
            margin-bottom: 3px;
            color: #555;
        }
        .info-label {
            color: #c41e3a;
        }
        .customer-section {
            margin-bottom: 5px;
        }
        .customer-name {
            font-weight: bold;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        table.items th {
            background: #f8f8f8;
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
            font-size: 12px;
        }
        table.items th:last-child {
            text-align: right;
        }
        table.items td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        table.items td:last-child {
            text-align: right;
        }
        .total-section {
            text-align: right;
            margin: 20px 0;
            padding: 15px 0;
            border-top: 2px solid #ddd;
        }
        .total-row {
            font-size: 14px;
        }
        .total-label {
            display: inline-block;
            margin-right: 30px;
            font-weight: bold;
        }
        .total-value {
            font-weight: bold;
            font-size: 16px;
        }
        .footer-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .support-message {
            color: #c41e3a;
            margin-bottom: 20px;
        }
        .footer-row {
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .footer-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .footer-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Logo -->
        <div class="header">
            <div class="logo-section">
                @if($company?->logo)
                    @php
                        $logoPath = storage_path('app/public/' . $company->logo);
                        if (file_exists($logoPath)) {
                            $logoData = base64_encode(file_get_contents($logoPath));
                            $logoMime = mime_content_type($logoPath);
                        }
                    @endphp
                    @if(isset($logoData))
                        <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" class="logo">
                    @endif
                @endif
            </div>
        </div>

        <!-- Company and Invoice Info -->
        <div class="info-row">
            <div class="company-info">
                <div class="invoice-title">Invoice</div>
                <div class="company-name">{{ $company?->name ?? 'Company Name' }}</div>
                @if($company?->address)
                    <div class="info-line">{{ $company->address }}</div>
                @endif
                @if($company?->city || $company?->state)
                    <div class="info-line">{{ $company?->city }}{{ $company?->city && $company?->state ? ', ' : '' }}{{ $company?->state }}{{ $company?->zip ? ' ' . $company->zip : '' }}</div>
                @endif
                @if($company?->phone)
                    <div class="info-line">Phone: {{ $company->phone }}</div>
                @endif
                @if($company?->email)
                    <div class="info-line">Email: {{ $company->email }}</div>
                @endif
            </div>
            <div class="invoice-details">
                <div class="info-line"><span class="info-label">Date:</span> {{ now()->format('d-m-Y') }}</div>
                <div class="info-line"><span class="info-label">Invoice:</span> #{{ $invoiceNumber ?? $booking->id }}</div>
                <div class="customer-section" style="margin-top: 15px;">
                    <div class="info-line"><span class="info-label">To:</span> <span class="customer-name">{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</span></div>
                    @if($booking->customer->address)
                        <div class="info-line">{{ $booking->customer->address }}</div>
                    @endif
                    @if($booking->customer->suburb || $booking->customer->state || $booking->customer->postcode)
                        <div class="info-line">{{ $booking->customer->suburb }}{{ $booking->customer->suburb && ($booking->customer->state || $booking->customer->postcode) ? ', ' : '' }}{{ $booking->customer->state }}{{ $booking->customer->postcode ? ' ' . $booking->customer->postcode : '' }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Services Table -->
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 30%;">Date/Time</th>
                    <th style="width: 45%;">Service/Product</th>
                    <th style="width: 25%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($booking->details as $detail)
                <tr>
                    <td>{{ $booking->start_date?->format('d-m-Y') }} {{ $booking->start_time }}</td>
                    <td>{{ $detail->item->name ?? 'Pet' }}, {{ $detail->service->name ?? 'Service' }}</td>
                    <td>${{ number_format($detail->price ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Booking Notes -->
        @if($booking->notes)
        <div style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #ddd;">
            <strong style="color: #555;">Notes:</strong>
            <p style="margin: 5px 0 0 0; color: #666;">{{ $booking->notes }}</p>
        </div>
        @endif

        <!-- NDIS Notice -->
        @if($booking->customer->is_ndis)
        <div style="margin: 20px 0; padding: 15px; background: #e8f4fd; border-left: 3px solid #2196F3; border-radius: 4px;">
            <strong style="color: #1565C0;">NDIS Customer</strong>
            @if($booking->customer->notes)
            <p style="margin: 5px 0 0 0; color: #555;">{{ $booking->customer->notes }}</p>
            @endif
        </div>
        @endif

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row">
                <span class="total-label">Total</span>
                <span class="total-value">${{ number_format($booking->total ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <div class="support-message">
                Please call Mate Support at {{ $company?->phone ?? '0000000000' }} if you have any further query on this invoice.
            </div>
            <div class="footer-row">
                <div class="footer-left">
                    <div class="footer-title">Mate Support</div>
                    <div class="info-line">{{ $company?->name ?? 'Company Name' }}</div>
                    <div class="info-line">{{ $company?->phone ?? '' }}</div>
                </div>
                <div class="footer-right">
                    <div class="info-line"><strong>Bank Name:</strong> {{ config('app.bank_name', 'Bank Name') }}</div>
                    <div class="info-line"><strong>BSB:</strong> {{ config('app.bsb', '000-000') }}</div>
                    <div class="info-line"><strong>Account Number:</strong> {{ config('app.account_number', '0000000000') }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
