<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $cartDrawerItems = Auth::check()
            ? Auth::user()->cart()->get()
                ->map(fn ($p) => $p->cartRow((int) $p->pivot->portion, (int) $p->pivot->quantity))
                ->values()
            : collect();
        // small, rarely-changing table — cheap enough to query on every page load, needed
        // site-wide for the mobile bottom nav's Categories panel (see partials/category-panel)
        $navCategories = \App\Models\Category::where('is_active', true)->orderBy('sort_order')->get();

        // both documents' current content, embedded once so the signup modal (and the
        // re-consent gate) open instantly with zero fetch — see partials/legal-document-modal
        $termsDoc = \App\Models\LegalDocumentVersion::current('terms');
        $privacyDoc = \App\Models\LegalDocumentVersion::current('privacy');

        // powers the site-wide re-consent prompt: has this logged-in customer's last accepted
        // version fallen behind what's currently published? Impersonation is exempt — an admin
        // clicking "I agree" as the customer would produce a legally meaningless record (see
        // ImpersonationService).
        $needsReconsent = false;
        if (Auth::check() && !\App\Services\ImpersonationService::active()) {
            $latestTermsAccepted = Auth::user()->consents()->whereNotNull('terms_version')->max('terms_version');
            $latestPrivacyAccepted = Auth::user()->consents()->whereNotNull('privacy_version')->max('privacy_version');
            $needsReconsent = ($termsDoc && (int) $latestTermsAccepted < $termsDoc->version)
                || ($privacyDoc && (int) $latestPrivacyAccepted < $privacyDoc->version);
        }
    @endphp
    <script>
        window.__mbIsLoggedIn = @json(Auth::check());
        window.__mbShopStatus = @json($shopStatus);
        window.__mbCartItems = @json($cartDrawerItems);
        window.__mbLegalDocs = {
            terms: @json($termsDoc ? ['title' => $termsDoc->title, 'content' => $termsDoc->content, 'updatedAt' => $termsDoc->published_at->format('d M Y')] : null),
            privacy: @json($privacyDoc ? ['title' => $privacyDoc->title, 'content' => $privacyDoc->content, 'updatedAt' => $privacyDoc->published_at->format('d M Y')] : null),
        };
    </script>
    <title>@yield('title', 'Makhanbhog Sweets — No. 1 Sweet Shop in Thuthibari')</title>
    {{-- plain @yield is correct here: @section('name', $value) already runs $value through e()
         internally (Laravel's ManagesLayouts::startSection()), so by the time this yields, a
         literal " in a per-page description is already &quot; — wrapping this in another {{ }}
         would escape it a second time (confirmed: & became &amp;amp; before this was caught). --}}
    <meta name="description" content="@yield('description', "Makhanbhog Sweets, Thuthibari's favourite sweet shop, now delivering fresh mithai to your doorstep.")">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Baloo+2:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- pb-32 (not pb-16) — the bottom nav alone is only 4rem tall, but the floating "View Cart"
     pill rests higher still (bottom-20 + its own height), so content needs clearance for both
     or the page's last few pixels (e.g. the footer's copyright line) end up hidden under it --}}
<body class="antialiased pb-32 lg:pb-0">
    @include('partials.impersonation-banner')
    <div x-data="{ authOpen: false }" @open-auth-modal.window="authOpen = true">
        @include('partials.navbar')
        @include('partials.auth-modal')
        @include('partials.cart-drawer')
        @include('partials.category-panel', ['categories' => $navCategories])
        @include('partials.legal-document-modal')
        @if ($needsReconsent)
            @include('partials.reconsent-gate')
        @endif

        {{-- live toast when the shop's accepting-orders status changes while browsing --}}
        <div x-show="$store.shop.toast" x-cloak
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed top-4 inset-x-0 z-[70] flex justify-center px-4 pointer-events-none">
            <div class="max-w-md bg-maroon-800 text-cream rounded-xl shadow-2xl px-5 py-3 text-sm font-medium text-center pointer-events-auto"
                 x-text="$store.shop.toast"></div>
        </div>

        {{-- bottom free-delivery + View Cart bar (desktop + mobile, per the reference).
             Left half shows the live free-delivery progress when the admin runs the
             free_above_minimum strategy; right button opens the cart drawer. On mobile it
             sits just above the bottom nav, and lifts further on the product page when
             that page's own sticky Add-to-Cart bar appears (`sticky-bar-toggled`). --}}
        {{-- hidden on cart/checkout — those pages have their own progress bar + path forward --}}
        @auth
            @unless (request()->routeIs('cart.index') || request()->routeIs('checkout.show'))
            <div x-data="{ stickyBarVisible: false }"
                 @sticky-bar-toggled.window="stickyBarVisible = $event.detail.visible"
                 x-show="$store.cart.count > 0" x-cloak
                 x-transition:enter="transition ease-out duration-400"
                 x-transition:enter-start="opacity-0 translate-y-8"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-8"
                 :class="stickyBarVisible ? 'bottom-36 lg:bottom-5' : 'bottom-[4.6rem] lg:bottom-5'"
                 class="fixed transition-[bottom] duration-500 ease-out inset-x-0 z-[55] flex justify-center pointer-events-none px-3 sm:px-6">
                <div class="pointer-events-auto w-full max-w-3xl lg:max-w-4xl bg-white/95 backdrop-blur border border-gold-200/70 rounded-2xl shadow-2xl shadow-maroon-900/25 pl-3 pr-2 py-2 flex items-center gap-3">
                    <span class="hidden sm:flex w-10 h-10 rounded-full border-2 border-gold-400/70 bg-white items-center justify-center text-xl shrink-0">🛵</span>

                    {{-- free-delivery progress — live from $store.shop (admin changes need no reload) --}}
                    <div class="flex-1 min-w-0" x-show="$store.shop.deliveryFeeStrategy === 'free_above_minimum' && $store.shop.deliveryFreeMinOrder > 0">
                        <template x-if="$store.shop.amountToFreeDelivery($store.cart.subtotal()) > 0">
                            <div>
                                <p class="text-xs sm:text-sm font-semibold text-maroon-800 truncate">
                                    {{ __('Add items worth') }} ₹<span x-text="$store.shop.amountToFreeDelivery($store.cart.subtotal())"></span> {{ __('to get free delivery') }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] text-maroon-400 font-semibold shrink-0 tabular-nums">₹<span x-text="$store.cart.subtotal()"></span></span>
                                    <div class="flex-1 h-1.5 rounded-full bg-gold-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-pista-500 transition-all duration-500"
                                             :style="`width: ${Math.min(100, Math.round($store.cart.subtotal() / Math.max(1, $store.shop.deliveryFreeMinOrder) * 100))}%`"></div>
                                    </div>
                                    <span class="text-[10px] text-maroon-400 font-semibold shrink-0 tabular-nums">₹<span x-text="$store.shop.deliveryFreeMinOrder"></span></span>
                                    <span class="text-xs" aria-hidden="true">🔒</span>
                                </div>
                            </div>
                        </template>
                        <template x-if="$store.shop.amountToFreeDelivery($store.cart.subtotal()) === 0">
                            <p class="text-xs sm:text-sm font-bold text-pista-600 truncate">🎉 {{ __('Free delivery unlocked!') }} 🚚</p>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0" x-show="!($store.shop.deliveryFeeStrategy === 'free_above_minimum' && $store.shop.deliveryFreeMinOrder > 0)">
                        <p class="text-xs sm:text-sm font-semibold text-maroon-800 truncate">
                            {{ __('Your sweets are waiting') }} 🍬
                        </p>
                    </div>

                    <button type="button" @click="$store.cart.open = true"
                            class="shrink-0 flex items-center gap-2 bg-maroon-800 hover:bg-maroon-700 text-cream rounded-xl px-3.5 sm:px-5 py-2.5 shadow transition active:scale-95">
                        <span class="text-xs sm:text-sm font-semibold whitespace-nowrap">{{ __('View Cart') }} (<span x-text="$store.cart.count"></span>)</span>
                        <span class="text-xs sm:text-sm font-bold tabular-nums whitespace-nowrap">₹<span x-text="$store.cart.subtotal().toLocaleString('en-IN')"></span></span>
                    </button>
                </div>
            </div>
            @endunless
        @endauth

        @include('partials.bottom-nav')
    </div>

    <main>
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.announcement-banner')
    @if ($promoPopupEnabled ?? true)
        @include('partials.promo-popup')
    @endif
</body>
</html>
