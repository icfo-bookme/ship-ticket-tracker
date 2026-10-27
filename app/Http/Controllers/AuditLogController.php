<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditLogDataTableService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, AuditLogDataTableService $auditLogs): View|JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json($auditLogs->dataTable($request));
        }

        return view('ship_ticket_sales.audit-logs');
    }
}
