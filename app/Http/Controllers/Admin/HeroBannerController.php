<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroBanner;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HeroBannerController extends Controller
{
    public function index()
    {
        return view('admin.hero-banners.index', [
            'banners' => HeroBanner::orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.hero-banners.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $imagePath = $this->storeCroppedImage($request);
        if (!$imagePath) {
            return back()->withInput()->withErrors(['cropped_image' => 'Please choose a banner image.']);
        }
        $data['image_path'] = $imagePath;

        HeroBanner::create($data);

        return redirect()->route('admin.hero-banners.index')->with('status', 'Hero banner added.');
    }

    public function edit(HeroBanner $heroBanner)
    {
        return view('admin.hero-banners.edit', ['banner' => $heroBanner]);
    }

    public function update(Request $request, HeroBanner $heroBanner)
    {
        $data = $this->validateData($request);

        if ($request->filled('cropped_image')) {
            $imagePath = $this->storeCroppedImage($request);
            if ($imagePath) {
                // only unlink files this feature manages — the seeded legacy hero-N.jpg images
                // are shared static assets and may be referenced elsewhere
                if ($heroBanner->image_path && str_starts_with($heroBanner->image_path, 'images/hero/banner-')) {
                    @unlink(public_path($heroBanner->image_path));
                    @unlink(public_path(self::mobileVariantPath($heroBanner->image_path)));
                }
                $data['image_path'] = $imagePath;
            }
        }

        $heroBanner->update($data);

        return redirect()->route('admin.hero-banners.index')->with('status', 'Hero banner updated.');
    }

    public function toggle(HeroBanner $heroBanner)
    {
        $heroBanner->update(['is_active' => !$heroBanner->is_active]);

        return response()->json(['ok' => true, 'value' => $heroBanner->is_active]);
    }

    public function destroy(HeroBanner $heroBanner)
    {
        if ($heroBanner->image_path && str_starts_with($heroBanner->image_path, 'images/hero/banner-')) {
            @unlink(public_path($heroBanner->image_path));
            @unlink(public_path(self::mobileVariantPath($heroBanner->image_path)));
        }

        $heroBanner->delete();

        return redirect()->route('admin.hero-banners.index')->with('status', 'Hero banner deleted.');
    }

    // derives the companion mobile-resized variant's path from the full-size image's path —
    // a plain filename convention (no new DB column) so hero-slider.blade.php can look it up
    // for srcset without any schema change. Public/static so both this controller and the view
    // agree on the exact same convention.
    public static function mobileVariantPath(string $imagePath): string
    {
        return preg_replace('/\.(jpe?g|png)$/i', '-mobile.jpg', $imagePath);
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:150',
            'eyebrow' => 'nullable|string|max:100',
            'subtitle' => 'nullable|string|max:200',
            'button_text' => 'nullable|string|max:60',
            'button_url' => 'nullable|string|max:300',
            'sort_order' => 'required|integer|min:0',
            'cropped_image' => 'nullable|string',
        ]);

        // same scheme allowlist idea as AnnouncementController — the URL lands in an href,
        // so only http(s), same-site paths, and in-page anchors are allowed
        if (!empty($data['button_url']) && !preg_match('#^(https?://|/|\#)#i', $data['button_url'])) {
            $data['button_url'] = '#'.ltrim($data['button_url'], '#');
        }

        $data['is_active'] = $request->boolean('is_active');
        unset($data['cropped_image']);

        return $data;
    }

    // same hardened base64 flow as CategoryController::storeCroppedImage() — Cropper.js hands
    // over a data URL in a hidden field; regex-check the label, then verify the decoded bytes
    // really are an image before writing (fixed .jpg extension, so never executable)
    private function storeCroppedImage(Request $request): ?string
    {
        if (!$request->filled('cropped_image')) {
            return null;
        }

        $dataUrl = $request->input('cropped_image');

        if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/', $dataUrl, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2]);

        if ($binary === false || @getimagesizefromstring($binary) === false) {
            return null;
        }

        // uploads at/above 400KB get re-encoded down toward 150KB — see ImageCompressor. Applied
        // before generateMobileVariant() below too, so that companion image starts from the
        // already-compressed source.
        $binary = ImageCompressor::compressToJpeg($binary);

        $filename = 'banner-'.Str::random(10).'.jpg';
        $directory = public_path('images/hero');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($directory.'/'.$filename, $binary);

        $imagePath = 'images/hero/'.$filename;
        $this->generateMobileVariant($binary, $directory.'/'.basename(self::mobileVariantPath($imagePath)));

        return $imagePath;
    }

    // a smaller companion image so phones don't download the same ~1700px-wide crop desktop
    // gets — the hero banner is this site's LCP element, and that was measured costing real
    // seconds on slow mobile connections. Best-effort: if GD can't decode/write for any reason,
    // the full-size image_path alone still works fine (see the file_exists guard in
    // hero-slider.blade.php), so this never blocks the upload itself.
    private function generateMobileVariant(string $binary, string $destPath): void
    {
        $targetWidth = 800;

        $src = @imagecreatefromstring($binary);
        if (!$src) {
            return;
        }

        $width = imagesx($src);
        $height = imagesy($src);

        if ($width <= $targetWidth) {
            imagejpeg($src, $destPath, 82);
            imagedestroy($src);
            return;
        }

        $targetHeight = (int) round($height * ($targetWidth / $width));
        $dst = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagejpeg($dst, $destPath, 82);

        imagedestroy($src);
        imagedestroy($dst);
    }
}
