<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;


   namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TripValidationService;
use App\Services\TripCreationService;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services as singletons for better performance
        $this->app->singleton(TripValidationService::class);
        $this->app->singleton(TripCreationService::class);
    }



    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
