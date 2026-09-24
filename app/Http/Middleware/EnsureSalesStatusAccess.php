<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSalesStatusAccess
{
    /**
     * Allow the request through only when the user may open the given sale status list.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $status = $request->route('status') ?? 'pending';

        abort_unless($request->user()?->can("sales.status.{$status}"), 403);

        return $next($request);
    }
}
