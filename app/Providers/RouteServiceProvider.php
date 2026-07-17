<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('otp-send', function (Request $request) {
            return [
                Limit::perMinute(3)->by('otp-send-ip:'.$request->ip()),
                Limit::perMinute(1)->by('otp-send-phone:'.$request->input('phone')),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinute(10)->by('otp-verify-ip:'.$request->ip());
        });

        RateLimiter::for('auth-otp-send', function (Request $request) {
            return [
                Limit::perMinute(3)->by('auth-otp-send-ip:'.$request->ip()),
                Limit::perMinute(1)->by('auth-otp-send-phone:'.$request->input('phone')),
            ];
        });

        RateLimiter::for('auth-otp-verify', function (Request $request) {
            return Limit::perMinute(10)->by('auth-otp-verify-ip:'.$request->ip());
        });

        RateLimiter::for('auth-complete-signup', function (Request $request) {
            return Limit::perMinute(10)->by('auth-complete-signup-ip:'.$request->ip());
        });

        // admin/rider logins are plain username+password with no OTP step — without this,
        // nothing stops an unlimited password-guessing attempt against either guard
        RateLimiter::for('admin-login', function (Request $request) {
            return [
                Limit::perMinute(5)->by('admin-login-ip:'.$request->ip()),
                Limit::perMinute(5)->by('admin-login-user:'.strtolower((string) $request->input('username'))),
            ];
        });

        RateLimiter::for('rider-login', function (Request $request) {
            return [
                Limit::perMinute(5)->by('rider-login-ip:'.$request->ip()),
                Limit::perMinute(5)->by('rider-login-user:'.strtolower((string) $request->input('username'))),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
