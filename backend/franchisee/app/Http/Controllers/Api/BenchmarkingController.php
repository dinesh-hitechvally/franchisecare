<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\BenchmarkingServiceInterface;
use App\Http\Requests\Benchmarking\IndexBenchmarkingRequest;
use Illuminate\Http\JsonResponse;

class BenchmarkingController extends Controller
{
    public function __construct(
        protected BenchmarkingServiceInterface $benchmarkingService
    ) {}

    public function index(IndexBenchmarkingRequest $request): JsonResponse
    {
        $result = $this->benchmarkingService->getReport(
            $request->user(),
            $request->validated()
        );

        return response()->json($result);
    }
}
