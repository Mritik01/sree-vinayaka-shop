<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // this app has no dedicated login page — sign-in happens through a modal on any page
        // (partials/auth-modal.blade.php). Laravel's default here is route('login'), which
        // doesn't exist and throws RouteNotFoundException instead of redirecting; send them to
        // the homepage with a flag app.js uses to auto-open the modal and, after a successful
        // login, carry them on to wherever they were originally headed (e.g. back to checkout).
        return url('/').'?login=1&redirect='.urlencode($request->fullUrl());
    }
}
