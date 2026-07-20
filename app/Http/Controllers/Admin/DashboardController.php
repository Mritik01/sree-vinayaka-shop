<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_products' => Product::count(),
            'active_coupons' => Coupon::where('is_active', true)->where('expires_at', '>', now())->count(),
            'customers' => User::count(),
            'revenue' => (int) Order::whereIn('status', ['confirmed', 'out_for_delivery', 'delivered'])->sum('total'),
        ];

        // last 14 days of orders + revenue for the charts
        $window = collect(range(13, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->toDateString());
        $ordersByDay = Order::where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->get(['total', 'status', 'created_at'])
            ->groupBy(fn ($order) => $order->created_at->toDateString());

        $chartData = [
            'days' => [
                'labels' => $window->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('d M'))->values(),
                'orders' => $window->map(fn ($date) => ($ordersByDay[$date] ?? collect())->count())->values(),
                'revenue' => $window->map(fn ($date) => (int) ($ordersByDay[$date] ?? collect())
                    ->whereIn('status', ['confirmed', 'out_for_delivery', 'delivered'])->sum('total'))->values(),
            ],
            'status' => [
                'pending' => Order::where('status', 'pending')->count(),
                'confirmed' => Order::where('status', 'confirmed')->count(),
                'out_for_delivery' => Order::where('status', 'out_for_delivery')->count(),
                'delivered' => Order::where('status', 'delivered')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
            ],
        ];

        $topProducts = OrderItem::selectRaw('product_name, SUM(quantity) as total_quantity')
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get();

        $chartData['topProducts'] = [
            'labels' => $topProducts->pluck('product_name')->values(),
            'quantities' => $topProducts->pluck('total_quantity')->map(fn ($quantity) => (int) $quantity)->values(),
        ];

        $recentOrders = Order::with('items')->latest()->take(8)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'chartData' => $chartData,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function configuration()
    {
        $settings = ShopSetting::current();

        return view('admin.configuration', [
            'acceptingOrders' => $settings->accepting_orders,
            'restrictDeliveryArea' => $settings->restrict_delivery_area,
            'deliveryRadiusKm' => $settings->delivery_radius_km,
            'rewardSettings' => $settings,
            'settings' => $settings,
            'promoPopupEnabled' => $settings->promo_popup_enabled,
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function toggleAcceptingOrders(Request $request)
    {
        $accepting = ShopSetting::toggleAcceptingOrders();

        return response()->json(['ok' => true, 'accepting_orders' => $accepting, 'value' => $accepting]);
    }

    public function toggleRainFeeEnabled(Request $request)
    {
        $settings = ShopSetting::current();
        $settings->update(['rain_fee_enabled' => !$settings->rain_fee_enabled]);

        return response()->json(['ok' => true, 'value' => $settings->rain_fee_enabled]);
    }

    public function toggleDeliveryArea(Request $request)
    {
        $restricted = ShopSetting::toggleRestrictDeliveryArea();

        return response()->json(['ok' => true, 'value' => $restricted]);
    }

    public function toggleRewardEnabled(Request $request)
    {
        $settings = ShopSetting::current();
        $settings->update(['reward_enabled' => !$settings->reward_enabled]);

        return response()->json(['ok' => true, 'value' => $settings->reward_enabled]);
    }

    public function toggleCodEnabled(Request $request)
    {
        $settings = ShopSetting::current();
        $newValue = !$settings->cod_enabled;

        if (!$newValue && !$settings->razorpay_enabled) {
            return response()->json(['ok' => false, 'message' => 'At least one payment method must stay enabled.'], 422);
        }

        $settings->update(['cod_enabled' => $newValue]);

        return response()->json(['ok' => true, 'value' => $settings->cod_enabled]);
    }

    public function toggleRazorpayEnabled(Request $request)
    {
        $settings = ShopSetting::current();
        $newValue = !$settings->razorpay_enabled;

        if (!$newValue && !$settings->cod_enabled) {
            return response()->json(['ok' => false, 'message' => 'At least one payment method must stay enabled.'], 422);
        }

        $settings->update(['razorpay_enabled' => $newValue]);

        return response()->json(['ok' => true, 'value' => $settings->razorpay_enabled]);
    }

    public function togglePromoPopup(Request $request)
    {
        $settings = ShopSetting::current();
        $settings->update(['promo_popup_enabled' => !$settings->promo_popup_enabled]);

        return response()->json(['ok' => true, 'value' => $settings->promo_popup_enabled]);
    }

    public function toggleCategoryRow(Request $request)
    {
        $settings = ShopSetting::current();
        $settings->update(['show_category_row' => !$settings->show_category_row]);

        return response()->json(['ok' => true, 'value' => $settings->show_category_row]);
    }

    public function updateRewardSettings(Request $request)
    {
        $data = $request->validate([
            'reward_orders_required' => 'required|integer|min:1|max:100',
            'reward_gift_product_id' => 'nullable|integer|exists:products,id',
            'reward_gift_label' => 'nullable|string|max:100',
        ]);

        ShopSetting::current()->update($data);

        return back()->with('status', 'Reward settings updated.');
    }

    public function updateOrderLimits(Request $request)
    {
        $data = $request->validate([
            'min_order_amount' => 'nullable|integer|min:0',
            'max_order_amount' => 'nullable|integer|min:0',
            'max_orders_per_hour' => 'required|integer|min:1|max:100',
        ]);

        if ($data['min_order_amount'] !== null && $data['max_order_amount'] !== null && $data['max_order_amount'] < $data['min_order_amount']) {
            return back()->withErrors(['max_order_amount' => 'Maximum order amount must be greater than or equal to the minimum.'])->withInput();
        }

        ShopSetting::current()->update($data);

        return back()->with('status', 'Order limits updated.');
    }

    public function updateDeliveryTimeSettings(Request $request)
    {
        $data = $request->validate([
            'delivery_time_estimate_minutes' => 'nullable|integer|min:0|max:999',
        ]);

        ShopSetting::current()->update($data);

        return back()->with('status', 'Delivery time estimate updated.');
    }

    public function updateBusinessInfoSettings(Request $request)
    {
        // accept +91-prefixed/spaced/dashed input and reduce it to bare digits before validating,
        // so pasting a formatted number doesn't hard-fail — stored as bare 10 digits everywhere,
        // same convention as customer/rider phone numbers elsewhere in this app. An empty submitted
        // value must become null (not '') so `nullable` actually clears the field instead of
        // failing the regex — Laravel's `nullable` only skips other rules for a true null.
        $request->merge(['business_mobile_number' => $request->filled('business_mobile_number')
            ? substr(preg_replace('/\D/', '', $request->input('business_mobile_number')), -10)
            : null]);

        $data = $request->validate([
            'business_mobile_number' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
        ]);

        ShopSetting::current()->update($data);

        return back()->with('status', 'Business info updated.');
    }

    public function updateDeliveryFeeSettings(Request $request)
    {
        $data = $request->validate([
            'delivery_fee_strategy' => 'required|in:free_above_minimum,fixed',
            'delivery_free_min_order' => 'nullable|integer|min:0',
            'delivery_fee_below_minimum' => 'nullable|integer|min:0',
            'delivery_fee_fixed' => 'nullable|integer|min:0',
            'delivery_success_message' => 'required|string|max:150',
            'delivery_success_animation' => 'required|in:confetti_truck,minimal',
        ]);

        $data['delivery_fee_fixed'] = $data['delivery_fee_fixed'] ?? 0;

        ShopSetting::current()->update($data);

        return back()->with('status', 'Delivery fee settings updated.');
    }

    public function updateRainFeeSettings(Request $request)
    {
        $data = $request->validate([
            'rain_fee_amount' => 'nullable|integer|min:0',
            'rain_fee_reason' => 'nullable|string|max:100',
            'rain_fee_message' => 'nullable|string|max:500',
        ]);

        ShopSetting::current()->update($data);

        return back()->with('status', 'Rain fee settings updated.');
    }

    public function updateHighDemandSettings(Request $request)
    {
        $data = $request->validate([
            'high_demand_mode' => 'required|in:normal,stop,fee',
            'high_demand_fee_amount' => 'nullable|integer|min:0',
            'high_demand_message' => 'nullable|string|max:500',
            'high_demand_stop_message' => 'nullable|string|max:500',
        ]);

        ShopSetting::current()->update($data);

        return back()->with('status', 'High demand settings updated.');
    }
}
