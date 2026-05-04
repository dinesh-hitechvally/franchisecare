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
        .warning-box { border: 1px solid #fbbf24; padding: 10px; margin: 10px 0; border-radius: 2px; background: white; }
        
        ul { padding-left: 20px; margin: 10px 0; }
        li { margin-bottom: 8px; line-height: 1.5; }
        .bold { font-weight: bold; }

        .signature-section { margin-top: 40px; }
        .signature-box { border: 1px solid #cbd5e1; border-radius: 2px; padding: 10px; min-height: 120px; margin-top: -6px; margin-bottom: 20px; }
        .signature-img { max-width: 300px; height: auto; background: #fff; }
        .date-line { border-bottom: 1px dotted #94a3b8; display: inline-block; width: 100%; margin-top: 2px; }
        .date-text { color: #94a3b8; font-size: 8px; font-weight: bold; }
        .row-group { margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Matted Dog Waiver</h1>

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
            <p style="margin-top: 0; font-weight: bold;">Dear Blue Wheelers / Dash Franchisee,</p>
            <p>I, {{ $ownerName }}, acknowledge that I have been well advised that my dog's coat is severely matted, that matted coats can cause a variety of skin & health problems.</p>
            
            <div class="warning-box">
                Matted fur does not allow for air circulation to the skin, causing hot spots, bacterial and fungal infections. Fleas, ticks, maggots, and other parasites may be lurking in the coat, causing further skin infections.
            </div>
            
            <div class="warning-box">
                Matted fur pulls and binds, restricting blood flow and causing pain to your pet when they move or lay on mats. The skin underneath is usually raw and inflamed. Matted coats will not dry properly and can lead to rotting fur and skin.
            </div>

            <div class="warning-box">
                The matted hair rests tightly against the skin &ndash; the only way of removing mats is to use a short blade to clip between the skin and mats.
            </div>

            <ul>
                <li>Your groomer will show all care to avoid injury, however as we need to work so closely to the skin to remove the matted coat, your dog may be nicked. The chances of injury are increased when a pet's coat is severely matted.</li>
                <li>Shaving may cause irritation and/or rash.</li>
                <li>Your dog may show signs of skin irritation and sores due to the matting, wet undercoat and dirty coat.</li>
                <li>As de-matting pets is often too stressful for the animal and too dangerous and harmful to the skin, we will not attempt to remove severe matting by combing or brushing.</li>
                <li>If your dog's ears are badly matted, the chance of them experiencing hematomas once the hair is removed/shaved (due to circulation being cut off by the mats), is increased. Hematomas are exhibited by blood pooling at the ends of the ear leather. This can occur hours after the initial groom and can be triggered through the shaking of the ears. <span class="bold">If your pet shows prolonged, excessive head shaking after the groom, consider vet treatment to avoid any further issues caused from the shaking.</span></li>
            </ul>

            <p style="margin-bottom: 0;">I accept full responsibility for my dog's health, I understand the risks and information above and authorize the provision of services required to remove the coat in its entirety.</p>
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
