<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServicePrice;
use Illuminate\Http\Request;

class ServicePriceController extends Controller
{
    private function getCompanyId()
    {
        return auth()->user()->company_id ?? 1;
    }

    public function index()
    {
        $companyId = $this->getCompanyId();

        $servicePrices = ServicePrice::where('company_id', $companyId)
            ->orderBy('id')
            ->get();

        return response()->json($servicePrices);
    }

    public function updateAll(Request $request)
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'services' => 'required|array',
            'services.*.id' => 'nullable|integer',
            'services.*.name' => 'required|string',
            'services.*.my_price' => 'required|numeric|min:0',
            'services.*.default_price' => 'required|numeric|min:0',
            'services.*.color' => 'required|string',
            'services.*.my_time' => 'required|integer|min:0',
            'services.*.default_time' => 'required|integer|min:0',
        ]);

        foreach ($validated['services'] as $serviceData) {
            $serviceData['company_id'] = $companyId;

            if (isset($serviceData['id'])) {
                ServicePrice::where('id', $serviceData['id'])
                    ->where('company_id', $companyId)
                    ->update($serviceData);
            } else {
                ServicePrice::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'name' => $serviceData['name']
                    ],
                    $serviceData
                );
            }
        }

        $servicePrices = ServicePrice::where('company_id', $companyId)
            ->orderBy('id')
            ->get();

        return response()->json($servicePrices);
    }
}
