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
        .warning-box { border: 1px solid #fbbf24; padding: 10px; margin: 10px 0; border-radius: 2px; background: white; font-style: italic;}
        
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
        <h1>Senior Dog Waiver</h1>

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

        <div class="row-group">
            <span class="label">Dog's Age *</span>
            <div class="input-box">{{ $form_data['age'] ?? '' }}</div>
        </div>

        <div class="info-container">
            <div class="center-heading">SENIOR WAIVER (for pets over 7 years old)</div>
            
            <p style="margin-top: 0;">Dear Blue Wheelers / Dash Franchisee,</p>
            <p>I, {{ $ownerName }}, acknowledge that I have been well advised of the risks to grooming a senior dog.</p>
            <p>I understand that:</p>
            
            <div class="warning-box">
                • Standing for long periods of time may be difficult for a senior pet, so they may (in most cases) require a longer appointment.
            </div>
            <div class="warning-box">
                • As pets age their coat / fur can change, thus there is no guarantee that a groom on a senior pet will result in the desired style.
            </div>
            <div class="warning-box">
                • Older pets are prone to a variety of lumps and bumps. Please let us know to the best of your abilities where these are on your pets' body. Fatty tumors, cysts, warts and other miscellaneous skin lesions/bumps can cause an uneven haircut at times and can be easily nicked, resulting in injury
            </div>
            <div class="warning-box">
                • Skin becomes thin and loose as pets age, which can be problematic when grooming. While we use extreme caution and care in all situations, possible problems could occur including cuts, nicks, & scratches. In the event an accident does occur you will be notified immediately
            </div>
            <div class="warning-box">
                • Nails can become thick and brittle as pets age, thus trimming nails can result in split and/or broken nails.
            </div>
            <div class="warning-box">
                • Lungs lose their elasticity during the aging process and the ability of the lungs to oxygenate the blood may be decreased. In the event that your groomer suspects that your dog is experiencing respiratory stress or infection, they may refuse services with your pets' best interest in mind.
            </div>
            <div class="warning-box">
                • As pets age their abilities to withhold bowels and urine can decrease. This can make the grooming process challenging. We will wash your dog to the best of our abilities, but will not repeatedly wash them and put them under any unnecessary stress.
            </div>
            <div class="warning-box">
                • Having a senior dog groomed by somebody new in a new environment may be stressful for them; particularly if they are very elderly or have health complications.
            </div>
            <div class="warning-box">
                • While Blue Wheelers / Dash DogWash practice quality care, there is a possibility for a senior pet to have seizures or heart attacks. In the event of a medical emergency of this nature you will be contacted immediately
            </div>
            <div class="warning-box">
                • If your pet has an issue that we feel needs urgent medical treatment, the groom will be stopped and we will advise you to take your dog to your regular veterinarian.
            </div>

            <p class="bold" style="margin-top: 15px;">I accept full responsibility for the health of my dog, understand the information above and choose to continue with my requested service and have my dog groomed.</p>
            
            <p>I have advised the groomer of any medical or health issues with my dog.</p>
            <p>I accept that if the groomer notices any form of stress, they may decide to stop the service at any time; in the best interests of my pet.</p>

            <table style="width: 100%; margin-top: 15px;">
                <tr>
                    <td style="width: 33%;" class="bold">Dog/s name: <span style="font-weight: normal;">{{ $petName }}</span></td>
                    <td style="width: 33%; text-align: center;" class="bold">Dog/s breed: <span style="font-weight: normal;">{{ $breed !== 'Not specified' ? '[' . $breed . ']' : '[Dog\'s Breed]' }}</span></td>
                    <td style="width: 34%; text-align: right;" class="bold">Dog's age: <span style="font-weight: normal;">{{ isset($form_data['age']) && $form_data['age'] !== '' ? '[' . $form_data['age'] . ']' : '[Dog\'s Age]' }}</span></td>
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
