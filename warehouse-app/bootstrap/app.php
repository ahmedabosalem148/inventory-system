<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminAuth::class,
            'warehouse.auth' => \App\Http\Middleware\WarehouseAuth::class,
            'warehouse.manager' => \App\Http\Middleware\WarehouseManagerAuth::class,
        ]);
        
        // Use our custom CSRF middleware
        $middleware->validateCsrfTokens(except: [
            'test-create-product',
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
