<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
        // Super Admin bypass: users with the configured super admin role
        // are granted every ability without needing explicit permissions.
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole(config('roles.super_admin_role'))) {
                return true;
            }
        });
    }
}
