<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanWrite
{
    /**
     * Blocks any non-GET request from users with the Viewer role.
     * Applied to route groups that perform create/update/delete actions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canWrite() && ! $request->isMethod('get')) {
            abort(403, 'Role Viewer hanya memiliki akses baca (read-only).');
        }

        return $next($request);
    }
}
