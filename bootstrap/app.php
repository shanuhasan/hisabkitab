<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.active' => \App\Http\Middleware\EnsureTenantIsActive::class,
            'subscription' => \App\Http\Middleware\EnforceSubscriptionLimits::class,
            'super_admin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
            'shop_user' => \App\Http\Middleware\EnsureUserIsShopUser::class,
            'api.shop' => \App\Http\Middleware\EnsureApiShopAccess::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhook/razorpay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The mobile API always answers in JSON, even without an Accept header.
        $exceptions->shouldRenderJsonWhen(
            fn (\Illuminate\Http\Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        // Friendly message instead of "No query results for model [App\Models\Customer] 5".
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getPrevious() instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                    ? 'Record not found.'
                    : ($e->getMessage() ?: 'Not found.');

                return response()->json(['message' => $message], 404);
            }
        });
    })->create();
