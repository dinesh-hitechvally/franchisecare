<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\SuburbReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuburbReportController extends Controller
{
    public function __construct(
        protected SuburbReportServiceInterface $suburbReportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'suburb' => 'nullable|string|max:255',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
        ]);

        return response()->json($this->suburbReportService->index($request->user(), $validated));
    }
}
