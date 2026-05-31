<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Repository Interfaces
use App\Contracts\Repositories\BookingRepositoryInterface;

// Repository Implementations
use App\Repositories\BookingRepository;

// Service Interfaces
use App\Contracts\Services\BookingServiceInterface;

// Service Implementations
use App\Services\BookingService;

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

        // Services
        BookingServiceInterface::class => BookingService::class,
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
