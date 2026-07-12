<?php

namespace App\Support;

class PhoneNumber
{
    // strips formatting/country code down to a bare 10-digit Indian mobile number
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    public static function isValidIndianMobile(string $normalized): bool
    {
        return (bool) preg_match('/^[6-9]\d{9}$/', $normalized);
    }
}
