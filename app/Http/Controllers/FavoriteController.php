<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Product $product)
    {
        $result = $request->user()->favorites()->toggle($product->id);
        $favorited = count($result['attached']) > 0;

        ActivityLogger::log($favorited ? 'favorite_add' : 'favorite_remove', $product->name, ['product_id' => $product->id]);

        return response()->json([
            'ok' => true,
            'favorited' => $favorited,
        ]);
    }
}
