<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\CancellationPolicyServiceInterface;
use Illuminate\Http\JsonResponse;

class CancellationPoliciesController extends Controller
{
    public function __construct(
        protected CancellationPolicyServiceInterface $cancellationPolicyService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->cancellationPolicyService->index());
    }
}
