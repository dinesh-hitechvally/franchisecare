<?php

namespace App\Services;

use App\Contracts\Repositories\IntakeFormRepositoryInterface;
use App\Contracts\Services\IntakeFormServiceInterface;
use App\Models\CustomerItemWaiver;
use App\Models\CustomerItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IntakeFormService implements IntakeFormServiceInterface
{
    public function __construct(
        private IntakeFormRepositoryInterface $intakeFormRepository
    ) {}

    public function getWaiversByPet(CustomerItem $pet): Collection
    {
        return $this->intakeFormRepository->getByPet($pet->id)
            ->groupBy('waiver_type')
            ->map(fn($group) => $group->first());
    }

    public function getWaiver(int $id): CustomerItemWaiver
    {
        return $this->intakeFormRepository->findByIdOrFail($id);
    }

    public function getWaiverHistory(CustomerItem $pet, string $type): Collection
    {
        return $this->intakeFormRepository->getHistory($pet->id, $type);
    }

    public function createWaiver(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Save Signature
            $signaturePath = $this->saveSignature($data['signature']);

            // 2. Map Form Data to Columns
            $waiverData = $this->prepareWaiverData($data, $signaturePath);

            // 3. Prepare Data for PDF
            $pdfData = $this->preparePdfData($data, $signaturePath);

            // 4. Generate PDF
            $pdfPath = $this->generatePdf($data['waiver_type'], $pdfData);
            $waiverData['pdf_path'] = $pdfPath;

            // 5. Update or Create Current Waiver
            $waiver = $this->intakeFormRepository->updateOrCreate(
                ['item_id' => $data['item_id'], 'waiver_type' => $data['waiver_type']],
                $waiverData
            );

            // 6. Create Audit History
            $this->intakeFormRepository->createAudit(array_merge($waiverData, [
                'waiver_id' => $waiver->id,
                'action_at' => now(),
            ]));

            return [
                'message' => 'Waiver saved and audit created successfully',
                'data' => $waiver,
                'pdf_url' => asset($pdfPath),
            ];
        });
    }

    private function saveSignature(string $signatureData): string
    {
        $image = str_replace('data:image/png;base64,', '', $signatureData);
        $image = str_replace(' ', '+', $image);
        $imageName = 'sig_' . time() . '_' . Str::random(10) . '.png';

        Storage::disk('public')->put('signatures/' . $imageName, base64_decode($image));
        
        return 'storage/signatures/' . $imageName;
    }

    private function prepareWaiverData(array $data, string $signaturePath): array
    {
        $waiverData = [
            'customer_id' => $data['customer_id'],
            'item_id' => $data['item_id'],
            'waiver_type' => $data['waiver_type'],
            'signature_path' => $signaturePath,
        ];

        if (isset($data['form_data'])) {
            $fd = $data['form_data'];
            $waiverType = $data['waiver_type'];

            if ($waiverType === 'intake') {
                $waiverData = array_merge($waiverData, $this->mapIntakeFormData($fd));
            } elseif ($waiverType === 'cologne') {
                $waiverData['cologne_decline_reason'] = $fd['cologneReason'] ?? null;
                $waiverData['accepted_expectations'] = $fd['acceptedExpectations'] ?? false;
            } elseif ($waiverType === 'shampoo') {
                $waiverData['shampoo_own_reason'] = $fd['shampooReason'] ?? null;
                $waiverData['accepted_expectations'] = $fd['acceptedExpectations'] ?? false;
            } elseif ($waiverType === 'pregnant') {
                $waiverData['accepted_expectations'] = true;
                $waiverData['delivery_date'] = $fd['deliveryDate'] ?? null;
            } elseif ($waiverType === 'senior') {
                $waiverData['accepted_expectations'] = true;
                $waiverData['age'] = $fd['age'] ?? null;
            } elseif (in_array($waiverType, ['matted', 'clipping'])) {
                $waiverData['accepted_expectations'] = true;
            }
        }

        return $waiverData;
    }

    private function mapIntakeFormData(array $fd): array
    {
        return [
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
        ];
    }

    private function preparePdfData(array $data, string $signaturePath): array
    {
        return [
            'ownerName' => $data['ownerName'],
            'petName' => $data['petName'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'breed' => $data['breed'] ?? 'Not specified',
            'form_data' => $data['form_data'] ?? [],
            'signature_url' => $signaturePath,
            'franchiseeName' => $data['form_data']['franchiseeName'] ?? 'Mate Support',
            'customerAddress' => $data['form_data']['customerAddress'] ?? 'Not specified',
        ];
    }

    private function generatePdf(string $waiverType, array $pdfData): string
    {
        $view = match ($waiverType) {
            'intake' => 'pdf.intake-form',
            'cologne' => 'pdf.cologne-waiver',
            'shampoo' => 'pdf.shampoo-waiver',
            'matted' => 'pdf.matted-waiver',
            'clipping' => 'pdf.clipping-waiver',
            'pregnant' => 'pdf.pregnant-waiver',
            'senior' => 'pdf.senior-waiver',
            default => 'pdf.generic-waiver',
        };

        $pdf = Pdf::loadView($view, $pdfData);
        $pdfName = $waiverType . '_' . time() . '_' . Str::random(10) . '.pdf';
        $pdfFolder = 'intake_forms/';

        Storage::disk('public')->put($pdfFolder . $pdfName, $pdf->output());

        return 'storage/' . $pdfFolder . $pdfName;
    }
}
