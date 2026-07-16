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
    @endphp
    <script>
        window.__mbIsLoggedIn = @json(Auth::check());
        window.__mbShopStatus = @json($shopStatus);
        window.__mbCartItems = @json($cartDrawerItems);
    </script>
    <title>@yield('title', 'Makhanbhog Sweets — No. 1 Sweet Shop in Thuthibari')</title>
    <meta name="description" content="Makhanbhog Sweets, Thuthibari's favourite sweet shop, now delivering fresh mithai to your doorstep.">

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
    <div x-data="{ authOpen: false }" @open-auth-modal.window="authOpen = true">
        @include('partials.navbar')
        @include('partials.auth-modal')
        @include('partials.cart-drawer')

        {{-- live toast when the shop's accepting-orders status changes while browsing --}}
        <div x-show="$store.shop.toast" x-cloak
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed top-4 inset-x-0 z-[70] flex justify-center px-4 pointer-events-none">
            <div class="max-w-md bg-maroon-800 text-cream rounded-xl shadow-2xl px-5 py-3 text-sm font-medium text-center pointer-events-auto"
                 x-text="$store.shop.toast"></div>
        </div>

        {{-- floating "Go to Cart" button — mobile only (the sticky navbar's cart icon already
             covers desktop; on mobile it's a thumb-reach shortcut once something's in the cart).
             Stays low, just above the bottom nav bar, by default. On the product detail page,
             once that page's own sticky "Add to Cart" bar scrolls into view, it smoothly lifts
             to sit just above it instead — see the `sticky-bar-toggled` event productPage()
             dispatches in app.js. --}}
        {{-- pointless on the cart page itself, and on checkout it just covers the form fields
             (see screenshot report) — both pages already have their own clear path forward --}}
        @auth
            @unless (request()->routeIs('cart.index') || request()->routeIs('checkout.show'))
            <div x-data="{
                     count: {{ (int) Auth::user()->cart()->sum('quantity') }},
                     previewImages: (window.__mbCartItems || []).slice(-2).reverse().map(i => i.image),
                     bump: false,
                     stickyBarVisible: false,
                 }"
                 @cart-updated.window="
                     if ($event.detail.count > count) { bump = false; $nextTick(() => bump = true); setTimeout(() => bump = false, 500); }
                     count = $event.detail.count;
                     if ($event.detail.items) previewImages = $event.detail.items.slice(-2).reverse().map(i => i.image);
                 "
                 @sticky-bar-toggled.window="stickyBarVisible = $event.detail.visible"
                 x-show="count > 0" x-cloak
                 x-transition:enter="transition ease-out duration-400"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-90"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-90"
                 :class="stickyBarVisible ? 'bottom-36' : 'bottom-20'"
                 class="lg:hidden fixed transition-[bottom] duration-500 ease-out inset-x-0 z-[55] flex justify-center pointer-events-none px-5">
                <button type="button" @click="$store.cart.open = true"
                        :class="bump && 'animate-fab-bump'"
                        class="pointer-events-auto relative flex items-center gap-2 pl-1 pr-1.5 py-1 rounded-full bg-gradient-to-r from-maroon-700 to-maroon-600 text-cream shadow-lg shadow-maroon-900/30 active:scale-95 transition-transform">
                    {{-- overlapping thumbnails of what's actually in the cart --}}
                    <span class="flex items-center shrink-0">
                        <template x-if="previewImages.length === 0">
                            <span class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.693 2.602-7.152.232-.94-.437-1.85-1.402-1.85H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                </svg>
                            </span>
                        </template>
                        <template x-for="(img, i) in previewImages" :key="img + i">
                            <span class="w-8 h-8 rounded-full bg-white ring-2 ring-maroon-600 overflow-hidden -ml-3 first:ml-0" :style="`z-index: ${10 - i}`">
                                <img :src="img" class="w-full h-full object-cover" alt="">
                            </span>
                        </template>
                    </span>

                    <span class="text-left mr-1">
                        <span class="block font-display font-semibold text-[13px] leading-tight whitespace-nowrap">{{ __('View Cart') }}</span>
                        <span class="block text-[11px] text-cream/70 leading-tight whitespace-nowrap" x-text="count + ' ' + (count === 1 ? '{{ __('Item') }}' : '{{ __('Items') }}')"></span>
                    </span>

                    <span class="w-7 h-7 rounded-full bg-white/15 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                </button>
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
