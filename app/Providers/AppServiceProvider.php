<?php

namespace App\Providers;

use App\Services\Logging\SystemLoggerService;
use App\Services\RequestAnalyticsService;
use App\Services\Role\RoleService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register RoleService as a singleton
        $this->app->singleton(RoleService::class, function ($app) {
            return new RoleService();
        });
        // Register SystemLoggerService as a singleton  
        $this->app->singleton(SystemLoggerService::class, function ($app) {
            return new SystemLoggerService();
        });

        $this->app->singleton('request-analytics', function ($app) {
            return new RequestAnalyticsService();
        });

        $this->app->bind(RequestAnalyticsService::class, function ($app) {
            return new RequestAnalyticsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        
    }
}
