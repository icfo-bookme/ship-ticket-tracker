<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyPermission
{
    /**
     * @param  array<int, string>  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        abort_unless(
            $user && collect($permissions)->contains(fn (string $permission): bool => $user->can($permission)),
            403,
        );

        return $next($request);
    }
}
