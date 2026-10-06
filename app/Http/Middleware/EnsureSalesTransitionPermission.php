<?php

namespace App\Http\Middleware;

use App\Enums\SaleStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSalesTransitionPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $permission = match ($request->route('status')) {
            SaleStatus::PaymentVerified->value => 'sales.verify',
            SaleStatus::TicketIssued->value => 'sales.issue',
            SaleStatus::TicketPrinted->value => 'sales.mark_printed',
            SaleStatus::ShipmentIdEntered->value => 'sales.create_parcel',
            SaleStatus::Shipped->value => 'sales.mark_shipped',
            SaleStatus::CollectFromOffice->value => 'sales.mark_collected',
            default => null,
        };

        abort_unless($permission && $request->user()?->can($permission), 403);

        return $next($request);
    }
}
