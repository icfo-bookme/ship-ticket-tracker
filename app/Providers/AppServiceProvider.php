<?php

namespace App\Providers;

use App\Enums\SaleStatus;
use App\Models\ShipTicketSale;
use App\Models\User;
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
        foreach (['edit', 'delete'] as $action) {
            Gate::define("sales.{$action}-record", function (User $user, ShipTicketSale|int|string $sale) use ($action): bool {
                if ($user->can("sales.{$action}")) {
                    return true;
                }

                if (! $user->can("sales.pending.{$action}")) {
                    return false;
                }

                $sale = $sale instanceof ShipTicketSale ? $sale : ShipTicketSale::query()->findOrFail($sale);

                return $sale->status === SaleStatus::Pending->value;
            });
        }

        // Super Admin bypass: users with the configured super admin role
        // are granted every ability without needing explicit permissions.
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole(config('roles.super_admin_role'))) {
                return true;
            }
        });
    }
}
