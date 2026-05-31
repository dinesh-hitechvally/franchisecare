<?php

namespace App\Services;

use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Contracts\Services\LeadServiceInterface;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class LeadService implements LeadServiceInterface
{
    public function __construct(
        private LeadRepositoryInterface $leadRepository
    ) {}

    public function listLeads(array $filters = []): Collection
    {
        // Add company_id filter from auth
        if (Auth::user()?->company_id) {
            $filters['company_id'] = Auth::user()->company_id;
        }

        return $this->leadRepository->getAll($filters);
    }

    public function getLead(int $id): Lead
    {
        return $this->leadRepository->findByIdOrFail($id);
    }

    public function createLead(array $data): Lead
    {
        $data['company_id'] = Auth::user()?->company_id;
        $data['customer_name'] = $data['customer_name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        return $this->leadRepository->create($data);
    }

    public function updateLead(Lead $lead, array $data): Lead
    {
        // Generate customer_name if name parts are being updated
        if (!isset($data['customer_name']) && (isset($data['first_name']) || isset($data['last_name']))) {
            $firstName = $data['first_name'] ?? $lead->first_name;
            $lastName = $data['last_name'] ?? $lead->last_name;
            $data['customer_name'] = trim($firstName . ' ' . $lastName);
        }

        return $this->leadRepository->update($lead, $data);
    }

    public function deleteLead(Lead $lead): bool
    {
        return $this->leadRepository->delete($lead);
    }

    public function convertLead(Lead $lead): Lead
    {
        return $this->leadRepository->update($lead, ['status' => 'converted']);
    }
}
