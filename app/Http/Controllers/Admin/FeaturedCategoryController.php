<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedCategory;
use App\Models\ProductTag;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeaturedCategoryController extends Controller
{
    public function index()
    {
        return view('admin.featured-categories.index', [
            // reachable_products_count lets the list flag a category whose tags exist but have
            // no products tagged with them yet — otherwise it silently never appears on the
            // homepage (which drops any tile with zero reachable products) with no clue why.
            // Counted as unique products across all mapped tags (not summed per-tag), since a
            // product carrying two mapped tags must only count once — same union the homepage
            // query itself resolves to.
            'categories' => FeaturedCategory::withCount('tags')
                ->with('tags.products:id')
                ->orderBy('sort_order')
                ->get()
                ->each(fn ($c) => $c->reachable_products_count = $c->tags->flatMap->products->pluck('id')->unique()->count()),
        ]);
    }

    public function create()
    {
        return view('admin.featured-categories.create', ['allTags' => ProductTag::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $imagePath = $this->storeCroppedImage($request);
        if (!$imagePath) {
            return back()->withInput()->withErrors(['cropped_image' => 'Please choose an icon image.']);
        }
        $data['image_path'] = $imagePath;

        $category = FeaturedCategory::create($data);
        $category->tags()->sync($request->input('tags', []));

        return redirect()->route('admin.featured-categories.index')->with('status', 'Featured category added.');
    }

    public function edit(FeaturedCategory $featuredCategory)
    {
        return view('admin.featured-categories.edit', [
            'category' => $featuredCategory,
            'allTags' => ProductTag::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, FeaturedCategory $featuredCategory)
    {
        $data = $this->validateData($request);

        if ($request->filled('cropped_image')) {
            $imagePath = $this->storeCroppedImage($request);
            if ($imagePath) {
                if ($featuredCategory->image_path) {
                    @unlink(public_path($featuredCategory->image_path));
                }
                $data['image_path'] = $imagePath;
            }
        }

        $featuredCategory->update($data);
        $featuredCategory->tags()->sync($request->input('tags', []));

        return redirect()->route('admin.featured-categories.index')->with('status', 'Featured category updated.');
    }

    public function toggle(FeaturedCategory $featuredCategory)
    {
        $featuredCategory->update(['is_active' => !$featuredCategory->is_active]);

        return response()->json(['ok' => true, 'value' => $featuredCategory->is_active]);
    }

    public function destroy(FeaturedCategory $featuredCategory)
    {
        if ($featuredCategory->image_path) {
            @unlink(public_path($featuredCategory->image_path));
        }

        $featuredCategory->delete();

        return redirect()->route('admin.featured-categories.index')->with('status', 'Featured category deleted.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'sort_order' => 'required|integer|min:0',
            'cropped_image' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:product_tags,id',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        unset($data['cropped_image'], $data['tags']);

        return $data;
    }

    // same hardened flow as CategoryController/HeroBannerController's storeCroppedImage() —
    // deliberately kept as PNG (not re-encoded to JPEG) so the icon art's transparency survives,
    // matching the reference's clean flat-icon look
    private function storeCroppedImage(Request $request): ?string
    {
        if (!$request->filled('cropped_image')) {
            return null;
        }

        $dataUrl = $request->input('cropped_image');

        if (!preg_match('/^data:image\/png;base64,(.+)$/', $dataUrl, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[1]);

        if ($binary === false || @getimagesizefromstring($binary) === false) {
            return null;
        }

        // uploads at/above 400KB get re-encoded down toward 150KB — resize-only, since this stays
        // PNG to keep the icon's transparency (see class comment above)
        $binary = ImageCompressor::compressPngPreservingAlpha($binary);

        $filename = 'featured-'.Str::random(10).'.png';
        $directory = public_path('images/featured-categories');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($directory.'/'.$filename, $binary);

        return 'images/featured-categories/'.$filename;
    }
}
