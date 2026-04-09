<?php

namespace App\Providers;

use App\Contracts\SupplierImportExportServiceInterface;
use App\Models\User;
use App\Services\SupplierImportExportService;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('manage-suppliers', function (User $user): bool {
            $mode = config('suppliers.access_mode', 'all');

            if ($mode === 'emails') {
                $allowedEmails = config('suppliers.allowed_emails', []);

                return in_array(strtolower($user->email), $allowedEmails, true);
            }

            return $user->exists;
        });
    }
}
