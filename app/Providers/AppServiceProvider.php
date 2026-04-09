<?php

namespace App\Providers;

use App\Contracts\SupplierImportExportServiceInterface;
use App\Services\SupplierImportExportService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SupplierImportExportServiceInterface::class, SupplierImportExportService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
