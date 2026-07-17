<?php

namespace App\Http\Middleware;

use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $marker = $request->session()->get('impersonation');

        if ($marker) {
            if (ImpersonationService::isExpired($marker)) {
                ImpersonationService::stop($request, 'timeout');
            } else {
                ImpersonationService::touch($request);
            }
        }

        return $next($request);
    }
}
