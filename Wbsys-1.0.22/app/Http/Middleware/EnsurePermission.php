<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** RBAC: require one of the given permission slugs. */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }
        foreach ($permissions as $permission) {
            if ($request->user()->hasPermission($permission)) {
                return $next($request);
            }
        }
        abort(403, 'You do not have permission to perform this action.');
    }
}
