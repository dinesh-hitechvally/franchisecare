<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.4; font-size: 11px; margin: 0; padding: 0; }
        .container { padding: 30px; border: 1px solid #ddd; margin: 20px; border-radius: 8px; }
        h1 { color: #3b82f6; text-align: center; font-size: 20px; margin-bottom: 30px; font-weight: normal; }
        .section-title { color: #3b82f6; padding-bottom: 5px; font-size: 14px; border-bottom: 1px solid #3b82f6; margin: 20px 0 10px 0; font-weight: normal; }
        .input-box { border: 1px solid #cbd5e1; padding: 8px; border-radius: 2px; margin-bottom: 10px; color: #334155; font-size: 12px; }
        .label { color: #94a3b8; font-size: 9px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; display: inline-block; background: white; padding: 0 5px; position: relative; top: 6px; left: 10px; z-index: 10; }
        .info-box { background: #f8fafc; padding: 15px; border: 1px solid #e2e8f0; border-radius: 4px; margin: 20px 0; font-size: 11px; line-height: 1.5; color: #334155; }
        .text-area { border: 1px solid #cbd5e1; padding: 10px; border-radius: 2px; min-height: 80px; color: #334155; font-size: 12px; margin-bottom: 10px; margin-top: -6px; }
        .signature-section { margin-top: 40px; }
        .signature-box { border: 1px solid #cbd5e1; border-radius: 2px; padding: 10px; min-height: 120px; margin-top: -6px; margin-bottom: 20px; }
        .signature-img { max-width: 300px; height: auto; background: #fff; }
        .disclaimer { font-size: 10px; color: #475569; margin: 15px 0; }
        .date-line { border-bottom: 1px dotted #94a3b8; display: inline-block; width: 100%; margin-top: 2px; }
        .date-text { color: #94a3b8; font-size: 8px; font-weight: bold; }
        .row-group { margin-bottom: 5px; }
        .control-group { position: relative; margin-top: -6px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cologne Waiver</h1>

        <div class="section-title">Customer Information</div>
        <div class="row-group">
            <span class="label">Franchisee Name *</span>
            <div class="input-box">{{ $franchiseeName }}</div>
        </div>
        <div class="row-group">
            <span class="label">Customer Name *</span>
            <div class="input-box">{{ $ownerName }}</div>
        </div>
        <div class="row-group">
            <span class="label">Customer Address</span>
            <div class="input-box">{{ $customerAddress }}</div>
        </div>

        <div class="info-box">
            <p style="margin-top: 0;">Dear Blue Wheelers / Dash DogWash Franchisee,</p>
            <p style="margin-bottom: 0;">I, {{ $ownerName }}, acknowledge that although the cologne is part of the Blue Wheelers and Dash DogWash service and I have been told that it will make my dog smell good for up to two weeks, I choose not to have it used on my dog for the following reasons:</p>
        </div>

        <div class="section-title">Reason for Declining Cologne Service</div>
        <span class="label">Reason(s) for declining *</span>
        <div class="text-area">
            {{ $form_data['cologneReason'] ?? '' }}
        </div>

        <div class="disclaimer">
            I understand that this is my choice and the franchisee has explained the disadvantages in not using the cologne.
        </div>

        <div class="section-title">Signature and Date</div>
        <span class="label">Please draw your signature below</span>
        <div class="signature-box">
            <img src="{{ public_path($signature_url) }}" class="signature-img">
        </div>
        
        <div style="width: 50%;">
            <span class="label" style="top: 2px; left: 0; background: none;">Date</span>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 5px;">{{ \Carbon\Carbon::now()->format('jS F, Y') }}</div>
            <div class="date-line"></div>
            <div class="date-text">Signature date</div>
        </div>
    </div>
</body>
</html>
