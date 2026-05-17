<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyService;
use App\Models\Service;
use Illuminate\Http\Request;

class CompanyServiceController extends Controller
{
    private function getCompanyId()
    {
        return auth()->user()->company_id ?? 1;
    }

    public function index()
    {
        $companyId = $this->getCompanyId();

        // Get all services with company-specific pricing
        $services = Service::all();
        $companyServices = CompanyService::where('company_id', $companyId)->get()->keyBy('service_id');

        $result = $services->map(function ($service) use ($companyServices, $companyId) {
            $companyService = $companyServices->get($service->id);

            return [
                'id' => $companyService?->id,
                'company_id' => $companyId,
                'service_id' => $service->id,
                'name' => $service->name,
                'my_price' => $companyService?->price ?? $service->price,
                'default_price' => $service->price,
                'color' => '#4169E1', // Default color, can be customized
                'my_time' => $companyService?->duration ?? $service->duration,
                'default_time' => $service->duration,
            ];
        });

        return response()->json($result);
    }

    public function updateAll(Request $request)
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'services' => 'required|array',
            'services.*.id' => 'nullable|integer',
            'services.*.service_id' => 'required|integer',
            'services.*.my_price' => 'required|numeric|min:0',
            'services.*.my_time' => 'required|integer|min:0',
        ]);

        foreach ($validated['services'] as $serviceData) {
            CompanyService::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'service_id' => $serviceData['service_id']
                ],
                [
                    'price' => $serviceData['my_price'],
                    'duration' => $serviceData['my_time'],
                ]
            );
        }

        // Return updated list
        return $this->index();
    }
}
