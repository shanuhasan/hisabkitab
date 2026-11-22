<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Bootstrap 5 pagination markup across all paginated views
        Paginator::useBootstrapFive();

        // Enforce HTTPS URLs only when explicitly configured or over secure requests
        if (env('FORCE_HTTPS', false) || request()->isSecure()) {
            URL::forceScheme('https');
        }
    }
}
