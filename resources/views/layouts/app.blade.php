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
<body class="antialiased">
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
    </div>

    <main>
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.promo-popup')
</body>
</html>
