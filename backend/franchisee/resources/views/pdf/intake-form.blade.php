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
        .checkbox-row { padding: 5px 0; border-bottom: 1px solid #f1f5f9; }
        .checkbox-row:last-child { border-bottom: none; }
        .check-box { display: inline-block; width: 12px; height: 12px; border: 1px solid #cbd5e1; vertical-align: middle; margin-right: 8px; text-align: center; line-height: 12px; font-size: 10px; }
        .check-box.checked { background-color: #4f46e5; border-color: #4f46e5; color: white; }
        .radio-inline { margin-right: 20px; display: inline-block; }
        .notes-box { background: #f8fafc; padding: 10px; border: 1px solid #e2e8f0; border-radius: 4px; margin-top: 5px; min-height: 40px; }
        .expectations { background: #fefce8; padding: 15px; border: 1px solid #fef08a; border-radius: 6px; margin-top: 30px; font-style: italic; }
        .signature-section { margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .signature-img { max-width: 300px; height: auto; border: 1px solid #e2e8f0; margin: 10px 0; background: #fff; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="container">
        <h1>New Customer Intake Form</h1>
        <div class="subtitle">Completed by franchisee for pet's grooming safety and profile management.</div>

        <div class="section-title">Customer & Pet Information</div>
        <table class="grid">
            <tr>
                <td><span class="label">Owner Name</span><span class="value">{{ $ownerName }}</span></td>
                <td><span class="label">Pet Name</span><span class="value">{{ $petName }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Phone / Email</span><span class="value">{{ $phone }} / {{ $email }}</span></td>
                <td><span class="label">Breed</span><span class="value">{{ $breed }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Age</span><span class="value">{{ $form_data['age'] }}</span></td>
                <td><span class="label">Weight (approx)</span><span class="value">{{ $form_data['weight'] }}</span></td>
            </tr>
        </table>

        <div class="section-title">Health History</div>
        <div class="checkbox-row">
            <span class="check-box {{ $form_data['isVaccinated'] ? 'checked' : '' }}">{{ $form_data['isVaccinated'] ? '✓' : '' }}</span>
            <span class="value">Pet is fully vaccinated</span>
        </div>
        <div style="margin-top: 10px;">
            <p style="font-size: 10px; color: #666; margin-bottom: 5px;">Pre-existing conditions:</p>
            <table width="100%">
                <tr>
                    <td width="50%">
                        <span class="check-box {{ $form_data['healthConditions']['arthritis'] ? 'checked' : '' }}">{{ $form_data['healthConditions']['arthritis'] ? '✓' : '' }}</span> Arthritis<br><br>
                        <span class="check-box {{ $form_data['healthConditions']['collapsingTrachea'] ? 'checked' : '' }}">{{ $form_data['healthConditions']['collapsingTrachea'] ? '✓' : '' }}</span> Collapsing Trachea<br><br>
                        <span class="check-box {{ $form_data['healthConditions']['diabetes'] ? 'checked' : '' }}">{{ $form_data['healthConditions']['diabetes'] ? '✓' : '' }}</span> Diabetes<br><br>
                        <span class="check-box {{ $form_data['healthConditions']['chronicEarIssues'] ? 'checked' : '' }}">{{ $form_data['healthConditions']['chronicEarIssues'] ? '✓' : '' }}</span> Chronic Ear Issues
                    </td>
                    <td>
                        <span class="check-box {{ $form_data['healthConditions']['epilepsy'] ? 'checked' : '' }}">{{ $form_data['healthConditions']['epilepsy'] ? '✓' : '' }}</span> Epilepsy<br><br>
                        <span class="check-box {{ $form_data['healthConditions']['heartDisease'] ? 'checked' : '' }}">{{ $form_data['healthConditions']['heartDisease'] ? '✓' : '' }}</span> Heart Disease<br><br>
                        <span class="check-box {{ $form_data['healthConditions']['chronicSkinIssues'] ? 'checked' : '' }}">{{ $form_data['healthConditions']['chronicSkinIssues'] ? '✓' : '' }}</span> Chronic Skin Issues<br><br>
                        <span class="check-box {{ $form_data['healthConditions']['allergies'] ? 'checked' : '' }}">{{ $form_data['healthConditions']['allergies'] ? '✓' : '' }}</span> Allergies
                    </td>
                </tr>
            </table>
        </div>
        @if(!empty($form_data['otherHealth']))
        <div class="label" style="margin-top: 10px;">Other Health Notes</div>
        <div class="notes-box">{{ $form_data['otherHealth'] }}</div>
        @endif

        <div class="section-title">Skin & Sensitivities</div>
        <p>Sensitive skin? <strong>{{ strtoupper($form_data['skinSensitivities']['sensitiveSkin']) }}</strong></p>
        <p>Product allergies? <strong>{{ strtoupper($form_data['skinSensitivities']['productAllergies']) }}</strong></p>
        <p>Vet wash advice? <strong>{{ strtoupper($form_data['skinSensitivities']['veterinaryAdvice']) }}</strong></p>

        <div class="section-title">Behavioural Assessment</div>
        <table width="100%">
            <tr>
                <td width="25%"><span class="check-box {{ $form_data['behavioural']['fearful'] ? 'checked' : '' }}">{{ $form_data['behavioural']['fearful'] ? '✓' : '' }}</span> Fearful</td>
                <td width="25%"><span class="check-box {{ $form_data['behavioural']['aggressive'] ? 'checked' : '' }}">{{ $form_data['behavioural']['aggressive'] ? '✓' : '' }}</span> Aggressive</td>
                <td width="25%"><span class="check-box {{ $form_data['behavioural']['anxious'] ? 'checked' : '' }}">{{ $form_data['behavioural']['anxious'] ? '✓' : '' }}</span> Anxious</td>
                <td width="25%"><span class="check-box {{ $form_data['behavioural']['noneKnown'] ? 'checked' : '' }}">{{ $form_data['behavioural']['noneKnown'] ? '✓' : '' }}</span> None known</td>
            </tr>
        </table>

        <div class="expectations">
            "Overall your pet's health and comfort is at the heart of everything we do. We will make every effort to provide you with the best service, however the dog's welfare always comes first. A groom may be paused or stopped at any point for health or behavioural reasons."
        </div>

        <div class="signature-section">
            <span class="label">Signature</span>
            <img src="{{ public_path($signature_url) }}" class="signature-img">
            <p class="value" style="font-size: 10px;">Date: {{ \Carbon\Carbon::now()->format('d F Y') }}</p>
        </div>
    </div>
</body>
</html>
