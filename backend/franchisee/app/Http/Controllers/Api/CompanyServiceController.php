<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\CompanyServiceServiceInterface;
use App\Http\Requests\CompanyService\UpdateAllCompanyServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyServiceController extends Controller
{
    public function __construct(
        protected CompanyServiceServiceInterface $companyServiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->companyServiceService->index());
    }

    public function updateAll(UpdateAllCompanyServiceRequest $request): JsonResponse
    {
        $services = $this->companyServiceService->updateAll($request->validated()['services']);
        return response()->json($services);
    }
}
