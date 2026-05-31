<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\CustomerServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SOLID CustomerController
 * 
 * Single Responsibility: Only handles HTTP request/response concerns
 * Open/Closed: Extensible through service injection
 * Dependency Inversion: Depends on CustomerServiceInterface abstraction
 */
class CustomerController extends Controller
{
    public function __construct(
        private CustomerServiceInterface $customerService
    ) {}

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): array
    {
        $filters = [
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ];

        $customers = $this->customerService->listCustomers($filters);

        return CustomerResource::collection($customers)->resolve();
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): array
    {
        $customer = $this->customerService->createCustomer($request->customerData());

        return (new CustomerResource($customer))->resolve();
    }

    /**
     * Display the specified customer.
     */
    public function show(int $id): array
    {
        $customer = $this->customerService->getCustomer($id);

        return (new CustomerResource($customer))->resolve();
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): array
    {
        $customer = $this->customerService->updateCustomer($customer, $request->customerData());

        return (new CustomerResource($customer))->resolve();
    }

    /**
     * Archive the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->customerService->archiveCustomer($customer);

        return response()->json(['message' => 'Customer archived successfully']);
    }

    /**
     * Restore the specified customer.
     */
    public function restore(int $id): JsonResponse
    {
        $this->customerService->restoreCustomer($id);

        return response()->json(['message' => 'Customer restored successfully']);
    }

    /**
     * Get audit history for a customer.
     */
    public function getHistory(int $id): JsonResponse
    {
        $history = $this->customerService->getCustomerHistory($id);

        return response()->json($history);
    }
}
