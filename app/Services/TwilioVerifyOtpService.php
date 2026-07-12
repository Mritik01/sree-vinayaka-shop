<?php

namespace App\Services;

use App\Exceptions\OtpDeliveryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioVerifyOtpService
{
    protected string $accountSid;
    protected string $authToken;
    protected string $serviceSid;

    public function __construct()
    {
        $this->accountSid = (string) config('services.twilio.account_sid');
        $this->authToken = (string) config('services.twilio.auth_token');
        $this->serviceSid = (string) config('services.twilio.verify_service_sid');
    }

    /**
     * Ask Twilio Verify to generate and text an OTP to the given phone number.
     * Twilio manages the OTP itself — there is nothing for us to cache besides
     * the phone number, which VerificationCheck uses to look it back up.
     */
    public function send(string $phone): void
    {
        if ($this->accountSid === '' || $this->authToken === '' || $this->serviceSid === '') {
            Log::error('Twilio Verify send failed: Twilio credentials are not configured.');
            throw new OtpDeliveryException('Could not send OTP right now, please try again in a bit.');
        }

        $response = Http::withBasicAuth($this->accountSid, $this->authToken)
            ->asForm()
            ->timeout(5)
            ->post("https://verify.twilio.com/v2/Services/{$this->serviceSid}/Verifications", [
                'To' => "+91{$phone}",
                'Channel' => 'sms',
            ]);

        if (!$response->successful()) {
            Log::error('Twilio Verify send failed', ['phone' => $phone, 'response' => $response->json()]);
            throw new OtpDeliveryException('Could not send OTP right now, please try again in a bit.');
        }
    }

    /**
     * Ask Twilio to check a user-entered code against the OTP it sent to this phone.
     */
    public function verify(string $phone, string $otp): bool
    {
        if ($this->accountSid === '' || $this->authToken === '' || $this->serviceSid === '') {
            Log::error('Twilio Verify check failed: Twilio credentials are not configured.');
            throw new OtpDeliveryException('Could not verify OTP right now, please try again in a bit.');
        }

        $response = Http::withBasicAuth($this->accountSid, $this->authToken)
            ->asForm()
            ->timeout(5)
            ->post("https://verify.twilio.com/v2/Services/{$this->serviceSid}/VerificationCheck", [
                'To' => "+91{$phone}",
                'Code' => $otp,
            ]);

        if (!$response->successful()) {
            Log::error('Twilio Verify check request failed', ['phone' => $phone, 'response' => $response->json()]);
            throw new OtpDeliveryException('Could not verify OTP right now, please try again in a bit.');
        }

        return ($response->json()['status'] ?? null) === 'approved';
    }
}
