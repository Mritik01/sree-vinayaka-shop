<?php

namespace App\Services;

use App\Exceptions\OtpDeliveryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwoFactorOtpService
{
    // 2Factor's AUTOGEN endpoint hands back a session id that VERIFY needs instead of the
    // phone number — cached here (keyed by phone) so callers keep using send()/verify(phone, otp)
    protected const SESSION_CACHE_PREFIX = '2factor-session:';
    protected const SESSION_TTL_MINUTES = 15;

    protected string $apiKey;
    protected string $template;

    public function __construct()
    {
        $this->apiKey = (string) config('services.twofactor.api_key');
        $this->template = (string) config('services.twofactor.template');
    }

    /**
     * Ask 2Factor to generate and text an OTP to the given phone number.
     */
    public function send(string $phone): void
    {
        if ($this->apiKey === '') {
            Log::error('2Factor send failed: 2Factor API key is not configured.');
            throw new OtpDeliveryException('Could not send OTP right now, please try again in a bit.');
        }

        $response = Http::timeout(5)
            ->get("https://2factor.in/API/V1/{$this->apiKey}/SMS/91{$phone}/AUTOGEN/{$this->template}");

        $body = $response->json();

        if (!$response->successful() || ($body['Status'] ?? null) !== 'Success' || empty($body['Details'])) {
            Log::error('2Factor send failed', ['phone' => $phone, 'response' => $body]);
            throw new OtpDeliveryException('Could not send OTP right now, please try again in a bit.');
        }

        Cache::put(self::SESSION_CACHE_PREFIX.$phone, $body['Details'], now()->addMinutes(self::SESSION_TTL_MINUTES));
    }

    /**
     * Ask 2Factor to check a user-entered code against the OTP it sent to this phone.
     */
    public function verify(string $phone, string $otp): bool
    {
        if ($this->apiKey === '') {
            Log::error('2Factor verify failed: 2Factor API key is not configured.');
            throw new OtpDeliveryException('Could not verify OTP right now, please try again in a bit.');
        }

        $sessionId = Cache::get(self::SESSION_CACHE_PREFIX.$phone);

        if (!$sessionId) {
            return false;
        }

        $response = Http::timeout(5)
            ->get("https://2factor.in/API/V1/{$this->apiKey}/SMS/VERIFY/{$sessionId}/{$otp}");

        $body = $response->json();

        if (!$response->successful()) {
            Log::error('2Factor verify request failed', ['phone' => $phone, 'response' => $body]);
            throw new OtpDeliveryException('Could not verify OTP right now, please try again in a bit.');
        }

        $verified = ($body['Status'] ?? null) === 'Success' && ($body['Details'] ?? null) === 'OTP Matched';

        if ($verified) {
            Cache::forget(self::SESSION_CACHE_PREFIX.$phone);
        }

        return $verified;
    }
}
