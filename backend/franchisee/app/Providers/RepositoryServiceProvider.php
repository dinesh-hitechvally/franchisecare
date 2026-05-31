<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Interfaces
use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Contracts\Repositories\BlockoutRepositoryInterface;
use App\Contracts\Repositories\ExpenseRepositoryInterface;
use App\Contracts\Repositories\IncomeRepositoryInterface;
use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Contracts\Repositories\WaitlistRepositoryInterface;
use App\Contracts\Repositories\PetRepositoryInterface;
use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Contracts\Repositories\BookingRecurringRepositoryInterface;
use App\Contracts\Repositories\CommunicationRepositoryInterface;
use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Contracts\Repositories\IntakeFormRepositoryInterface;
use App\Contracts\Repositories\ServiceRepositoryInterface;

// Repository Implementations
use App\Repositories\BookingRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\LeadRepository;
use App\Repositories\BlockoutRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\IncomeRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\WaitlistRepository;
use App\Repositories\PetRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\BookingRecurringRepository;
use App\Repositories\CommunicationRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\IntakeFormRepository;
use App\Repositories\ServiceRepository;

// Service Interfaces
use App\Contracts\Services\BookingServiceInterface;
use App\Contracts\Services\CustomerServiceInterface;
use App\Contracts\Services\LeadServiceInterface;
use App\Contracts\Services\BlockoutServiceInterface;
use App\Contracts\Services\ExpenseServiceInterface;
use App\Contracts\Services\IncomeServiceInterface;
use App\Contracts\Services\InventoryServiceInterface;
use App\Contracts\Services\WaitlistServiceInterface;
use App\Contracts\Services\PetServiceInterface;
use App\Contracts\Services\DocumentServiceInterface;
use App\Contracts\Services\BookingRecurringServiceInterface;
use App\Contracts\Services\CommunicationServiceInterface;
use App\Contracts\Services\PaymentServiceInterface;
use App\Contracts\Services\IntakeFormServiceInterface;
use App\Contracts\Services\ServiceServiceInterface;

// Service Implementations
use App\Services\BookingService;
use App\Services\CustomerService;
use App\Services\LeadService;
use App\Services\BlockoutService;
use App\Services\ExpenseService;
use App\Services\IncomeService;
use App\Services\InventoryService;
use App\Services\WaitlistService;
use App\Services\PetService;
use App\Services\DocumentService;
use App\Services\BookingRecurringService;
use App\Services\CommunicationService;
use App\Services\PaymentService;
use App\Services\IntakeFormService;
use App\Services\ServiceService;

/**
 * Dependency Inversion Principle (DIP):
 * This provider binds interfaces to their implementations,
 * allowing the application to depend on abstractions.
 * 
 * Open/Closed Principle (OCP):
 * To change an implementation, just update the binding here
 * without modifying the classes that depend on the interface.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     * 
     * Add new repository and service bindings here when implementing
     * SOLID patterns for other controllers.
     *
     * @var array
     */
    public array $bindings = [
        // Repositories
        BookingRepositoryInterface::class => BookingRepository::class,
        CustomerRepositoryInterface::class => CustomerRepository::class,
        LeadRepositoryInterface::class => LeadRepository::class,
        BlockoutRepositoryInterface::class => BlockoutRepository::class,
        ExpenseRepositoryInterface::class => ExpenseRepository::class,
        IncomeRepositoryInterface::class => IncomeRepository::class,
        InventoryRepositoryInterface::class => InventoryRepository::class,
        WaitlistRepositoryInterface::class => WaitlistRepository::class,
        PetRepositoryInterface::class => PetRepository::class,
        DocumentRepositoryInterface::class => DocumentRepository::class,
        BookingRecurringRepositoryInterface::class => BookingRecurringRepository::class,
        CommunicationRepositoryInterface::class => CommunicationRepository::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
        IntakeFormRepositoryInterface::class => IntakeFormRepository::class,
        ServiceRepositoryInterface::class => ServiceRepository::class,

        // Services
        BookingServiceInterface::class => BookingService::class,
        CustomerServiceInterface::class => CustomerService::class,
        LeadServiceInterface::class => LeadService::class,
        BlockoutServiceInterface::class => BlockoutService::class,
        ExpenseServiceInterface::class => ExpenseService::class,
        IncomeServiceInterface::class => IncomeService::class,
        InventoryServiceInterface::class => InventoryService::class,
        WaitlistServiceInterface::class => WaitlistService::class,
        PetServiceInterface::class => PetService::class,
        DocumentServiceInterface::class => DocumentService::class,
        BookingRecurringServiceInterface::class => BookingRecurringService::class,
        CommunicationServiceInterface::class => CommunicationService::class,
        PaymentServiceInterface::class => PaymentService::class,
        IntakeFormServiceInterface::class => IntakeFormService::class,
        ServiceServiceInterface::class => ServiceService::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Repositories are bound as singletons for performance
        $this->app->singleton(BookingRepositoryInterface::class, BookingRepository::class);

        // Services are bound normally (new instance each time)
        $this->app->bind(BookingServiceInterface::class, BookingService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
