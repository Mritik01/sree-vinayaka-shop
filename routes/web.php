<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BestsellerController as AdminBestsellerController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FestivalSpecialController as AdminFestivalSpecialController;
use App\Http\Controllers\Admin\HeroBannerController as AdminHeroBannerController;
use App\Http\Controllers\Admin\ImpersonationController as AdminImpersonationController;
use App\Http\Controllers\Admin\ImpersonationLogController as AdminImpersonationLogController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\LegalDocumentController as AdminLegalDocumentController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RiderController as AdminRiderController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\VisitorController as AdminVisitorController;
use App\Http\Controllers\Rider\AuthController as RiderAuthController;
use App\Http\Controllers\Rider\OrderController as RiderOrderController;
use App\Http\Controllers\Auth\PhoneAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PromoLeadController;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SupportChatController;
use App\Models\Category;
use App\Models\HeroBanner;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/shop-status', function () {
    $settings = ShopSetting::current();

    return response()->json([
        'accepting_orders' => $settings->accepting_orders,
        'restrict_delivery_area' => $settings->restrict_delivery_area,
        'delivery_radius_km' => $settings->delivery_radius_km,
        'delivery_fee_strategy' => $settings->delivery_fee_strategy,
        'delivery_free_min_order' => $settings->delivery_free_min_order,
        'delivery_fee_below_minimum' => $settings->delivery_fee_below_minimum,
        'delivery_fee_fixed' => $settings->delivery_fee_fixed,
        'delivery_success_message' => $settings->delivery_success_message,
        'delivery_success_animation' => $settings->delivery_success_animation,
        'rain_fee_enabled' => $settings->rain_fee_enabled,
        'rain_fee_amount' => $settings->rain_fee_amount ?? 0,
        'rain_fee_message' => $settings->rain_fee_enabled ? $settings->rainFeeMessage() : null,
        'high_demand_mode' => $settings->high_demand_mode,
        'high_demand_fee_amount' => $settings->high_demand_fee_amount ?? 0,
        'high_demand_fee_message' => $settings->high_demand_mode === 'fee' ? $settings->highDemandFeeMessage() : null,
        'high_demand_stop_message' => $settings->high_demand_mode === 'stop' ? $settings->highDemandStopMessage() : null,
        'delivery_time_estimate_minutes' => $settings->delivery_time_estimate_minutes,
    ]);
})->name('shop.status');

// live product suggestions for the header search — small payload, throttled, public
Route::get('/search-suggest', function (Illuminate\Http\Request $request) {
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
        ->limit(8)
        ->get()
        ->map(fn ($p) => [
            'name' => $p->name,
            'image' => asset($p->image),
            'weight' => $p->isLoose() ? Product::portionLabel($p->defaultPortion()) : $p->weight,
            'price' => $p->priceForPortion($p->defaultPortion()),
            'url' => route('products.show', $p),
        ]);

    return response()->json(['products' => $products]);
})->middleware('throttle:60,1')->name('search.suggest');

Route::get('/delivery-check', function (Illuminate\Http\Request $request) {
    $data = $request->validate([
        'lat' => 'required|numeric|between:-90,90',
        'lng' => 'required|numeric|between:-180,180',
    ]);

    $settings = ShopSetting::current();
    $distanceKm = round($settings->distanceFromShopKm((float) $data['lat'], (float) $data['lng']), 2);

    return response()->json([
        'ok' => true,
        'restricted' => $settings->restrict_delivery_area,
        'radius_km' => $settings->delivery_radius_km,
        'distance_km' => $distanceKm,
        'within' => $distanceKm <= $settings->delivery_radius_km,
    ]);
})->name('delivery.check');

Route::get('/', function () {
    return view('home', [
        'products' => Product::where('is_bestseller', true)
            ->withAvg('reviews', 'rating')->withCount('reviews')
            ->orderBy('sort_order')->get(),
        'festivalProducts' => Product::where('is_festival_special', true)->orderBy('sort_order')->get(),
        'heroBanners' => HeroBanner::active()->get(),
    ]);
});

Route::get('/products', function () {
    $products = Product::withAvg('reviews', 'rating')->withCount('reviews')
        ->with('categories:id')
        ->orderBy('sort_order')
        ->get();

    // arriving from the mobile category panel (?category=slug) — resolved server-side so the
    // grid can be pre-filtered on load via productListing()'s new `filters.categoryId`, and so
    // a shared/bookmarked link works the same way
    $activeCategory = request()->filled('category')
        ? Category::where('slug', request('category'))->where('is_active', true)->first()
        : null;

    return view('products', [
        'products' => $products,
        'categories' => $products->pluck('category')->unique()->values(),
        'activeCategory' => $activeCategory,
    ]);
})->name('products.index');

Route::post('/locale/{locale}', [LocaleController::class, 'switchCustomer'])->name('locale.switch');

Route::post('/promo/send-otp', [PromoLeadController::class, 'sendOtp'])
    ->middleware('throttle:otp-send')
    ->name('promo.send-otp');

Route::post('/promo/verify-otp', [PromoLeadController::class, 'verifyOtp'])
    ->middleware('throttle:otp-verify')
    ->name('promo.verify-otp');

Route::post('/auth/send-otp', [PhoneAuthController::class, 'sendOtp'])
    ->middleware('throttle:auth-otp-send')
    ->name('auth.send-otp');

Route::post('/auth/verify-otp', [PhoneAuthController::class, 'verifyOtp'])
    ->middleware('throttle:auth-otp-verify')
    ->name('auth.verify-otp');

Route::post('/auth/complete-signup', [PhoneAuthController::class, 'completeSignup'])
    ->middleware('throttle:auth-complete-signup')
    ->name('auth.complete-signup');

Route::post('/logout', [PhoneAuthController::class, 'logout'])
    ->name('logout');

Route::get('/account', function () {
    if (!Auth::check()) {
        return redirect('/');
    }

    $user = Auth::user();

    return view('account', [
        'addresses' => $user->addresses->map(fn ($a) => [
            'id' => $a->id,
            'label' => $a->label,
            'address_line' => $a->address_line,
            'latitude' => $a->latitude,
            'longitude' => $a->longitude,
            'is_default' => $a->is_default,
        ])->values(),
        'reward' => \App\Services\RewardService::statusFor($user),
        'orders' => $user->orders()->with('items')->latest()->get(),
        'ordersCount' => $user->orders()->count(),
        'favoriteProducts' => $user->favorites()->orderBy('sort_order')->get(),
        'deliveredCount' => $user->orders()->where('status', 'delivered')->count(),
        'initialTab' => in_array(request('tab'), ['profile', 'orders', 'addresses', 'rewards', 'favorites'], true) ? request('tab') : 'profile',
    ]);
})->name('account');

// the standalone orders list is retired in favor of the account page's "My Orders" tab —
// kept as a redirect so old bookmarks/links still land somewhere sensible
Route::get('/orders', function () {
    return redirect()->route('account', ['tab' => 'orders']);
})->name('orders');

Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::post('/consent/accept', [ConsentController::class, 'accept'])
    ->middleware('auth')
    ->name('consent.accept');

Route::middleware('auth')->group(function () {
    Route::get('/orders/{order}', [OrderTrackingController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/status', [OrderTrackingController::class, 'status'])->name('orders.status');
    Route::get('/orders/{order}/partial', [OrderTrackingController::class, 'partial'])->name('orders.partial');
    Route::patch('/orders/{order}/note', [OrderTrackingController::class, 'updateNote'])->name('orders.note');
    Route::patch('/orders/{order}/cancel', [OrderTrackingController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{order}/invoice', [OrderTrackingController::class, 'invoice'])->name('orders.invoice');

    Route::get('/orders/{order}/support', [SupportChatController::class, 'fetch'])->name('orders.support.fetch');
    Route::post('/orders/{order}/support', [SupportChatController::class, 'send'])->name('orders.support.send');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-read');
    Route::delete('/notifications/clear', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

Route::post('/favorites/{product:id}/toggle', [FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');

// the standalone favorites page is retired in favor of the account page's "My Favorites" tab —
// kept as a redirect so old bookmarks/links still land somewhere sensible
Route::get('/favorites', function () {
    return redirect()->route('account', ['tab' => 'favorites']);
})->name('favorites.index');

Route::get('/product/{product:slug}', function (Product $product) {
    $related = Product::where('id', '!=', $product->id)
        ->where('category', $product->category)
        ->orderBy('sort_order')
        ->get();

    if ($related->count() < 4) {
        $related = $related->concat(
            Product::where('id', '!=', $product->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->orderBy('sort_order')
                ->get()
        );
    }

    $favoritedIds = Auth::check() ? Auth::user()->favorites()->pluck('products.id')->all() : [];

    $reviews = $product->reviews()->with('user')->get();
    $averageRating = $reviews->avg('rating');

    ActivityLogger::log('product_view', $product->name, ['product_id' => $product->id]);

    return view('product-show', [
        'product' => $product,
        'related' => $related->take(4),
        'isFavorited' => in_array($product->id, $favoritedIds),
        'favoritedIds' => $favoritedIds,
        'reviews' => $reviews,
        'averageRating' => $averageRating ? round($averageRating, 1) : 0,
        'reviewsCount' => $reviews->count(),
        'userReview' => Auth::check() ? $reviews->firstWhere('user_id', Auth::id()) : null,
    ]);
})->name('products.show');

Route::post('/product/{product:slug}/reviews', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('reviews.store');

Route::post('/cart/{product:id}/add', [CartController::class, 'add'])
    ->middleware('auth')
    ->name('cart.add');

Route::patch('/cart/{product:id}', [CartController::class, 'updateQuantity'])
    ->middleware('auth')
    ->name('cart.update');

Route::delete('/cart/{product:id}', [CartController::class, 'remove'])
    ->middleware('auth')
    ->name('cart.remove');

Route::post('/coupon/apply', [CouponController::class, 'apply'])
    ->middleware('auth')
    ->name('coupon.apply');

Route::delete('/coupon/remove', [CouponController::class, 'remove'])
    ->middleware('auth')
    ->name('coupon.remove');

Route::post('/addresses', [AddressController::class, 'store'])
    ->middleware('auth')
    ->name('addresses.store');

Route::patch('/addresses/{address}', [AddressController::class, 'update'])
    ->middleware('auth')
    ->name('addresses.update');

Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])
    ->middleware('auth')
    ->name('addresses.destroy');

Route::patch('/addresses/{address}/default', [AddressController::class, 'setDefault'])
    ->middleware('auth')
    ->name('addresses.set-default');

Route::get('/cart', function () {
    if (!Auth::check()) {
        return redirect('/');
    }

    return view('cart', [
        'cartProducts' => Auth::user()->cart()->get(),
    ]);
})->name('cart.index');

Route::get('/checkout', [CheckoutController::class, 'show'])
    ->middleware('auth')
    ->name('checkout.show');

Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('auth')
    ->name('checkout.store');

Route::post('/checkout/razorpay/verify', [RazorpayController::class, 'verify'])
    ->middleware('auth')
    ->name('checkout.razorpay.verify');

// hit by Razorpay's servers directly, never by the browser — no session/CSRF, verified by signature instead
Route::post('/webhooks/razorpay', [RazorpayController::class, 'webhook'])
    ->name('webhooks.razorpay');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:admin-login')->name('login.attempt');
    Route::post('/locale/{locale}', [LocaleController::class, 'switchAdmin'])->name('locale.switch');

    Route::middleware('admin.auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/configuration', [AdminDashboardController::class, 'configuration'])->name('configuration');
        Route::patch('/settings/accepting-orders', [AdminDashboardController::class, 'toggleAcceptingOrders'])->name('settings.accepting-orders');
        Route::patch('/settings/delivery-area', [AdminDashboardController::class, 'toggleDeliveryArea'])->name('settings.delivery-area');
        Route::patch('/settings/reward-enabled', [AdminDashboardController::class, 'toggleRewardEnabled'])->name('settings.reward-enabled');
        Route::patch('/settings/promo-popup', [AdminDashboardController::class, 'togglePromoPopup'])->name('settings.promo-popup');
        Route::patch('/settings/rewards', [AdminDashboardController::class, 'updateRewardSettings'])->name('settings.rewards');
        Route::patch('/settings/order-limits', [AdminDashboardController::class, 'updateOrderLimits'])->name('settings.order-limits');
        Route::patch('/settings/delivery-time', [AdminDashboardController::class, 'updateDeliveryTimeSettings'])->name('settings.delivery-time');
        Route::patch('/settings/rain-fee-enabled', [AdminDashboardController::class, 'toggleRainFeeEnabled'])->name('settings.rain-fee-enabled');
        Route::patch('/settings/delivery-fee', [AdminDashboardController::class, 'updateDeliveryFeeSettings'])->name('settings.delivery-fee');
        Route::patch('/settings/rain-fee', [AdminDashboardController::class, 'updateRainFeeSettings'])->name('settings.rain-fee');
        Route::patch('/settings/high-demand', [AdminDashboardController::class, 'updateHighDemandSettings'])->name('settings.high-demand');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/poll', [AdminOrderController::class, 'poll'])->name('orders.poll');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/status', [AdminOrderController::class, 'status'])->name('orders.status.poll');
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
        Route::patch('/orders/{order}/rider', [AdminOrderController::class, 'assignRider'])->name('orders.rider');
        Route::post('/orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
        Route::patch('/orders/{order}/items/{item}/confirm', [AdminOrderController::class, 'confirmItem'])->name('orders.items.confirm');
        Route::post('/orders/{order}/items/confirm-all', [AdminOrderController::class, 'confirmAllItems'])->name('orders.items.confirm-all');

        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])->name('categories.toggle');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/bestsellers', [AdminBestsellerController::class, 'index'])->name('bestsellers.index');
        Route::post('/bestsellers', [AdminBestsellerController::class, 'update'])->name('bestsellers.update');

        Route::get('/festival-special', [AdminFestivalSpecialController::class, 'index'])->name('festival-special.index');
        Route::post('/festival-special', [AdminFestivalSpecialController::class, 'update'])->name('festival-special.update');

        Route::get('/announcement', [AdminAnnouncementController::class, 'edit'])->name('announcement.edit');
        Route::post('/announcement', [AdminAnnouncementController::class, 'update'])->name('announcement.update');

        Route::get('/hero-banners', [AdminHeroBannerController::class, 'index'])->name('hero-banners.index');
        Route::get('/hero-banners/create', [AdminHeroBannerController::class, 'create'])->name('hero-banners.create');
        Route::post('/hero-banners', [AdminHeroBannerController::class, 'store'])->name('hero-banners.store');
        Route::get('/hero-banners/{heroBanner}/edit', [AdminHeroBannerController::class, 'edit'])->name('hero-banners.edit');
        Route::put('/hero-banners/{heroBanner}', [AdminHeroBannerController::class, 'update'])->name('hero-banners.update');
        Route::patch('/hero-banners/{heroBanner}/toggle', [AdminHeroBannerController::class, 'toggle'])->name('hero-banners.toggle');
        Route::delete('/hero-banners/{heroBanner}', [AdminHeroBannerController::class, 'destroy'])->name('hero-banners.destroy');

        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/mark-read', [AdminNotificationController::class, 'markAllRead'])->name('notifications.mark-read');
        Route::delete('/notifications/clear', [AdminNotificationController::class, 'clearAll'])->name('notifications.clear-all');
        Route::delete('/notifications/{notification}', [AdminNotificationController::class, 'destroy'])->name('notifications.destroy');

        // Managing OTHER admin accounts is a step above just being logged in as an admin —
        // gated by admin.super on top of admin.auth. See EnsureSuperAdmin and CreateAdminCommand.
        Route::middleware('admin.super')->prefix('admins')->name('admins.')->group(function () {
            Route::get('/', [AdminAccountController::class, 'index'])->name('index');
            Route::get('/create', [AdminAccountController::class, 'create'])->name('create');
            Route::post('/', [AdminAccountController::class, 'store'])->name('store');
            Route::get('/{admin}/edit', [AdminAccountController::class, 'edit'])->name('edit');
            Route::put('/{admin}', [AdminAccountController::class, 'update'])->name('update');
            Route::delete('/{admin}', [AdminAccountController::class, 'destroy'])->name('destroy');
        });

        Route::get('/riders', [AdminRiderController::class, 'index'])->name('riders.index');
        Route::get('/riders/create', [AdminRiderController::class, 'create'])->name('riders.create');
        Route::post('/riders', [AdminRiderController::class, 'store'])->name('riders.store');
        Route::get('/riders/{rider}/edit', [AdminRiderController::class, 'edit'])->name('riders.edit');
        Route::put('/riders/{rider}', [AdminRiderController::class, 'update'])->name('riders.update');
        Route::delete('/riders/{rider}', [AdminRiderController::class, 'destroy'])->name('riders.destroy');

        Route::get('/legal', [AdminLegalDocumentController::class, 'index'])->name('legal.index');
        Route::get('/legal/{type}', [AdminLegalDocumentController::class, 'edit'])->name('legal.edit');
        Route::put('/legal/{type}', [AdminLegalDocumentController::class, 'update'])->name('legal.update');

        Route::get('/coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
        Route::get('/coupons/create', [AdminCouponController::class, 'create'])->name('coupons.create');
        Route::post('/coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
        Route::get('/coupons/{coupon}/edit', [AdminCouponController::class, 'edit'])->name('coupons.edit');
        Route::put('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');
        Route::get('/coupons/{coupon}', [AdminCouponController::class, 'show'])->name('coupons.show');

        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{user}', [AdminCustomerController::class, 'show'])->name('customers.show');
        Route::patch('/customers/{user}/clear-cod-restriction', [AdminCustomerController::class, 'clearCodRestriction'])->name('customers.clear-cod-restriction');
        Route::post('/customers/notify-all', [AdminCustomerController::class, 'notifyAll'])->name('customers.notify-all');
        Route::post('/customers/{user}/notify', [AdminCustomerController::class, 'notify'])->name('customers.notify');
        Route::post('/customers/{user}/coupons', [AdminCustomerController::class, 'attachCoupon'])->name('customers.coupons.attach');
        Route::delete('/customers/{user}/coupons/{coupon}', [AdminCustomerController::class, 'detachCoupon'])->name('customers.coupons.detach');

        // "Login as Customer" — super-admin only (see EnsureSuperAdmin); stop is reachable by any
        // logged-in admin since it just ends whatever impersonation their own session started
        Route::post('/customers/{user}/impersonate', [AdminImpersonationController::class, 'start'])
            ->middleware('admin.super')
            ->name('customers.impersonate');
        Route::post('/impersonate/stop', [AdminImpersonationController::class, 'stop'])->name('impersonate.stop');
        Route::middleware('admin.super')->get('/impersonation-log', [AdminImpersonationLogController::class, 'index'])->name('impersonation-log.index');

        Route::get('/visitors', [AdminVisitorController::class, 'index'])->name('visitors.index');

        Route::get('/leads', [AdminLeadController::class, 'index'])->name('leads.index');

        Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');

        Route::get('/support', [AdminSupportController::class, 'index'])->name('support.index');
        Route::get('/support/{order}', [AdminSupportController::class, 'show'])->name('support.show');
        Route::get('/support/{order}/messages', [AdminSupportController::class, 'messages'])->name('support.messages');
        Route::post('/support/{order}/messages', [AdminSupportController::class, 'send'])->name('support.send');
    });
});

Route::prefix('rider')->name('rider.')->group(function () {
    Route::get('/login', [RiderAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [RiderAuthController::class, 'login'])->middleware('throttle:rider-login')->name('login.attempt');
    Route::post('/locale/{locale}', [LocaleController::class, 'switchRider'])->name('locale.switch');

    Route::middleware('rider.auth')->group(function () {
        Route::post('/logout', [RiderAuthController::class, 'logout'])->name('logout');
        Route::get('/orders', [RiderOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/poll', [RiderOrderController::class, 'poll'])->name('orders.poll');
        Route::get('/orders/{order}', [RiderOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [RiderOrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/orders/{order}/photo', [RiderOrderController::class, 'uploadPhoto'])->name('orders.photo');
    });
});
