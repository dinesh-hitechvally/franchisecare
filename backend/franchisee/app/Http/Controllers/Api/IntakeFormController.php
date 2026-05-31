<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\IntakeFormServiceInterface;
use App\Http\Requests\IntakeForm\StoreIntakeFormRequest;
use App\Models\CustomerItemWaiver;
use App\Models\CustomerItem;
use Illuminate\Http\JsonResponse;

class IntakeFormController extends Controller
{
    public function __construct(
        private IntakeFormServiceInterface $intakeFormService
    ) {}

    public function getByPet(CustomerItem $pet): JsonResponse
    {
        return response()->json($this->intakeFormService->getWaiversByPet($pet));
    }

    public function show(CustomerItemWaiver $waiver): JsonResponse
    {
        return response()->json($waiver);
    }

    public function getHistory(CustomerItem $pet, string $type): JsonResponse
    {
        return response()->json($this->intakeFormService->getWaiverHistory($pet, $type));
    }

    public function store(StoreIntakeFormRequest $request): JsonResponse
    {
        $result = $this->intakeFormService->createWaiver($request->validated());
        return response()->json($result);
    }
}
