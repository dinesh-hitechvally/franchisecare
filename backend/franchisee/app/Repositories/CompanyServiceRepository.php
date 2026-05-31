<?php

namespace App\Repositories;

use App\Contracts\Repositories\CompanyServiceRepositoryInterface;
use App\Models\CompanyService;
use App\Models\Service;
use Illuminate\Support\Collection;

class CompanyServiceRepository implements CompanyServiceRepositoryInterface
{
    public function all(int $companyId): Collection
    {
        $services = Service::all();
        $companyServices = CompanyService::where('company_id', $companyId)->get()->keyBy('service_id');

        return $services->map(function ($service) use ($companyServices, $companyId) {
            $companyService = $companyServices->get($service->id);

            return [
                'id' => $companyService?->id,
                'company_id' => $companyId,
                'service_id' => $service->id,
                'name' => $service->name,
                'my_price' => $companyService?->price ?? $service->price,
                'default_price' => $service->price,
                'color' => '#4169E1',
                'my_time' => $companyService?->duration ?? $service->duration,
                'default_time' => $service->duration,
            ];
        });
    }

    public function updateAll(int $companyId, array $services): void
    {
        foreach ($services as $serviceData) {
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
    }
}
