<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Centralizes branding/theme settings the admin previously had no control over: the business
// logo (used everywhere on the customer-facing site) and the customer website's color theme.
// Business Mobile Number also lives here now (moved from admin/configuration.blade.php) — but
// still saves through DashboardController::updateBusinessInfoSettings(), unchanged; this is a UI
// relocation only, not a rewiring of that field's read side (see AppServiceProvider's
// $businessPhone share, untouched).
class CustomizationController extends Controller
{
    // easy-to-change-in-code constants rather than admin-facing settings — no other upload in
    // this app exposes its own size/resolution limits as a setting either
    private const MAX_LOGO_BYTES = 2 * 1024 * 1024; // 2MB
    private const MIN_LOGO_DIMENSION = 200; // px, raster only — SVG is resolution-independent
    private const MAX_STORED_DIMENSION = 1024; // px — larger raster uploads are downscaled to this

    // set by storeRasterLogo()/storeSvgLogo() when they return null, so updateLogo() can surface
    // a specific reason instead of one generic message
    private ?string $lastLogoError = null;

    public function index()
    {
        $settings = ShopSetting::current();

        return view('admin.customization.index', [
            'settings' => $settings,
            'themes' => config('customer_themes'),
        ]);
    }

    // handles BOTH upload paths in one endpoint — whichever field is present tells us which:
    // 'cropped_image' (Cropper.js base64 data URL) for PNG/JPG/JPEG, or 'svg_file' (a plain
    // multipart file input — Cropper.js/GD can't crop or decode vector data) for SVG.
    public function updateLogo(Request $request)
    {
        if ($request->filled('cropped_image')) {
            $path = $this->storeRasterLogo($request);
        } elseif ($request->hasFile('svg_file')) {
            $path = $this->storeSvgLogo($request);
        } else {
            return back()->withErrors(['logo' => 'Please choose a logo image to upload.']);
        }

        if (!$path) {
            return back()->withErrors(['logo' => $this->lastLogoError ?? 'That file could not be used as a logo — please try a different one.']);
        }

        $this->deleteCurrentLogoFile();
        ShopSetting::current()->update(['business_logo_path' => $path]);

        return back()->with('status', 'Business logo updated.');
    }

    public function destroyLogo()
    {
        $this->deleteCurrentLogoFile();
        ShopSetting::current()->update(['business_logo_path' => null]);

        return back()->with('status', 'Business logo removed — the default logo is now shown.');
    }

    public function updateTheme(Request $request)
    {
        $data = $request->validate([
            'customer_theme' => 'required|in:'.implode(',', array_keys(config('customer_themes'))),
        ]);

        ShopSetting::current()->update($data);

        return back()->with('status', 'Customer website theme updated.');
    }

    // only unlinks files this feature manages — the seeded default images/logo-circle.png is a
    // shared static asset and must never be deleted by this code path (same scoping precedent as
    // HeroBannerController/CategoryController's own cleanup)
    private function deleteCurrentLogoFile(): void
    {
        $current = ShopSetting::current()->business_logo_path;
        if ($current && str_starts_with($current, 'images/branding/logo-')) {
            @unlink(public_path($current));
        }
    }

    // same hardened base64 flow as HeroBannerController::storeCroppedImage() — Cropper.js hands
    // over a data URL in a hidden field, regex-check the label, then verify the decoded bytes
    // really are an image before writing. Unlike hero banners this always re-encodes as PNG
    // (never re-uses the source's own encoding) so an uploaded logo's transparency survives
    // regardless of what the admin cropped from — matches FeaturedCategoryController's precedent.
    private function storeRasterLogo(Request $request): ?string
    {
        $dataUrl = $request->input('cropped_image');

        if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/', $dataUrl, $matches)) {
            $this->lastLogoError = 'Unsupported image format.';
            return null;
        }

        $binary = base64_decode($matches[2]);
        if ($binary === false || strlen($binary) > self::MAX_LOGO_BYTES) {
            $this->lastLogoError = 'That image is too large (max 2MB).';
            return null;
        }

        $size = @getimagesizefromstring($binary);
        if ($size === false) {
            $this->lastLogoError = 'That file is not a valid image.';
            return null;
        }
        if ($size[0] < self::MIN_LOGO_DIMENSION || $size[1] < self::MIN_LOGO_DIMENSION) {
            $this->lastLogoError = 'Please upload an image at least '.self::MIN_LOGO_DIMENSION.'×'.self::MIN_LOGO_DIMENSION.'px.';
            return null;
        }

        $directory = public_path('images/branding');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = 'logo-'.Str::random(10).'.png';
        $this->writeOptimizedPng($binary, $directory.'/'.$filename);

        return 'images/branding/'.$filename;
    }

    // downscales to MAX_STORED_DIMENSION if needed (the "automatic optimization" requirement,
    // same technique as HeroBannerController::generateMobileVariant()) and always re-encodes as
    // PNG with alpha preserved, so a transparent source logo stays transparent
    private function writeOptimizedPng(string $binary, string $destPath): void
    {
        $src = imagecreatefromstring($binary);
        $width = imagesx($src);
        $height = imagesy($src);

        if ($width <= self::MAX_STORED_DIMENSION && $height <= self::MAX_STORED_DIMENSION) {
            imagesavealpha($src, true);
            ob_start();
            imagepng($src, null, 6);
            $out = ob_get_clean();
            imagedestroy($src);
        } else {
            $scale = self::MAX_STORED_DIMENSION / max($width, $height);
            $targetWidth = (int) round($width * $scale);
            $targetHeight = (int) round($height * $scale);

            $dst = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            ob_start();
            imagepng($dst, null, 6);
            $out = ob_get_clean();

            imagedestroy($src);
            imagedestroy($dst);
        }

        // uploads still at/above 400KB after the dimension cap above get shrunk further toward
        // 150KB, resize-only so the logo's transparency survives — see ImageCompressor
        $out = ImageCompressor::compressPngPreservingAlpha($out);

        file_put_contents($destPath, $out);
    }

    // SVG can't go through Cropper.js/GD at all — it's XML text, not a raster format either can
    // decode. Validated as text instead: reject anything containing a <script> tag, inline event
    // handlers, or javascript: URIs. This is defense in depth, not the only protection — the
    // logo is always rendered via <img src="...">, never inlined as raw <svg> markup, and browsers
    // don't execute scripts inside an <img>-loaded SVG regardless, which is what actually
    // neutralizes the risk. Stored as-is: vector art has no fixed resolution to downscale.
    private function storeSvgLogo(Request $request): ?string
    {
        $request->validate(['svg_file' => 'required|file|mimes:svg|max:'.(self::MAX_LOGO_BYTES / 1024)]);

        $file = $request->file('svg_file');
        $contents = file_get_contents($file->getRealPath());

        if (!str_contains($contents, '<svg')) {
            $this->lastLogoError = 'That file is not a valid SVG.';
            return null;
        }
        if (preg_match('/<script|on\w+\s*=|javascript:/i', $contents)) {
            $this->lastLogoError = 'That SVG contains disallowed content (scripts/event handlers) and was rejected.';
            return null;
        }

        $directory = public_path('images/branding');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = 'logo-'.Str::random(10).'.svg';
        file_put_contents($directory.'/'.$filename, $contents);

        return 'images/branding/'.$filename;
    }
}
