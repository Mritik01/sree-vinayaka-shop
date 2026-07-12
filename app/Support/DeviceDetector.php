<?php

namespace App\Support;

class DeviceDetector
{
    public static function parse(?string $userAgent): array
    {
        $ua = $userAgent ?? '';

        return [
            'device_type' => self::deviceType($ua),
            'browser' => self::browser($ua),
            'platform' => self::platform($ua),
        ];
    }

    private static function deviceType(string $ua): string
    {
        if (preg_match('/iPad|Tablet|Nexus 7|Nexus 9|Nexus 10|SM-T/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private static function browser(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/EdgiOS|EdgA|Edg\//i', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/FBAV|FBAN/i', $ua) => 'Facebook In-App',
            (bool) preg_match('/Instagram/i', $ua) => 'Instagram In-App',
            (bool) preg_match('/CriOS|Chrome\//i', $ua) => 'Chrome',
            (bool) preg_match('/FxiOS|Firefox\//i', $ua) => 'Firefox',
            (bool) preg_match('/Version\/.*Safari/i', $ua) => 'Safari',
            (bool) preg_match('/Safari/i', $ua) => 'Safari',
            (bool) preg_match('/MSIE|Trident/i', $ua) => 'Internet Explorer',
            default => 'Unknown',
        };
    }

    private static function platform(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'iOS',
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/Windows/i', $ua) => 'Windows',
            (bool) preg_match('/Macintosh|Mac OS X/i', $ua) => 'macOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => 'Unknown',
        };
    }
}
