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

// Repository Implementations
use App\Repositories\BookingRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\LeadRepository;
use App\Repositories\BlockoutRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\IncomeRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\WaitlistRepository;

// Service Interfaces
use App\Contracts\Services\BookingServiceInterface;
use App\Contracts\Services\CustomerServiceInterface;
use App\Contracts\Services\LeadServiceInterface;
use App\Contracts\Services\BlockoutServiceInterface;
use App\Contracts\Services\ExpenseServiceInterface;
use App\Contracts\Services\IncomeServiceInterface;
use App\Contracts\Services\InventoryServiceInterface;
use App\Contracts\Services\WaitlistServiceInterface;

// Service Implementations
use App\Services\BookingService;
use App\Services\CustomerService;
use App\Services\LeadService;
use App\Services\BlockoutService;
use App\Services\ExpenseService;
use App\Services\IncomeService;
use App\Services\InventoryService;
use App\Services\WaitlistService;

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

        // Services
        BookingServiceInterface::class => BookingService::class,
        CustomerServiceInterface::class => CustomerService::class,
        LeadServiceInterface::class => LeadService::class,
        BlockoutServiceInterface::class => BlockoutService::class,
        ExpenseServiceInterface::class => ExpenseService::class,
        IncomeServiceInterface::class => IncomeService::class,
        InventoryServiceInterface::class => InventoryService::class,
        WaitlistServiceInterface::class => WaitlistService::class,
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
