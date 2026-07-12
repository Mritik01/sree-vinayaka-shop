<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use PaginatesAdminLists;

    public const CATEGORIES = ['Sweets', 'Namkeen', 'Desi Ghee', 'Cookies', 'Mathi', 'Dry Fruits', 'Syrup'];

    public function index(Request $request)
    {
        $query = Product::orderBy('sort_order');

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('tag', 'like', "%{$search}%");
            });
        }

        $data = [
            'products' => $query->paginate($this->perPage($request))->withQueryString(),
            'search' => $search,
        ];

        return $request->ajax() ? view('admin.products._results', $data) : view('admin.products.index', $data);
    }

    public function create()
    {
        return view('admin.products.create', ['categories' => self::CATEGORIES]);
    }

    public function store(Request $request)
    {
        $request->validate(['image' => 'required|image|max:4096']);
        $data = $this->validateData($request);
        $data['image'] = $this->storeImage($request);

        Product::create($data);

        return redirect()->route('admin.products.index')->with('status', 'Product added.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', ['product' => $product, 'categories' => self::CATEGORIES]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request);

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request);
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    private function validateData(Request $request): array
    {
        $portionOptions = implode(',', Product::PORTION_OPTIONS);
        $discountEnabled = $request->boolean('discount_enabled');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'required|string|in:'.implode(',', self::CATEGORIES),
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
            'color' => 'required|string|max:20',
            'sort_order' => 'required|integer|min:0',
            'image' => 'nullable|image|max:4096',
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
        unset($data['discount_enabled']);

        return $data;
    }

    private function storeImage(Request $request): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        $filename = Str::slug($request->input('name')).'-'.Str::random(6).'.'.$file->getClientOriginalExtension();
        $file->move(public_path('images/products'), $filename);

        return 'images/products/'.$filename;
    }
}
