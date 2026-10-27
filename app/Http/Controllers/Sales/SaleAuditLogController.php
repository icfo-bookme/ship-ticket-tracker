<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ShipTicketSale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SaleAuditLogController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, int $sale): JsonResponse|View
    {
        Gate::authorize('viewAny', AuditLog::class);
        $record = ShipTicketSale::withTrashed()->findOrFail($sale);

        $logs = AuditLog::query()
            ->where('sale_id', $record->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        if ($request->expectsJson()) {
            return response()->json($logs);
        }

        return view('ship_ticket_sales.partials.audit-entries', ['logs' => $logs]);
    }
}
