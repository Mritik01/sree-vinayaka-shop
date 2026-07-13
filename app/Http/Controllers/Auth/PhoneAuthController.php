<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\OtpDeliveryException;
use App\Http\Controllers\Controller;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\ActivityLogger;
use App\Services\MasterCouponAssigner;
use App\Services\TwoFactorOtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PhoneAuthController extends Controller
{
    protected const RESEND_COOLDOWN_SECONDS = 45;
    protected const OTP_TTL_MINUTES = 15;
    protected const MAX_VERIFY_ATTEMPTS = 5;
    protected const CACHE_PREFIX = 'auth-otp:';

    public function sendOtp(Request $request, TwoFactorOtpService $otp)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string'],
        ]);

        $phone = PhoneNumber::normalize($data['phone']);

        if (!PhoneNumber::isValidIndianMobile($phone)) {
            return response()->json([
                'ok' => false,
                'message' => 'Please enter a valid 10-digit mobile number.',
            ], 422);
        }

        $cacheKey = self::CACHE_PREFIX.$phone;
        $existing = Cache::get($cacheKey);

        if ($existing && isset($existing['last_sent_at'])) {
            $secondsSinceLastSend = now()->diffInSeconds($existing['last_sent_at']);
            if ($secondsSinceLastSend < self::RESEND_COOLDOWN_SECONDS) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Please wait before requesting another OTP.',
                    'retry_after' => self::RESEND_COOLDOWN_SECONDS - $secondsSinceLastSend,
                ], 429);
            }
        }

        $devOtp = null;
        $cacheData = [
            'name' => $data['name'],
            'attempts' => 0,
            'last_sent_at' => now(),
        ];

        if (config('services.otp.dev_mode')) {
            $devOtp = (string) random_int(100000, 999999);
            $cacheData['otp'] = $devOtp;
        } else {
            try {
                $otp->send($phone);
            } catch (OtpDeliveryException $e) {
                Log::error('Auth OTP send failed', ['phone' => $phone, 'message' => $e->getMessage()]);

                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 503);
            }
        }

        Cache::put($cacheKey, $cacheData, now()->addMinutes(self::OTP_TTL_MINUTES));

        return response()->json([
            'ok' => true,
            'resend_after' => self::RESEND_COOLDOWN_SECONDS,
            'dev_otp' => $devOtp,
        ]);
    }

    public function verifyOtp(Request $request, TwoFactorOtpService $otp)
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'digits:6'],
        ]);

        $phone = PhoneNumber::normalize($data['phone']);
        $cacheKey = self::CACHE_PREFIX.$phone;
        $pending = Cache::get($cacheKey);

        if (!$pending) {
            return response()->json([
                'ok' => false,
                'message' => 'OTP expired or not found, please request a new one.',
            ], 422);
        }

        if (($pending['attempts'] ?? 0) >= self::MAX_VERIFY_ATTEMPTS) {
            Cache::forget($cacheKey);

            return response()->json([
                'ok' => false,
                'message' => 'Too many incorrect attempts. Please request a new OTP.',
            ], 422);
        }

        if (config('services.otp.dev_mode')) {
            $verified = isset($pending['otp']) && hash_equals($pending['otp'], $data['otp']);
        } else {
            try {
                $verified = $otp->verify($phone, $data['otp']);
            } catch (OtpDeliveryException $e) {
                Log::error('Auth OTP verify failed', ['phone' => $phone, 'message' => $e->getMessage()]);

                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 503);
            }
        }

        if (!$verified) {
            $pending['attempts'] = ($pending['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $pending, now()->addMinutes(self::OTP_TTL_MINUTES));

            $remaining = self::MAX_VERIFY_ATTEMPTS - $pending['attempts'];

            return response()->json([
                'ok' => false,
                'message' => $remaining > 0
                    ? "Incorrect OTP. {$remaining} attempt(s) left."
                    : 'Too many incorrect attempts. Please request a new OTP.',
            ], 422);
        }

        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['name' => $pending['name'], 'phone_verified_at' => now()]
        );

        if ($user->wasRecentlyCreated) {
            MasterCouponAssigner::assignFor($user);
        }

        $guestSessionId = $request->session()->getId();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // attribute this device's pre-login browsing (product views, cart adds) to the now-known user
        UserActivity::where('session_id', $guestSessionId)->whereNull('user_id')->update(['user_id' => $user->id]);
        SiteVisit::where('session_id', $guestSessionId)->whereNull('user_id')->update(['user_id' => $user->id]);
        ActivityLogger::log('login', $user->name);

        Cache::forget($cacheKey);

        return response()->json(['ok' => true]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
