<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request)
    {
        $query = Category::withCount('products')->orderBy('sort_order');

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $data = [
            'categories' => $query->paginate($this->perPage($request))->withQueryString(),
            'search' => $search,
        ];

        return $request->ajax() ? view('admin.categories._results', $data) : view('admin.categories.index', $data);
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['image_path'] = $this->storeCroppedImage($request);

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('status', 'Category added.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateData($request, $category->id);

        if ($request->filled('cropped_image')) {
            if ($category->image_path) {
                @unlink(public_path($category->image_path));
            }
            $data['image_path'] = $this->storeCroppedImage($request);
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function toggle(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        return response()->json(['ok' => true, 'value' => $category->is_active]);
    }

    public function destroy(Category $category)
    {
        if ($category->image_path) {
            @unlink(public_path($category->image_path));
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name'.($ignoreId ? ",{$ignoreId}" : ''),
            'sort_order' => 'required|integer|min:0',
            'cropped_image' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        unset($data['cropped_image']);

        return $data;
    }

    // the crop/zoom/reposition happens client-side in Cropper.js (admin/categories/_form.blade.php)
    // before the native multipart form submits — it hands over a plain base64 JPEG data URL in a
    // hidden field rather than a raw file input, so this just has to decode and write it
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

        // the data-URL prefix regex only checks a client-supplied label, not the actual bytes —
        // confirm the decoded payload is really a decodable image before it's ever written to
        // the public webroot (the extension is a fixed literal above, so this can't become an
        // executable file, but it could still be arbitrary junk without this check)
        if ($binary === false || @getimagesizefromstring($binary) === false) {
            return null;
        }

        // uploads at/above 400KB get re-encoded down toward 150KB — see ImageCompressor
        $binary = ImageCompressor::compressToJpeg($binary);

        $filename = 'category-'.Str::random(10).'.jpg';
        $directory = public_path('images/categories');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($directory.'/'.$filename, $binary);

        return 'images/categories/'.$filename;
    }
}
