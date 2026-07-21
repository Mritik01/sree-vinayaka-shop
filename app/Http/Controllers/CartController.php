<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function add(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => 'sometimes|integer|min:1|max:10',
            'portion' => 'sometimes|nullable|integer|in:'.implode(',', Product::PORTION_OPTIONS),
        ]);

        if ($product->is_out_of_stock) {
            return response()->json(['ok' => false, 'message' => 'This product is currently out of stock.'], 422);
        }

        $portion = $product->isLoose() ? ($data['portion'] ?? $product->defaultPortion()) : 0;

        if ($product->isLoose() && !in_array($portion, $product->portions ?? [], true)) {
            return response()->json(['ok' => false, 'message' => 'That portion is not available for this product.'], 422);
        }

        $user = $request->user();
        $existing = $user->cart()->where('products.id', $product->id)->first();
        $requestedQuantity = $data['quantity'] ?? 1;

        // same portion already in the cart → accumulate quantity (today's behavior);
        // switching to a different portion replaces it, like a size switcher
        $quantity = ($existing && (int) $existing->pivot->portion === $portion)
            ? min(10, $existing->pivot->quantity + $requestedQuantity)
            : min(10, $requestedQuantity);

        $user->cart()->syncWithoutDetaching([$product->id => ['quantity' => $quantity, 'portion' => $portion]]);
        $this->refreshCouponSnapshot($user);
        ActivityLogger::log('add_to_cart', $product->name, ['product_id' => $product->id]);

        return response()->json(['ok' => true, 'quantity' => $quantity] + $this->cartItemsPayload($user));
    }

    public function updateQuantity(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:10',
            'portion' => 'sometimes|nullable|integer|in:'.implode(',', Product::PORTION_OPTIONS),
        ]);

        $existing = $request->user()->cart()->where('products.id', $product->id)->first();
        if ($product->is_out_of_stock && $existing && $data['quantity'] > (int) $existing->pivot->quantity) {
            return response()->json(['ok' => false, 'message' => 'This product is currently out of stock.'], 422);
        }

        $updates = ['quantity' => $data['quantity']];

        if (array_key_exists('portion', $data) && $product->isLoose()) {
            if (!in_array($data['portion'], $product->portions ?? [], true)) {
                return response()->json(['ok' => false, 'message' => 'That portion is not available for this product.'], 422);
            }
            $updates['portion'] = $data['portion'];
        }

        $user = $request->user();
        $user->cart()->updateExistingPivot($product->id, $updates);
        $this->refreshCouponSnapshot($user);

        return response()->json(['ok' => true] + $this->cartItemsPayload($user));
    }

    public function remove(Request $request, Product $product)
    {
        $user = $request->user();
        $user->cart()->detach($product->id);
        $this->refreshCouponSnapshot($user);

        return response()->json(['ok' => true] + $this->cartItemsPayload($user));
    }

    // keep the redemption's recorded discount honest as the cart changes
    private function refreshCouponSnapshot($user): void
    {
        if (!$user->applied_coupon_id) {
            return;
        }

        $coupon = $user->appliedCoupon;
        $user->redeemedCoupons()->updateExistingPivot($coupon->id, [
            'discount_amount' => $coupon->discountFor($user->cartSubtotal()),
        ]);
    }

    // shared shape for anywhere that needs the full live cart (drawer, cart page) after a mutation
    private function cartItemsPayload($user): array
    {
        $items = $user->cart()->get()
            ->map(fn ($p) => $p->cartRow((int) $p->pivot->portion, (int) $p->pivot->quantity))
            ->values();

        return [
            'items' => $items,
            'count' => $items->sum('quantity'),
            'subtotal' => $items->sum(fn ($i) => $i['price'] * $i['quantity']),
        ];
    }
}
