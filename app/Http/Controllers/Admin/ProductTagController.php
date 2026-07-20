<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\ProductTag;
use Illuminate\Http\Request;

class ProductTagController extends Controller
{
    use PaginatesAdminLists;

    public function index(Request $request)
    {
        $query = ProductTag::withCount('products')->orderBy('name');

        $search = trim((string) $request->get('q', ''));
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $data = [
            'tags' => $query->paginate($this->perPage($request))->withQueryString(),
            'search' => $search,
        ];

        return $request->ajax() ? view('admin.product-tags._results', $data) : view('admin.product-tags.index', $data);
    }

    public function create()
    {
        return view('admin.product-tags.create');
    }

    public function store(Request $request)
    {
        ProductTag::create($this->validateData($request));

        return redirect()->route('admin.product-tags.index')->with('status', 'Tag added.');
    }

    public function edit(ProductTag $productTag)
    {
        return view('admin.product-tags.edit', ['tag' => $productTag]);
    }

    public function update(Request $request, ProductTag $productTag)
    {
        $productTag->update($this->validateData($request, $productTag->id));

        return redirect()->route('admin.product-tags.index')->with('status', 'Tag updated.');
    }

    public function destroy(ProductTag $productTag)
    {
        $productTag->delete();

        return redirect()->route('admin.product-tags.index')->with('status', 'Tag deleted.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:60|unique:product_tags,name'.($ignoreId ? ",{$ignoreId}" : ''),
        ]);
    }
}
