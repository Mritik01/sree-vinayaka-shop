<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    private const EXCLUDED_PATTERNS = ['admin*', 'shop-status', 'delivery-check', '_debugbar*', 'build/*'];

    private const LABELS = [
        '/' => 'Home',
        'cart' => 'Cart',
        'favorites' => 'Favorites',
        'orders' => 'My Orders',
        'account' => 'Account',
    ];

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    // deferred to run AFTER the response is already on its way to the client (PHP-FPM/LiteSpeed
    // flush the response on Response::send(), before terminate() middleware runs) — this used to
    // run inline in handle() before returning the response, which meant every session-less
    // visitor's page load blocked on ActivityLogger::log() -> IpGeolocationService's synchronous
    // ip-api.com HTTP call (up to a 3s timeout) before they saw anything. Nothing here reads
    // $response, so moving it costs nothing.
    public function terminate(Request $request, Response $response): void
    {
        if ($this->shouldTrack($request)) {
            ActivityLogger::log('page_view', $this->labelFor($request));
        }
    }

    private function shouldTrack(Request $request): bool
    {
        if (!$request->isMethod('get') || $request->ajax() || $request->wantsJson()) {
            return false;
        }

        foreach (self::EXCLUDED_PATTERNS as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }

    private function labelFor(Request $request): string
    {
        if ($request->is('product/*')) {
            return 'Product Page';
        }

        return self::LABELS[$request->path()] ?? $request->path();
    }
}
