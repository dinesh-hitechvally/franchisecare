<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\SmsCreditServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsCreditController extends Controller
{
    public function __construct(
        protected SmsCreditServiceInterface $smsCreditService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->smsCreditService->index($request->user()));
    }

    public function purchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => 'required|string|in:sms_500,sms_1000',
        ]);

        return response()->json($this->smsCreditService->purchase($request->user(), $validated));
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json($this->smsCreditService->history($request->user()));
    }
}
