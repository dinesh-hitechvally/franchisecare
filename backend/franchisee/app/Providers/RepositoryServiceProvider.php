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
use App\Contracts\Repositories\SettingsRepositoryInterface;
use App\Contracts\Repositories\BlockoutRecurringRepositoryInterface;
use App\Contracts\Repositories\RecurringExpenseRepositoryInterface;
use App\Contracts\Repositories\RecurringIncomeRepositoryInterface;
use App\Contracts\Repositories\ExpenseCategoryRepositoryInterface;
use App\Contracts\Repositories\IncomeCategoryRepositoryInterface;
use App\Contracts\Repositories\InventoryCategoryRepositoryInterface;

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
use App\Repositories\SettingsRepository;
use App\Repositories\BlockoutRecurringRepository;
use App\Repositories\RecurringExpenseRepository;
use App\Repositories\RecurringIncomeRepository;
use App\Repositories\ExpenseCategoryRepository;
use App\Repositories\IncomeCategoryRepository;
use App\Repositories\InventoryCategoryRepository;

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
use App\Contracts\Services\SettingsServiceInterface;
use App\Contracts\Services\BlockoutRecurringServiceInterface;
use App\Contracts\Services\RecurringExpenseServiceInterface;
use App\Contracts\Services\RecurringIncomeServiceInterface;
use App\Contracts\Services\ExpenseCategoryServiceInterface;
use App\Contracts\Services\IncomeCategoryServiceInterface;
use App\Contracts\Services\InventoryCategoryServiceInterface;

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
use App\Services\SettingsService;
use App\Services\BlockoutRecurringService;
use App\Services\RecurringExpenseService;
use App\Services\RecurringIncomeService;
use App\Services\ExpenseCategoryService;
use App\Services\IncomeCategoryService;
use App\Services\InventoryCategoryService;

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
        SettingsRepositoryInterface::class => SettingsRepository::class,
        BlockoutRecurringRepositoryInterface::class => BlockoutRecurringRepository::class,
        RecurringExpenseRepositoryInterface::class => RecurringExpenseRepository::class,
        RecurringIncomeRepositoryInterface::class => RecurringIncomeRepository::class,
        ExpenseCategoryRepositoryInterface::class => ExpenseCategoryRepository::class,
        IncomeCategoryRepositoryInterface::class => IncomeCategoryRepository::class,
        InventoryCategoryRepositoryInterface::class => InventoryCategoryRepository::class,

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
        SettingsServiceInterface::class => SettingsService::class,
        BlockoutRecurringServiceInterface::class => BlockoutRecurringService::class,
        RecurringExpenseServiceInterface::class => RecurringExpenseService::class,
        RecurringIncomeServiceInterface::class => RecurringIncomeService::class,
        ExpenseCategoryServiceInterface::class => ExpenseCategoryService::class,
        IncomeCategoryServiceInterface::class => IncomeCategoryService::class,
        InventoryCategoryServiceInterface::class => InventoryCategoryService::class,
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
