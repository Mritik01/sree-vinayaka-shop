<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const LOCALES = ['en', 'hi'];

    // admin, rider, and customer each pick their language independently — an admin browsing in
    // Hindi shouldn't flip the language for the customer or rider half of the site on the same
    // device, and vice versa, so each side has its own session key and its own account column
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin*')) {
            $locale = Auth::guard('admin')->user()?->locale ?? session('admin_locale');
        } elseif ($request->is('rider*')) {
            $locale = Auth::guard('rider')->user()?->locale ?? session('rider_locale');
        } else {
            $locale = $request->user()?->locale ?? session('locale');
        }

        if (in_array($locale, self::LOCALES, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
