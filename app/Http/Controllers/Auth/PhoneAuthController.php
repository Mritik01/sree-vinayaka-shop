<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\OtpDeliveryException;
use App\Http\Controllers\Controller;
use App\Models\LegalDocumentVersion;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserConsent;
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
    protected const VERIFIED_CACHE_PREFIX = 'auth-verified:';
    protected const VERIFIED_TTL_MINUTES = 10;

    public function sendOtp(Request $request, TwoFactorOtpService $otp)
    {
        $data = $request->validate([
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

        $user = User::where('phone', $phone)->first();

        // phone number belongs to an existing account — nothing else to ask, log them straight in.
        // A blocked account still logs in normally (they genuinely own this phone number) — every
        // other gated route (see EnsureNotBlocked) then bounces them to the restricted screen, and
        // `blocked: true` here tells the auth modal to skip the welcome-back celebration and send
        // them straight there too, instead of wherever they were originally headed.
        if ($user) {
            $this->loginAndAttribute($request, $user);
            Cache::forget($cacheKey);

            if ($user->isBlocked()) {
                return response()->json(['ok' => true, 'new_user' => false, 'blocked' => true, 'redirect' => route('account.blocked')]);
            }

            return response()->json(['ok' => true, 'new_user' => false, 'name' => $user->name]);
        }

        // no account yet — the OTP is confirmed, but we still need a name before we can create one.
        // The OTP's job is done, so clear it now; the "verified, awaiting name" state moves to its
        // own cache entry (tied to the exact session that just proved phone ownership, so knowing
        // someone's number alone can't be used to claim their account later) so that a *second*
        // send-otp call for this phone — from anyone, at any point while the name step is showing —
        // can no longer wipe this out from under the legitimate, already-verified user.
        Cache::forget($cacheKey);
        Cache::put(
            self::VERIFIED_CACHE_PREFIX.$phone,
            ['session' => $request->session()->getId()],
            now()->addMinutes(self::VERIFIED_TTL_MINUTES)
        );

        return response()->json(['ok' => true, 'new_user' => true]);
    }

    public function completeSignup(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string'],
            'agree_terms' => ['required', 'accepted'],
        ], [
            'agree_terms.required' => 'Please agree to the Terms & Conditions and Privacy Policy to continue.',
            'agree_terms.accepted' => 'Please agree to the Terms & Conditions and Privacy Policy to continue.',
        ]);

        $phone = PhoneNumber::normalize($data['phone']);
        $verifiedKey = self::VERIFIED_CACHE_PREFIX.$phone;
        $verified = Cache::get($verifiedKey);

        if (!$verified || ($verified['session'] ?? null) !== $request->session()->getId()) {
            return response()->json([
                'ok' => false,
                'message' => 'Your verification expired — please verify your phone number again.',
            ], 422);
        }

        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['name' => trim($data['name']), 'phone_verified_at' => now()]
        );

        if ($user->wasRecentlyCreated) {
            MasterCouponAssigner::assignFor($user);

            // consent is captured once, at account creation — an existing account re-using
            // this endpoint (shouldn't normally happen, firstOrCreate found them above) never
            // re-records it here to avoid a misleading duplicate entry
            UserConsent::record(
                $user->id,
                'signup',
                LegalDocumentVersion::current('terms')?->version,
                LegalDocumentVersion::current('privacy')?->version,
                $request->ip(),
                (string) $request->userAgent()
            );
        }

        $this->loginAndAttribute($request, $user);
        Cache::forget($verifiedKey);

        return response()->json(['ok' => true]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function loginAndAttribute(Request $request, User $user): void
    {
        $guestSessionId = $request->session()->getId();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // attribute this device's pre-login browsing (product views, cart adds) to the now-known user
        UserActivity::where('session_id', $guestSessionId)->whereNull('user_id')->update(['user_id' => $user->id]);
        SiteVisit::where('session_id', $guestSessionId)->whereNull('user_id')->update(['user_id' => $user->id]);
        ActivityLogger::log('login', $user->name);
    }
}
