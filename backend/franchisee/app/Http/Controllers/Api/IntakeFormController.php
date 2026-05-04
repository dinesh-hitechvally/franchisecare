<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerItemWaiver;
use App\Models\CustomerItemWaiverAudit;
use App\Models\CustomerItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class IntakeFormController extends Controller
{
    public function getByPet(CustomerItem $pet)
    {
        $waivers = CustomerItemWaiver::where('item_id', $pet->id)
            ->latest()
            ->get()
            ->groupBy('waiver_type')
            ->map(function ($group) {
                return $group->first();
            });

        return response()->json($waivers);
    }

    public function show(CustomerItemWaiver $waiver)
    {
        return response()->json($waiver);
    }

    public function getHistory(CustomerItem $pet, string $type)
    {
        $history = CustomerItemWaiverAudit::where('item_id', $pet->id)
            ->where('waiver_type', $type)
            ->latest()
            ->get();

        return response()->json($history);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'item_id' => 'required|exists:customer_items,id',
            'waiver_type' => 'required|string',
            'ownerName' => 'required|string',
            'petName' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string',
            'breed' => 'nullable|string',
            'form_data' => 'nullable|array',
            'signature' => 'required|string', // base64 string
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Save Signature
            $signatureData = $request->signature;
            $image = str_replace('data:image/png;base64,', '', $signatureData);
            $image = str_replace(' ', '+', $image);
            $imageName = 'sig_' . time() . '_' . Str::random(10) . '.png';
            
            Storage::disk('public')->put('signatures/' . $imageName, base64_decode($image));
            $signaturePath = 'storage/signatures/' . $imageName;

            // 2. Map Form Data to Columns (if Intake)
            $data = [
                'customer_id' => $request->customer_id,
                'item_id' => $request->item_id,
                'waiver_type' => $request->waiver_type,
                'signature_path' => $signaturePath,
            ];

            if ($request->has('form_data')) {
                $fd = $request->form_data;
                if ($request->waiver_type === 'intake') {
                    $data = array_merge($data, [
                        'age' => $fd['age'] ?? null,
                        'weight' => $fd['weight'] ?? null,
                        'is_vaccinated' => $fd['isVaccinated'] ?? false,
                        'health_arthritis' => $fd['healthConditions']['arthritis'] ?? false,
                        'health_epilepsy' => $fd['healthConditions']['epilepsy'] ?? false,
                        'health_collapsing_trachea' => $fd['healthConditions']['collapsingTrachea'] ?? false,
                        'health_heart_disease' => $fd['healthConditions']['heartDisease'] ?? false,
                        'health_diabetes' => $fd['healthConditions']['diabetes'] ?? false,
                        'health_chronic_skin' => $fd['healthConditions']['chronicSkinIssues'] ?? false,
                        'health_chronic_ear' => $fd['healthConditions']['chronicEarIssues'] ?? false,
                        'health_allergies' => $fd['healthConditions']['allergies'] ?? false,
                        'other_health' => $fd['otherHealth'] ?? null,
                        'sensitivity_skin' => $fd['skinSensitivities']['sensitiveSkin'] ?? 'no',
                        'sensitivity_products' => $fd['skinSensitivities']['productAllergies'] ?? 'no',
                        'sensitivity_vet_advice' => $fd['skinSensitivities']['veterinaryAdvice'] ?? 'no',
                        'behavioural_fearful' => $fd['behavioural']['fearful'] ?? false,
                        'behavioural_aggressive' => $fd['behavioural']['aggressive'] ?? false,
                        'behavioural_anxious' => $fd['behavioural']['anxious'] ?? false,
                        'behavioural_none_known' => $fd['behavioural']['noneKnown'] ?? false,
                        'behavioural_others' => $fd['behaviouralOther'] ?? null,
                        'dislike_head' => $fd['dislikeTouched']['head'] ?? false,
                        'dislike_paws' => $fd['dislikeTouched']['paws'] ?? false,
                        'dislike_tail' => $fd['dislikeTouched']['tail'] ?? false,
                        'dislike_other' => $fd['dislikeTouched']['other'] ?? false,
                        'grooming_prof_groomed' => $fd['groomingExperience']['professionallyGroomed'] ?? 'no',
                        'grooming_prev_issues' => $fd['groomingExperience']['previousIssues'] ?? 'no',
                        'tick_prevention' => $fd['tickPrevention'] ?? 'no',
                        'accepted_expectations' => $fd['acceptedExpectations'] ?? false,
                    ]);
                } elseif ($request->waiver_type === 'cologne') {
                    $data = array_merge($data, [
                        'cologne_decline_reason' => $fd['cologneReason'] ?? null,
                        'accepted_expectations' => $fd['acceptedExpectations'] ?? false,
                    ]);
                } elseif ($request->waiver_type === 'shampoo') {
                    $data = array_merge($data, [
                        'shampoo_own_reason' => $fd['shampooReason'] ?? null,
                        'accepted_expectations' => $fd['acceptedExpectations'] ?? false,
                    ]);
                } elseif ($request->waiver_type === 'pregnant') {
                    $data = array_merge($data, [
                        'accepted_expectations' => true,
                        'delivery_date' => $fd['deliveryDate'] ?? null,
                    ]);
                } elseif ($request->waiver_type === 'senior') {
                    $data = array_merge($data, [
                        'accepted_expectations' => true,
                        'age' => $fd['age'] ?? null,
                    ]);
                } elseif (in_array($request->waiver_type, ['matted', 'clipping'])) {
                    $data = array_merge($data, [
                        'accepted_expectations' => true,
                    ]);
                }
            }

            // 3. Prepare Data for PDF
            $pdfData = [
                'ownerName' => $request->ownerName,
                'petName' => $request->petName,
                'phone' => $request->phone,
                'email' => $request->email,
                'breed' => $request->breed ?: 'Not specified',
                'form_data' => $request->form_data, // Use raw for blade
                'signature_url' => $signaturePath,
                'franchiseeName' => $request->form_data['franchiseeName'] ?? 'Mate Support',
                'customerAddress' => $request->form_data['customerAddress'] ?? 'Not specified',
            ];

            // 4. Generate PDF
            $view = 'pdf.generic-waiver';
            if ($request->waiver_type === 'intake') {
                $view = 'pdf.intake-form';
            } elseif ($request->waiver_type === 'cologne') {
                $view = 'pdf.cologne-waiver';
            } elseif ($request->waiver_type === 'shampoo') {
                $view = 'pdf.shampoo-waiver';
            } elseif ($request->waiver_type === 'matted') {
                $view = 'pdf.matted-waiver';
            } elseif ($request->waiver_type === 'clipping') {
                $view = 'pdf.clipping-waiver';
            } elseif ($request->waiver_type === 'pregnant') {
                $view = 'pdf.pregnant-waiver';
            } elseif ($request->waiver_type === 'senior') {
                $view = 'pdf.senior-waiver';
            }
            
            $pdf = Pdf::loadView($view, $pdfData);
            $pdfName = $request->waiver_type . '_' . time() . '_' . Str::random(10) . '.pdf';
            $pdfFolder = 'intake_forms/';
            
            Storage::disk('public')->put($pdfFolder . $pdfName, $pdf->output());
            $pdfPath = 'storage/' . $pdfFolder . $pdfName;
            $data['pdf_path'] = $pdfPath;

            // 5. Update or Create Current Waiver
            $waiver = CustomerItemWaiver::updateOrCreate(
                ['item_id' => $request->item_id, 'waiver_type' => $request->waiver_type],
                $data
            );

            // 6. Create Audit History
            $auditData = array_merge($data, ['waiver_id' => $waiver->id]);
            CustomerItemWaiverAudit::create($auditData);

            return response()->json([
                'message' => 'Waiver saved and audit created successfully',
                'data' => $waiver,
                'pdf_url' => asset($pdfPath)
            ]);
        });
    }
}
