<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// gates the "Manage Admins" section — runs after admin.auth, so a request that gets here
// is already a logged-in admin; this just adds the extra super-admin-only restriction
class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->user()?->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can manage admin accounts.');
        }

        return $next($request);
    }
}
