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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'tenant.active' => \App\Http\Middleware\EnsureTenantIsActive::class,
            'subscription' => \App\Http\Middleware\EnforceSubscriptionLimits::class,
            'super_admin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhook/razorpay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
