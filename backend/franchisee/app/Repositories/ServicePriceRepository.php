<?php

namespace App\Repositories;

use App\Contracts\Repositories\ServicePriceRepositoryInterface;
use App\Models\ServicePrice;
use Illuminate\Support\Collection;

class ServicePriceRepository implements ServicePriceRepositoryInterface
{
    public function all(int $companyId): Collection
    {
        return ServicePrice::where('company_id', $companyId)
            ->orderBy('id')
            ->get();
    }

    public function updateAll(int $companyId, array $services): Collection
    {
        foreach ($services as $serviceData) {
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

        return $this->all($companyId);
    }
}
