<?php

namespace App\Contracts\Services;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Collection;

interface LeadServiceInterface
{
    public function listLeads(array $filters = []): Collection;

    public function getLead(int $id): Lead;

    public function createLead(array $data): Lead;

    public function updateLead(Lead $lead, array $data): Lead;

    public function deleteLead(Lead $lead): bool;

    public function convertLead(Lead $lead): Lead;
}
