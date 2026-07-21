<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use PaginatesAdminLists;

    // was a hardcoded 7-item list — now sourced from the same admin-managed Category records
    // that power the mobile Categories panel (Admin → Categories), so a newly created category
    // shows up here immediately with no code change. Order matches that screen's drag-to-reorder.
    private function categoryOptions(): array
    {
        return Category::where('is_active', true)->orderBy('sort_order')->pluck('name')->all();
    }

    public function index(Request $request)
    {
        $query = Product::orderBy('sort_order');

        $stockFilter = in_array($request->get('stock'), ['in_stock', 'out_of_stock'], true) ? $request->get('stock') : '';
        if ($stockFilter !== '') {
            $query->where('is_out_of_stock', $stockFilter === 'out_of_stock');
        }

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('tag', 'like', "%{$search}%")
                    ->orWhere('search_tags_flat', 'like', '%'.Str::lower($search).'%');
            });
        }

        $data = [
            'products' => $query->paginate($this->perPage($request))->withQueryString(),
            'search' => $search,
            'stockFilter' => $stockFilter,
            'outOfStockCount' => Product::where('is_out_of_stock', true)->count(),
        ];

        return $request->ajax() ? view('admin.products._results', $data) : view('admin.products.index', $data);
    }

    // quick toggle from the product list row — same shape as CategoryController::toggle() /
    // HeroBannerController::toggle() (see resources/js/admin.js: window.settingToggle())
    public function toggle(Product $product)
    {
        $product->update(['is_out_of_stock' => !$product->is_out_of_stock]);

        return response()->json(['ok' => true, 'value' => $product->is_out_of_stock]);
    }

    // live JSON typeahead for the order-detail "Add Product" modal. Returns each match with its
    // buyable variants (portionPriceList) so the modal can offer a portion dropdown + live price;
    // the actual price is always re-derived server-side in Admin\OrderController::addItems().
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['products' => []]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], mb_strtolower($q)).'%';

        $products = Product::where(function ($query) use ($like) {
                $query->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(search_tags_flat) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(category) LIKE ?', [$like]);
            })
            ->orderBy('sort_order')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image' => asset($p->image),
                'category' => $p->category,
                'is_loose' => $p->isLoose(),
                'portions' => $p->portionPriceList(),
            ]);

        return response()->json(['products' => $products]);
    }

    public function create()
    {
        return view('admin.products.create', [
            'categories' => $this->categoryOptions(),
            'allCategories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'allTags' => ProductTag::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,jpg,png,webp,gif|max:4096']);
        $data = $this->validateData($request);
        $data['image'] = $this->storeImage($request);

        $product = Product::create($data);
        $product->categories()->sync($request->input('categories', []));
        $product->tags()->sync($request->input('tags', []));

        return redirect()->route('admin.products.index')->with('status', 'Product added.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->categoryOptions(),
            'allCategories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'allTags' => ProductTag::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request);

        if ($request->hasFile('image')) {
            $oldImage = $product->image;
            $data['image'] = $this->storeImage($request);

            if ($oldImage && $oldImage !== $data['image']) {
                @unlink(public_path($oldImage));
            }
        }

        $product->update($data);
        $product->categories()->sync($request->input('categories', []));
        $product->tags()->sync($request->input('tags', []));

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            @unlink(public_path($product->image));
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    private function validateData(Request $request): array
    {
        $portionOptions = implode(',', Product::PORTION_OPTIONS);
        $discountEnabled = $request->boolean('discount_enabled');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'required|string|in:'.implode(',', $this->categoryOptions()),
            'description' => 'nullable|string',
            'type' => 'required|in:piece,loose',
            'price' => 'required|integer|min:1',
            'weight' => 'required_if:type,piece|nullable|string|max:50',
            'portions' => 'required_if:type,loose|array|min:1',
            'portions.*' => 'integer|in:'.$portionOptions,
            'discount_enabled' => 'sometimes|boolean',
            'discount_type' => 'required_if:discount_enabled,1|nullable|in:percentage,flat',
            'discount_value' => [
                'required_if:discount_enabled,1', 'nullable', 'integer', 'min:1',
                function ($attribute, $value, $fail) use ($request, $discountEnabled) {
                    if (!$discountEnabled || $value === null) {
                        return;
                    }
                    if ($request->input('discount_type') === 'percentage' && $value > 100) {
                        $fail('Discount percentage cannot exceed 100.');
                    }
                    if ($request->input('discount_type') === 'flat' && $value >= (int) $request->input('price')) {
                        $fail('Flat discount must be less than the price.');
                    }
                },
            ],
            'tag' => 'nullable|string|max:50',
            'search_tags' => 'nullable|array|max:30',
            // nullable per-item: an accidental blank entry (stray hidden input, JS disabled)
            // should just get silently dropped — see Product::booted()'s saving hook — rather
            // than fail the whole form over one empty tag
            'search_tags.*' => 'nullable|string|max:40',
            'color' => 'required|string|max:20',
            'sort_order' => 'required|integer|min:0',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:product_tags,id',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:4096',
            // CSS object-position, e.g. "50% 30%" — set by dragging the photo in the live
            // preview; regex keeps it to plain percentages since it's echoed into an inline style
            'image_position' => ['nullable', 'regex:/^\d{1,3}% \d{1,3}%$/'],
        ]);

        if ($data['type'] === 'loose') {
            // checkbox values arrive as strings ("250", not 250) — cast before sorting/saving
            // so downstream strict comparisons (in_array(..., true) in CartController etc.) work
            $data['portions'] = array_map('intval', $data['portions']);
            sort($data['portions']);
            $data['weight'] = implode('/', array_map(fn ($g) => Product::portionLabel($g), $data['portions']));
        } else {
            $data['portions'] = null;
        }

        if (preg_match('/^(\d{1,3})%\s*(\d{1,3})%$/', $data['image_position'] ?? '', $m)) {
            $data['image_position'] = max(0, min(100, (int) $m[1])).'% '.max(0, min(100, (int) $m[2])).'%';
        } else {
            $data['image_position'] = '50% 50%';
        }

        if (!$discountEnabled) {
            $data['discount_type'] = null;
            $data['discount_value'] = null;
        }

        // checkbox — absent from the request entirely when unchecked, so boolean() (not the
        // validate() array, which would just omit the key) is what makes unchecking actually
        // persist as false rather than leaving the previous value untouched
        $data['is_out_of_stock'] = $request->boolean('is_out_of_stock');

        unset($data['discount_enabled'], $data['categories'], $data['tags']);

        return $data;
    }

    private function storeImage(Request $request): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        $filename = Str::slug($request->input('name')).'-'.Str::random(6).'.'.($file->extension() ?: 'jpg');
        $file->move(public_path('images/products'), $filename);

        return 'images/products/'.$filename;
    }
}
