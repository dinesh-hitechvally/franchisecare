<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.4; font-size: 11px; margin: 0; padding: 0; }
        .container { padding: 30px; border: 1px solid #ddd; margin: 20px; border-radius: 8px; }
        h1 { color: #3b82f6; text-align: center; font-size: 20px; margin-bottom: 30px; font-weight: normal; }
        .section-title { color: #3b82f6; padding-bottom: 5px; font-size: 14px; border-bottom: 1px solid #3b82f6; margin: 20px 0 10px 0; font-weight: normal; }
        
        .grid-2 { width: 100%; margin-bottom: 5px; }
        .grid-2 td { vertical-align: top; }
        .grid-2 td:first-child { padding-right: 5px; width: 50%; }
        .grid-2 td:last-child { padding-left: 5px; width: 50%; }

        .input-box { border: 1px solid #cbd5e1; padding: 8px; border-radius: 2px; margin-bottom: 10px; color: #334155; font-size: 12px; }
        .label { color: #94a3b8; font-size: 9px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; display: inline-block; background: white; padding: 0 5px; position: relative; top: 6px; left: 10px; z-index: 10; }
        
        .info-container { background: #f8fafc; border-left: 3px solid #3b82f6; padding: 15px; margin: 20px 0; font-size: 11px; color: #334155; }
        .warning-box { border: 1px solid #fbbf24; padding: 10px; margin: 10px 0; border-radius: 2px; background: white; font-style: italic; }
        
        ul { padding-left: 20px; margin: 10px 0; }
        li { margin-bottom: 8px; line-height: 1.5; }
        .bold { font-weight: bold; }

        .signature-section { margin-top: 40px; }
        .signature-box { border: 1px solid #cbd5e1; border-radius: 2px; padding: 10px; min-height: 120px; margin-top: -6px; margin-bottom: 20px; }
        .signature-img { max-width: 300px; height: auto; background: #fff; }
        .date-line { border-bottom: 1px dotted #94a3b8; display: inline-block; width: 100%; margin-top: 2px; }
        .date-text { color: #94a3b8; font-size: 8px; font-weight: bold; }
        .row-group { margin-bottom: 5px; }
        .center-heading { text-align: center; text-transform: uppercase; font-weight: bold; text-decoration: underline; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Double Coated Dog Waiver</h1>

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
        
        <table class="grid-2">
            <tr>
                <td>
                    <div class="row-group">
                        <span class="label">Dog's Name</span>
                        <div class="input-box">{{ $petName }}</div>
                    </div>
                </td>
                <td>
                    <div class="row-group">
                        <span class="label">Dog's Breed</span>
                        <div class="input-box">{{ $breed }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="info-container">
            <div class="center-heading">WAIVER TO CLIP MY DOUBLE COATED DOG/S</div>
            
            <p style="margin-top: 0;">Dear Blue Wheelers / Dash Franchisee,</p>
            <p>I, {{ $ownerName }}, acknowledge that I have been well advised not to clip my double coated dog.</p>
            
            <div class="warning-box">
                • We cannot guarantee that the dog's hair will grow back after shaving
            </div>
            <div class="warning-box">
                • Shaving may cause irritation and/or rash
            </div>
            <div class="warning-box">
                • Shaving the coat does not necessarily make the dog feel cooler
            </div>
            <div class="warning-box">
                • Shaving does not reduce shedding; it only makes the shedding coat shorter
            </div>

            <p class="bold" style="margin-top: 15px; margin-bottom: 15px;">Despite this, I still choose to continue with my requested service and have my dog clipped.</p>
            
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%;" class="bold">Dog/s name: <span style="font-weight: normal;">{{ $petName }}</span></td>
                    <td style="width: 50%;" class="bold">Dog/s breed: <span style="font-weight: normal;">{{ $breed !== 'Not specified' ? '[' . $breed . ']' : '[Dog\'s Breed]' }}</span></td>
                </tr>
            </table>
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
