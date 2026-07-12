<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class BestsellerController extends Controller
{
    public function index()
    {
        return view('admin.bestsellers.index', [
            'products' => Product::orderBy('category')->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $selected = $data['product_ids'] ?? [];

        Product::whereIn('id', $selected)->update(['is_bestseller' => true]);
        Product::whereNotIn('id', $selected)->update(['is_bestseller' => false]);

        return redirect()->route('admin.bestsellers.index')->with('status', "Thuthibari's Favourites updated.");
    }
}
