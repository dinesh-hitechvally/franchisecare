<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.4; font-size: 11px; margin: 0; padding: 0; }
        .container { padding: 30px; border: 1px solid #ddd; margin: 20px; border-radius: 8px; }
        h1 { color: #1e1b4b; text-align: center; font-size: 24px; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; font-size: 12px; }
        .header-section { border-bottom: 2px solid #e0e7ff; padding-bottom: 10px; margin-bottom: 20px; }
        .section-title { background: #f8fafc; color: #1e1b4b; padding: 8px 12px; font-weight: bold; font-size: 13px; border-left: 4px solid #4f46e5; margin: 20px 0 10px 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .grid { width: 100%; margin-bottom: 15px; }
        .grid td { width: 50%; vertical-align: top; padding: 8px 0; }
        .label { color: #94a3b8; font-size: 9px; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .value { color: #334155; font-size: 12px; font-weight: 500; }
        table.items { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.items th { background: #f8fafc; color: #1e1b4b; padding: 10px; text-align: left; font-size: 11px; font-weight: bold; border-bottom: 2px solid #e0e7ff; }
        table.items td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        .total-section { margin-top: 30px; text-align: right; }
        .total-row { display: flex; justify-content: flex-end; margin-bottom: 5px; }
        .total-label { margin-right: 20px; color: #666; }
        .total-value { font-weight: bold; color: #1e1b4b; }
        .grand-total { font-size: 16px; color: #4f46e5; border-top: 2px solid #e0e7ff; padding-top: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>INVOICE</h1>
        <div class="subtitle">Invoice #{{ $booking->id }}</div>

        <div class="header-section">
            <table class="grid">
                <tr>
                    <td><span class="label">Invoice Date</span><span class="value">{{ $booking->date }}</span></td>
                    <td><span class="label">Booking Time</span><span class="value">{{ $booking->start_time }}</span></td>
                </tr>
                <tr>
                    <td><span class="label">Customer</span><span class="value">{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</span></td>
                    <td><span class="label">Status</span><span class="value">{{ ucfirst($booking->status) }}</span></td>
                </tr>
            </table>
        </div>

        <div class="section-title">Services</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Pet</th>
                    <th style="text-align: right;">Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($booking->details as $detail)
                <tr>
                    <td>{{ $detail->service->name }}</td>
                    <td>{{ $detail->pet->name }}</td>
                    <td style="text-align: right;">${{ number_format($detail->price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="total-value">${{ number_format($booking->total, 2) }}</span>
            </div>
            <div class="total-row grand-total">
                <span class="total-label">Total:</span>
                <span class="total-value">${{ number_format($booking->total, 2) }}</span>
            </div>
        </div>

        @if($booking->notes)
        <div class="section-title">Notes</div>
        <p>{{ $booking->notes }}</p>
        @endif
    </div>
</body>
</html>
