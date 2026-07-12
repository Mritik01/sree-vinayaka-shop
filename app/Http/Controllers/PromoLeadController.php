<?php

namespace App\Http\Controllers;

use App\Exceptions\OtpDeliveryException;
use App\Models\Lead;
use App\Services\TwilioVerifyOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PromoLeadController extends Controller
{
    protected const RESEND_COOLDOWN_SECONDS = 45;
    protected const OTP_TTL_MINUTES = 15;
    protected const MAX_VERIFY_ATTEMPTS = 5;

    public function sendOtp(Request $request, TwilioVerifyOtpService $otp)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string'],
        ]);

        $phone = $this->normalizePhone($data['phone']);

        if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
            return response()->json([
                'ok' => false,
                'message' => 'Please enter a valid 10-digit mobile number.',
            ], 422);
        }

        $cacheKey = "otp:{$phone}";
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
                Log::error('Promo OTP send failed', ['phone' => $phone, 'message' => $e->getMessage()]);

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

    public function verifyOtp(Request $request, TwilioVerifyOtpService $otp)
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'digits:6'],
        ]);

        $phone = $this->normalizePhone($data['phone']);
        $cacheKey = "otp:{$phone}";
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
                Log::error('Promo OTP verify failed', ['phone' => $phone, 'message' => $e->getMessage()]);

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

        Lead::create([
            'name' => $pending['name'],
            'phone' => $phone,
            'verified_at' => now(),
        ]);

        Cache::forget($cacheKey);

        return response()->json(['ok' => true]);
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }
}
