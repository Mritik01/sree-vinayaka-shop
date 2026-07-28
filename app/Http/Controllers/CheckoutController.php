<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\RewardService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

class CheckoutController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $settings = ShopSetting::current();

        // "Order Now" on a product card links here with ?buy_now=<id> — checkout shows ONLY that
        // one item, completely bypassing the customer's real cart (never read, never modified).
        $buyNowProductId = $request->integer('buy_now') ?: null;
        $buyNowQuantity = $buyNowProductId ? max(1, min(10, $request->integer('qty', 1))) : null;
        $buyNowPortion = null;

        if ($buyNowProductId) {
            $product = Product::find($buyNowProductId);

            if (!$product) {
                return redirect('/cart')->with('status', 'That product could not be found.');
            }

            $buyNowPortion = $product->isLoose()
                ? (in_array($request->integer('portion'), $product->portions ?? [], true) ? $request->integer('portion') : $product->defaultPortion())
                : 0;

            $cartItemsForJs = collect([$product->cartRow($buyNowPortion, $buyNowQuantity)]);
        } else {
            $cartProducts = $user->cart()->get();

            if ($cartProducts->isEmpty()) {
                return redirect('/cart')->with('status', 'Your cart is empty — add something sweet before checking out.');
            }

            $cartItemsForJs = $cartProducts->map(fn ($p) => $p->cartRow((int) $p->pivot->portion, (int) $p->pivot->quantity));
        }

        $appliedCoupon = null;
        if ($user->applied_coupon_id && ($coupon = $user->appliedCoupon)) {
            $appliedCoupon = [
                'code' => $coupon->code,
                'description' => $coupon->description,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (int) $coupon->discount_value,
            ];
        }

        // coupons an admin assigned to this customer specifically — offered automatically here
        // instead of making them type the code. Already-redeemed or already-applied ones are
        // excluded so this list only ever shows coupons actually worth offering right now.
        $redeemedCouponIds = $user->redeemedCoupons()->pluck('coupons.id');
        $availableCoupons = $user->assignedCoupons()
            ->where('is_active', true)
            ->where('expires_at', '>=', now())
            ->whereNotIn('coupons.id', $redeemedCouponIds)
            ->where('coupons.id', '!=', $user->applied_coupon_id ?? 0)
            ->get()
            ->map(fn ($coupon) => [
                'code' => $coupon->code,
                'description' => $coupon->description,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (int) $coupon->discount_value,
            ])
            ->values();

        $addresses = $user->addresses->map(function ($address) use ($settings) {
            $distanceKm = ($address->latitude !== null && $address->longitude !== null)
                ? round($settings->distanceFromShopKm($address->latitude, $address->longitude), 2)
                : null;

            return [
                'id' => $address->id,
                'label' => $address->label,
                'address_line' => $address->address_line,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'is_default' => $address->is_default,
                'distance_km' => $distanceKm,
                'within_range' => !$settings->restrict_delivery_area
                    || ($distanceKm !== null && $distanceKm <= $settings->delivery_radius_km),
            ];
        })->values();

        return view('checkout', [
            'cartItemsForJs' => $cartItemsForJs,
            'appliedCoupon' => $appliedCoupon,
            'availableCoupons' => $availableCoupons,
            'addresses' => $addresses,
            'defaultName' => $user->name,
            'defaultPhone' => $user->phone,
            'reward' => RewardService::statusFor($user),
            'buyNowProductId' => $buyNowProductId,
            'buyNowQuantity' => $buyNowQuantity,
            'buyNowPortion' => $buyNowPortion,
        ]);
    }

    public function store(Request $request)
    {
        if (\App\Services\ImpersonationService::active()) {
            return response()->json([
                'ok' => false,
                'message' => 'Placing orders is disabled while viewing this account as an admin.',
            ], 403);
        }

        $settings = ShopSetting::current();

        if (!$settings->accepting_orders) {
            return response()->json([
                'ok' => false,
                'message' => "We're not accepting online orders right now. Please check back soon.",
            ], 422);
        }

        if ($settings->high_demand_mode === 'stop') {
            return response()->json([
                'ok' => false,
                'message' => $settings->highDemandStopMessage(),
            ], 422);
        }

        $data = $request->validate([
            'address_id' => 'nullable|integer',
            'delivery_address' => 'required_without:address_id|nullable|string|min:10|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'save_address' => 'sometimes|boolean',
            'address_label' => 'nullable|string|max:40',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'required|string',
            'claim_gift' => 'sometimes|boolean',
            'buy_now_product_id' => 'nullable|integer|exists:products,id',
            'buy_now_quantity' => 'nullable|integer|min:1|max:10',
            'buy_now_portion' => 'nullable|integer|in:'.implode(',', Product::PORTION_OPTIONS),
            'payment_method' => 'sometimes|string|in:cod,razorpay',
        ], [
            'delivery_address.required_without' => 'Please enter your delivery address.',
            'delivery_address.min' => 'Please enter a complete delivery address.',
            'customer_name.required' => "Please enter the recipient's name.",
        ]);

        $user = $request->user();

        $resolvedAddress = $data['delivery_address'] ?? null;
        $resolvedLat = $data['latitude'] ?? null;
        $resolvedLng = $data['longitude'] ?? null;

        if (!empty($data['address_id'])) {
            $savedAddress = $user->addresses()->find($data['address_id']);

            if (!$savedAddress) {
                return response()->json([
                    'ok' => false,
                    'message' => 'That saved address could not be found. Please pick another or add a new one.',
                ], 422);
            }

            $resolvedAddress = $savedAddress->address_line;
            $resolvedLat = $savedAddress->latitude;
            $resolvedLng = $savedAddress->longitude;
        }

        $normalizedPhone = PhoneNumber::normalize($data['customer_phone']);
        if (!PhoneNumber::isValidIndianMobile($normalizedPhone)) {
            return response()->json([
                'ok' => false,
                'message' => "Please enter a valid 10-digit mobile number for the recipient.",
            ], 422);
        }

        $distanceKm = null;
        if ($resolvedLat !== null && $resolvedLng !== null) {
            $distanceKm = round($settings->distanceFromShopKm((float) $resolvedLat, (float) $resolvedLng), 2);
        }

        if ($settings->restrict_delivery_area) {
            if ($distanceKm === null) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Please allow location access so we can confirm you are inside our delivery area.',
                ], 422);
            }

            if ($distanceKm > $settings->delivery_radius_km) {
                return response()->json([
                    'ok' => false,
                    'message' => sprintf(
                        "Sorry, we currently deliver only within %s km of Siswa Bazar — you're about %s km away.",
                        rtrim(rtrim(number_format($settings->delivery_radius_km, 1), '0'), '.'),
                        number_format($distanceKm, 1)
                    ),
                ], 422);
            }
        }

        $recentOrders = $user->orders()->where('created_at', '>=', now()->subHour())->count();
        if ($recentOrders >= $settings->max_orders_per_hour) {
            return response()->json([
                'ok' => false,
                'message' => "You've placed too many orders in the last hour. Please wait a bit before ordering again.",
            ], 429);
        }

        // authoritative — never trust the client's submitted payment_method; the admin can
        // disable either method from Configuration and the customer's page may not have
        // refreshed yet
        $paymentMethod = $data['payment_method'] ?? 'cod';
        if ($paymentMethod === 'cod' && !$settings->cod_enabled) {
            return response()->json([
                'ok' => false,
                'message' => 'Cash on Delivery is currently unavailable. Please pay online instead.',
            ], 422);
        }
        if ($paymentMethod === 'razorpay' && !$settings->razorpay_enabled) {
            return response()->json([
                'ok' => false,
                'message' => 'Online payment is currently unavailable. Please choose Cash on Delivery.',
            ], 422);
        }

        // a customer who didn't accept a previous COD order must pay online for this many
        // orders before COD is available to them again
        if ($paymentMethod === 'cod' && $user->cod_blocked_orders > 0) {
            return response()->json([
                'ok' => false,
                'message' => "Your last COD order wasn't accepted, so online payment is required for your next {$user->cod_blocked_orders} order(s). Please choose \"Pay Online\" at checkout.",
            ], 403);
        }

        // "Order Now" posts buy_now_product_id — build a single-line order straight from that
        // product, never touching the customer's real cart (not read, not cleared afterward).
        $buyNowProductId = $data['buy_now_product_id'] ?? null;

        if ($buyNowProductId) {
            $buyNowProduct = Product::find($buyNowProductId);

            if (!$buyNowProduct) {
                return response()->json(['ok' => false, 'message' => 'That product is no longer available.'], 422);
            }

            $portion = $buyNowProduct->isLoose()
                ? (in_array($data['buy_now_portion'] ?? null, $buyNowProduct->portions ?? [], true) ? $data['buy_now_portion'] : $buyNowProduct->defaultPortion())
                : 0;

            $orderLines = collect([['product' => $buyNowProduct, 'quantity' => $data['buy_now_quantity'] ?? 1, 'portion' => $portion]]);
        } else {
            $cartProducts = $user->cart()->get();

            if ($cartProducts->isEmpty()) {
                return response()->json(['ok' => false, 'message' => 'Your cart is empty.'], 422);
            }

            $orderLines = $cartProducts->map(fn ($product) => [
                'product' => $product,
                'quantity' => $product->pivot->quantity,
                'portion' => (int) $product->pivot->portion,
            ]);
        }

        // a product can go out of stock after it was already added to the cart — catch it
        // here as the last line of defense before an order is actually placed
        $outOfStockNames = $orderLines->filter(fn ($line) => $line['product']->is_out_of_stock)->pluck('product.name');
        if ($outOfStockNames->isNotEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => $outOfStockNames->implode(', ').' '.($outOfStockNames->count() > 1 ? 'are' : 'is')." out of stock. Please remove ".($outOfStockNames->count() > 1 ? 'them' : 'it')." to continue.",
            ], 422);
        }

        $subtotal = (int) $orderLines->sum(fn ($line) => $line['product']->priceForPortion($line['portion']) * $line['quantity']);

        $coupon = $user->applied_coupon_id ? $user->appliedCoupon : null;
        $discount = 0;

        if ($coupon) {
            $redemption = $user->redeemedCoupons()->where('coupons.id', $coupon->id)->first();
            $discount = $redemption?->pivot->discount_amount ?? $coupon->discountFor($subtotal);
        }

        $payableTotal = $subtotal - $discount;

        if ($settings->min_order_amount !== null && $payableTotal < $settings->min_order_amount) {
            return response()->json([
                'ok' => false,
                'message' => "Your order total must be at least ₹{$settings->min_order_amount} to check out.",
            ], 422);
        }

        if ($settings->max_order_amount !== null && $payableTotal > $settings->max_order_amount) {
            return response()->json([
                'ok' => false,
                'message' => "Your order total can't exceed ₹{$settings->max_order_amount}. Please remove some items.",
            ], 422);
        }

        // authoritative fee computation — the client only ever shows a preview (Alpine.store('shop')
        // in app.js); ShopSetting::activeFees() is the single source of truth on both sides.
        // Based on subtotal alone (matches the free-delivery threshold's usual real-world meaning —
        // goods value before a coupon), separate from the min/max order checks above.
        $fees = $settings->activeFees($subtotal);
        $feesTotal = collect($fees)->sum('amount');

        // never trust the client flag alone — re-check eligibility server-side right before spending it
        $claimGift = false;
        $giftProduct = null;
        if ($request->boolean('claim_gift') && $settings->rewardConfigured() && $user->free_gifts_available > 0) {
            $claimGift = true;
            $giftProduct = $settings->rewardGiftProduct;
        }

        // for online payment, open the gateway order *before* touching our own database — if
        // Razorpay can't be reached, checkout fails cleanly instead of leaving a stray local order
        $razorpayOrder = null;
        if ($paymentMethod === 'razorpay') {
            try {
                $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
                $razorpayOrder = $api->order->create([
                    'amount' => ($subtotal - $discount + $feesTotal) * 100,
                    'currency' => 'INR',
                    'receipt' => 'order_rcpt_'.$user->id.'_'.now()->timestamp,
                    'payment_capture' => 1,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Could not start online payment right now. Please try again or choose Cash on Delivery.',
                ], 502);
            }
        }

        // only ever save a freshly-typed address once every guard above has passed, so a
        // rejected checkout attempt never silently saves an address the customer didn't confirm
        if (empty($data['address_id']) && ($data['save_address'] ?? false)) {
            $isFirst = $user->addresses()->count() === 0;
            $user->addresses()->create([
                'label' => $data['address_label'] ?? null,
                'address_line' => $resolvedAddress,
                'latitude' => $resolvedLat,
                'longitude' => $resolvedLng,
                'is_default' => $isFirst,
            ]);
        }

        $order = DB::transaction(function () use (
            $user, $coupon, $data, $normalizedPhone, $resolvedAddress, $resolvedLat, $resolvedLng,
            $distanceKm, $subtotal, $discount, $paymentMethod, $razorpayOrder, $claimGift,
            $orderLines, $giftProduct, $settings, $buyNowProductId, $fees, $feesTotal
        ) {
            // a single_use coupon is only ever meant to be spent by ONE customer, but that was
            // previously only checked at /coupon/apply time — two different customers applying
            // the same code before either finished checkout could BOTH reach here and both
            // redeem it. Re-verify right at the point of no return, with the lock held for the
            // rest of this transaction, so this can never be exploited even by genuinely
            // simultaneous requests from different accounts.
            if ($coupon && $coupon->usage_type === 'single_use') {
                $claimedByAnotherUser = DB::table('coupon_redemptions')
                    ->where('coupon_id', $coupon->id)
                    ->where('user_id', '!=', $user->id)
                    ->whereNotNull('redeemed_at')
                    ->lockForUpdate()
                    ->exists();

                if ($claimedByAnotherUser) {
                    return null;
                }
            }

            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $normalizedPhone,
                'delivery_address' => $resolvedAddress,
                'latitude' => $resolvedLat,
                'longitude' => $resolvedLng,
                'distance_km' => $distanceKm,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total' => $subtotal - $discount + $feesTotal,
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'razorpay_order_id' => $razorpayOrder['id'] ?? null,
                'is_gift_order' => $claimGift,
            ]);

            // snapshot exactly what was charged and why — immune to the admin changing the fee
            // amount/message later (see OrderFee, the generic table any future fee type reuses)
            foreach ($fees as $fee) {
                $order->fees()->create($fee);
            }

            // once online payment ships, a successful non-COD order should count against the strike
            if ($paymentMethod !== 'cod' && $user->cod_blocked_orders > 0) {
                $user->decrement('cod_blocked_orders');
            }

            foreach ($orderLines as $line) {
                $unitPrice = $line['product']->priceForPortion($line['portion']);
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'product_price' => $unitPrice,
                    'quantity' => $line['quantity'],
                    'portion' => $line['portion'] ?? 0,
                    'line_total' => $unitPrice * $line['quantity'],
                ]);
            }

            if ($claimGift && $giftProduct) {
                $order->items()->create([
                    'product_id' => $giftProduct->id,
                    'product_name' => $settings->reward_gift_label ?: $giftProduct->name,
                    'product_price' => 0,
                    'quantity' => 1,
                    'line_total' => 0,
                    'is_gift' => true,
                ]);

                // a single conditional UPDATE — atomic even without a row lock, so two
                // simultaneous checkout requests can never both decrement past zero
                User::where('id', $user->id)->where('free_gifts_available', '>', 0)->decrement('free_gifts_available');

                ActivityLogger::log('gift_claimed', "Order {$order->orderNumber()} claimed free gift: {$giftProduct->name}");
            }

            // clear the cart; the coupon redemption record itself stays intact as proof of usage.
            // Skipped for a buy-now order, which never touched the cart in the first place.
            if (!$buyNowProductId) {
                $user->cart()->detach();
            }
            if ($coupon) {
                // only now — an order has actually been placed — does the redemption become
                // permanent; before this it was just a pending apply, freely reusable/removable
                $user->redeemedCoupons()->updateExistingPivot($coupon->id, ['redeemed_at' => now()]);
            }
            if ($user->applied_coupon_id) {
                $user->forceFill(['applied_coupon_id' => null])->save();
            }

            return $order;
        });

        if ($order === null) {
            return response()->json([
                'ok' => false,
                'message' => 'This coupon was just used by someone else and is no longer available. Please remove it and try again.',
            ], 422);
        }

        ActivityLogger::log('order_placed', "Order {$order->orderNumber()} — ₹".number_format($order->total));

        if ($paymentMethod === 'razorpay') {
            return response()->json([
                'ok' => true,
                'order_id' => $order->id,
                'total' => $order->total,
                'payment_method' => 'razorpay',
                'razorpay' => [
                    'key' => config('services.razorpay.key'),
                    'order_id' => $razorpayOrder['id'],
                    'amount' => $razorpayOrder['amount'],
                    'currency' => $razorpayOrder['currency'],
                    'name' => 'Shri Vinayak Family Shop',
                    'description' => $order->orderNumber(),
                    'prefill' => [
                        'name' => $data['customer_name'],
                        'contact' => $normalizedPhone,
                    ],
                ],
            ]);
        }

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'total' => $order->total,
        ]);
    }
}
