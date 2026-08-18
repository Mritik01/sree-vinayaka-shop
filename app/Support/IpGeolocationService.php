<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// resolves an IP to a coarse city/country using ip-api.com's free tier (45 req/min, no API key,
// HTTP-only on the free tier — fine here since this is a server-to-server lookup, not a browser
// request, so there's no mixed-content concern; swapping to the paid HTTPS tier later is a
// one-line URL change). Same defensive shape as TwoFactorOtpService/AiSensyService: never throws,
// never blocks the caller — a failed/slow lookup just means no location gets stored.
class IpGeolocationService
{
    /**
     * @return array{city: ?string, country: ?string}|null
     */
    public function lookup(string $ip): ?array
    {
        // private/loopback/reserved ranges (127.0.0.1, ::1, LAN IPs — the normal case on local
        // dev) can't be geolocated at all; skip the HTTP call entirely rather than waste it
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,city',
            ]);

            if (!$response->successful() || $response->json('status') !== 'success') {
                return null;
            }

            return [
                'city' => $response->json('city') ?: null,
                'country' => $response->json('country') ?: null,
            ];
        } catch (\Throwable $e) {
            Log::debug('IpGeolocationService lookup failed: '.$e->getMessage());

            return null;
        }
    }
}
