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
        $response = $next($request);

        if ($this->shouldTrack($request)) {
            ActivityLogger::log('page_view', $this->labelFor($request));
        }

        return $response;
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
