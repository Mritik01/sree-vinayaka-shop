<?php

namespace App\Support;

// Central place for the "compress large admin uploads automatically" rule: any image at or
// above $thresholdBytes gets re-encoded down toward $targetBytes before it's written to disk.
// Two entry points because the business logo needs its transparency preserved (PNG, resize-only)
// while every other admin upload already ends up as a flat .jpg on disk regardless of what
// format was actually uploaded — so for those it's safe (and far more effective at hitting the
// byte target) to drop quality first, only falling back to shrinking dimensions if quality alone
// can't get there.
class ImageCompressor
{
    public const DEFAULT_THRESHOLD = 400 * 1024; // 400KB — uploads under this are left untouched
    public const DEFAULT_TARGET = 150 * 1024;    // 150KB — how far down we try to bring it

    /**
     * Re-encodes as JPEG (flattening any transparency onto white first), stepping quality and
     * then dimensions down until under $targetBytes or the floor is hit. Used by every upload
     * that already ends up as a flat .jpg on disk (products, categories, hero banners, riders,
     * featured categories, announcements).
     */
    public static function compressToJpeg(string $binary, int $thresholdBytes = self::DEFAULT_THRESHOLD, int $targetBytes = self::DEFAULT_TARGET): string
    {
        if (strlen($binary) < $thresholdBytes) {
            return $binary;
        }

        $src = @imagecreatefromstring($binary);
        if (!$src) {
            return $binary; // not a decodable image — leave it for the caller's own validation
        }

        $width = imagesx($src);
        $height = imagesy($src);

        $flat = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($flat, 255, 255, 255);
        imagefill($flat, 0, 0, $white);
        imagecopy($flat, $src, 0, 0, 0, 0, $width, $height);
        imagedestroy($src);

        $best = self::encodeJpeg($flat, 30);

        foreach ([80, 70, 60, 50, 40, 30] as $quality) {
            $out = self::encodeJpeg($flat, $quality);
            $best = $out;
            if (strlen($out) <= $targetBytes) {
                imagedestroy($flat);

                return $out;
            }
        }

        // quality alone couldn't hit the target — shrink dimensions too, at a fixed moderate
        // quality (further quality cuts on an already-small image just look bad for little gain)
        foreach ([0.8, 0.6, 0.45, 0.3, 0.2] as $scale) {
            $newWidth = max(150, (int) round($width * $scale));
            $newHeight = max(150, (int) round($height * ($newWidth / $width)));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $flat, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $out = self::encodeJpeg($resized, 60);
            imagedestroy($resized);

            $best = $out;
            if (strlen($out) <= $targetBytes) {
                break;
            }
        }

        imagedestroy($flat);

        return $best;
    }

    /**
     * Resize-only pass for images that must keep transparency (the business logo) — never
     * changes format or drops quality, just scales dimensions down until under budget.
     */
    public static function compressPngPreservingAlpha(string $binary, int $thresholdBytes = self::DEFAULT_THRESHOLD, int $targetBytes = self::DEFAULT_TARGET): string
    {
        if (strlen($binary) < $thresholdBytes) {
            return $binary;
        }

        $src = @imagecreatefromstring($binary);
        if (!$src) {
            return $binary;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $best = $binary;

        foreach ([0.85, 0.7, 0.55, 0.4, 0.3] as $scale) {
            $newWidth = max(100, (int) round($width * $scale));
            $newHeight = max(100, (int) round($height * ($newWidth / $width)));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            ob_start();
            imagepng($resized, null, 6);
            $out = ob_get_clean();
            imagedestroy($resized);

            $best = $out;
            if (strlen($out) <= $targetBytes) {
                break;
            }
        }

        imagedestroy($src);

        return $best;
    }

    private static function encodeJpeg($image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);

        return ob_get_clean();
    }
}
