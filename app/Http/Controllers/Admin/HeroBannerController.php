<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroBanner;
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
        }

        $heroBanner->delete();

        return redirect()->route('admin.hero-banners.index')->with('status', 'Hero banner deleted.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
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

        $filename = 'banner-'.Str::random(10).'.jpg';
        $directory = public_path('images/hero');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($directory.'/'.$filename, $binary);

        return 'images/hero/'.$filename;
    }
}
