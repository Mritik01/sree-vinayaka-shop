<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// applied only to the specific customer routes a blocked account must lose (account page, cart
// mutations, coupons, checkout — see routes/web.php) — deliberately NOT a blanket 'web' group
// middleware, since a blocked customer should still be able to browse the site and reach their
// existing order history/invoices (see OrderTrackingController's routes, which never get this).
// Guests always pass through untouched; this only ever acts on an authenticated, blocked user.
class EnsureNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isBlocked()) {
            $user = Auth::user();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'blocked' => true,
                    'redirect' => route('account.blocked'),
                    'reason' => $user->blockReasonLabel(),
                    'message' => $user->block_message,
                ], 403);
            }

            return redirect()->route('account.blocked');
        }

        return $next($request);
    }
}
