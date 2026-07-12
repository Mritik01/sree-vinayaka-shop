<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function apply(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:40',
            'buy_now_product_id' => 'nullable|integer|exists:products,id',
            'buy_now_quantity' => 'nullable|integer|min:1|max:10',
            'buy_now_portion' => 'nullable|integer|in:'.implode(',', Product::PORTION_OPTIONS),
        ]);

        $user = $request->user();
        $code = strtoupper(trim($data['code']));

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon || !$coupon->is_active) {
            return $this->fail('Invalid coupon code.');
        }

        if ($coupon->isExpired()) {
            return $this->fail('This coupon has expired.');
        }

        if (!$coupon->isAvailableFor($user)) {
            return $this->fail('This coupon is not available for your account.');
        }

        // "Order Now" checkout never touches the real cart (see CheckoutController::show()),
        // so the subtotal has to come from that single product instead of the persisted cart
        if (!empty($data['buy_now_product_id'])) {
            $product = Product::find($data['buy_now_product_id']);
            $portion = $product && $product->isLoose()
                ? (in_array($data['buy_now_portion'] ?? null, $product->portions ?? [], true) ? $data['buy_now_portion'] : $product->defaultPortion())
                : 0;
            $subtotal = $product ? $product->priceForPortion($portion) * ($data['buy_now_quantity'] ?? 1) : 0;
        } else {
            $subtotal = $user->cartSubtotal();
        }

        if ($subtotal === 0) {
            return $this->fail('Add some sweets to your cart first.');
        }

        // re-applying the coupon already on this cart is a no-op success
        if ($user->applied_coupon_id === $coupon->id) {
            $existing = $user->redeemedCoupons()->where('coupons.id', $coupon->id)->first();

            return $this->success($coupon, $existing?->pivot->discount_amount ?? $coupon->discountFor($subtotal));
        }

        if ($user->redeemedCoupons()->where('coupons.id', $coupon->id)->exists()) {
            return $this->fail('You have already used this coupon.');
        }

        if ($coupon->usage_type === 'single_use' && $coupon->redeemers()->exists()) {
            return $this->fail('This coupon has already been used.');
        }

        $discount = $coupon->discountFor($subtotal);

        // swapping coupons: release the one currently on the cart
        if ($user->applied_coupon_id) {
            $user->redeemedCoupons()->detach($user->applied_coupon_id);
        }

        $user->redeemedCoupons()->attach($coupon->id, ['discount_amount' => $discount]);
        $user->forceFill(['applied_coupon_id' => $coupon->id])->save();
        ActivityLogger::log('coupon_applied', $coupon->code);

        return $this->success($coupon, $discount);
    }

    public function remove(Request $request)
    {
        $user = $request->user();

        if ($user->applied_coupon_id) {
            $user->redeemedCoupons()->detach($user->applied_coupon_id);
            $user->forceFill(['applied_coupon_id' => null])->save();
        }

        return response()->json(['ok' => true]);
    }

    private function success(Coupon $coupon, int $discount)
    {
        return response()->json([
            'ok' => true,
            'coupon' => [
                'code' => $coupon->code,
                'description' => $coupon->description,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (int) $coupon->discount_value,
            ],
            'discount' => $discount,
        ]);
    }

    private function fail(string $message)
    {
        return response()->json(['ok' => false, 'message' => $message], 422);
    }
}
