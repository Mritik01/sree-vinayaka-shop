<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class FestivalSpecialController extends Controller
{
    public function index()
    {
        return view('admin.festival-special.index', [
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

        Product::whereIn('id', $selected)->update(['is_festival_special' => true]);
        Product::whereNotIn('id', $selected)->update(['is_festival_special' => false]);

        return redirect()->route('admin.festival-special.index')->with('status', 'Festival Special updated.');
    }
}
