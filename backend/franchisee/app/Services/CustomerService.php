<?php

namespace App\Services;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Services\CustomerServiceInterface;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerAudit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    public function listCustomers(array $filters = []): Collection
    {
        // Add company_id filter from auth
        if (Auth::check() && Auth::user()->company_id) {
            $filters['company_id'] = Auth::user()->company_id;
        }

        return $this->customerRepository->getAll($filters);
    }

    public function getCustomer(int $id): Customer
    {
        return $this->customerRepository->findByIdOrFail($id, ['customerItems']);
    }

    public function createCustomer(array $data): Customer
    {
        // Set company_id from authenticated user
        $data['company_id'] = Auth::user()->company_id;

        $customer = $this->customerRepository->create($data);

        // Record Audit
        $this->recordCustomerAudit($customer, 'created');

        return $customer;
    }

    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $customer = $this->customerRepository->update($customer, $data);

        // Record Audit
        $this->recordCustomerAudit($customer, 'updated');

        return $customer->load('customerItems');
    }

    public function archiveCustomer(Customer $customer): Customer
    {
        $customer = $this->customerRepository->archive($customer);

        // Record Audit
        $this->recordCustomerAudit($customer, 'archived');

        return $customer;
    }

    public function restoreCustomer(int $id): Customer
    {
        $customer = Customer::where('is_archived', true)->findOrFail($id);
        $customer = $this->customerRepository->restore($customer);

        // Record Audit
        $this->recordCustomerAudit($customer, 'restored');

        return $customer;
    }

    public function getCustomerHistory(int $customerId): LengthAwarePaginator
    {
        return CustomerAudit::where('customer_id', $customerId)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate(5);
    }

    private function recordCustomerAudit(Customer $customer, string $actionType): void
    {
        $auditData = $customer->only([
            'first_name', 'last_name', 'email', 'other_email',
            'phone', 'other_phone', 'address', 'street_address',
            'suburb', 'postcode', 'state', 'company_id', 'notes',
            'referred_by', 'is_ndis', 'is_subscribed', 'is_active',
            'latitude', 'longitude', 'reference_id', 'is_archived'
        ]);

        $auditData['customer_id'] = $customer->id;
        $auditData['action_type'] = $actionType;
        $auditData['action_at'] = now();

        CustomerAudit::create($auditData);
    }
}
