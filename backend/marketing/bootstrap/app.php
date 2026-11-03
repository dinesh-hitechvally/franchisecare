<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // every successful JSON answer of the dashboard API gets the standard shape (App\Support\ApiResponse)
        $middleware->api(append: [\App\Http\Middleware\WrapApiResponse::class]);

        $middleware->alias([
            'no.integration' => \App\Http\Middleware\BlockIntegrationTokens::class,
            'log.activity' => \App\Http\Middleware\LogActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // every failure of the API, including abort() and validation, is answered in the standard failure shape
        $exceptions->render(function (Throwable $e, Request $request) {
            return $request->is('api/*') || $request->expectsJson() ? \App\Support\ApiResponse::fromException($e) : null;
        });
    })->create();
