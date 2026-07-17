<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// baseline security headers for every response — none of these existed before this audit.
// The CSP is intentionally not "strict" (script-src/style-src allow 'unsafe-inline'): the app
// relies on inline <script>/onclick attributes and inline style="" throughout its Blade views,
// and rewriting all of that to a nonce-based CSP is a much larger change than this pass calls
// for. Even with 'unsafe-inline', this still blocks the two things that matter most for an XSS
// payload: loading a script/frame/object from an attacker-controlled domain, and exfiltrating
// data to one via connect-src/img-src.
class SecurityHeaders
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=(), payment=(self)');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $csp = implode('; ', [
            "default-src 'self'",
            // 'unsafe-eval' is required by Alpine.js itself (it evaluates x-data/x-on expressions
            // via `new Function()` at runtime) — without it, every interactive element on the
            // site breaks. Adopting Alpine's CSP-safe build to drop this is a real build-tooling
            // change, out of scope for a header-hardening pass; confirmed necessary by testing
            // this CSP against real pages with Playwright before landing it.
            // cdn.razorpay.com serves their risk-detection bundle, loaded by checkout.js itself —
            // missed in the first pass because it only loads once the payment widget actually
            // opens, which static review and page-load-only CSP testing never reached; caught by
            // running a real Razorpay-bound checkout through Playwright end-to-end.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://checkout.razorpay.com https://cdn.razorpay.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: blob:",
            "connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com",
            "frame-src 'self' https://api.razorpay.com https://checkout.razorpay.com",
            // Razorpay's widget spins up a blob: Web Worker; without this it falls back to
            // script-src, which doesn't cover blob: workers
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
