{{-- Announcement bar --}}
<div x-show="$store.shop.accepting" x-cloak class="bg-maroon-900 text-gold-100 text-xs sm:text-sm text-center py-2 px-4 font-medium tracking-wide">
    ✨ {{ __('Fresh groceries delivered daily in Siswa Bazar — order online, pay cash on delivery') }}<span x-show="$store.shop.restricted" x-cloak> · 📍 {{ __('delivering within') }} <span x-text="$store.shop.radiusKm"></span> {{ __('km of Siswa Bazar only') }}</span>
</div>
<div x-show="!$store.shop.accepting" x-cloak class="bg-red-700 text-white text-xs sm:text-sm text-center py-2 px-4 font-semibold tracking-wide">
    🚫 {{ __("We're not accepting online orders right now — please check back soon. You can still browse our store!") }}
</div>

{{-- rain fee banner — subtle falling-raindrop accent, respects prefers-reduced-motion --}}
<div x-show="$store.shop.rainFeeEnabled" x-cloak class="relative overflow-hidden bg-sky-700 text-white text-xs sm:text-sm text-center py-2 px-4 font-medium tracking-wide">
    <span class="relative z-10" x-text="$store.shop.rainFeeMessage"></span>
    <span class="raindrop" style="left: 8%; animation-delay: 0s;"></span>
    <span class="raindrop" style="left: 22%; animation-delay: 0.4s;"></span>
    <span class="raindrop" style="left: 46%; animation-delay: 0.8s;"></span>
    <span class="raindrop" style="left: 68%; animation-delay: 0.2s;"></span>
    <span class="raindrop" style="left: 88%; animation-delay: 0.6s;"></span>
</div>

{{-- high demand fee banner (only shown in "fee" mode — "stop" mode's message appears at
     checkout instead, where it actually blocks the action) --}}
<div x-show="$store.shop.highDemandMode === 'fee'" x-cloak class="bg-amber-600 text-white text-xs sm:text-sm text-center py-2 px-4 font-medium tracking-wide">
    <span x-text="$store.shop.highDemandFeeMessage"></span>
</div>

<style>
    @keyframes raindrop-fall {
        0% { transform: translateY(-10px); opacity: 0; }
        20% { opacity: 0.8; }
        100% { transform: translateY(28px); opacity: 0; }
    }
    .raindrop {
        position: absolute;
        top: 0;
        width: 2px;
        height: 10px;
        background: rgba(255, 255, 255, 0.55);
        border-radius: 2px;
        animation: raindrop-fall 1.6s linear infinite;
    }
    @media (prefers-reduced-motion: reduce) {
        .raindrop { display: none; }
    }
</style>

@php
    // "Deliver to" data for the header — the default (or most recent) saved address, plus the
    // rest for the switcher dropdown. Guests just see an area hint that opens the auth modal.
    $headerAddresses = Auth::check()
        ? Auth::user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get()
            ->map(fn ($a) => ['id' => $a->id, 'label' => $a->label, 'line' => $a->address_line, 'is_default' => $a->is_default])
            ->values()
        : collect();
@endphp

<header x-data="{
            open: false,
            // measuring the real navbar and anchoring the drawer just below it (so the navbar
            // stayed peeking through on top) turned out fragile in practice — the measurement
            // could go stale after a scroll or a layout shift, landing the drawer at the wrong
            // height. Simpler and reliable: the drawer is a true full-screen overlay (fixed,
            // inset-0), above the navbar in z-index, with its own header taking over navigation
            // while it's open — no measurement, no edge cases tied to scroll position.
            scrollY: 0,
            init() {
                // plain `overflow: hidden` on body resets the visible scroll offset to 0 the
                // instant it's applied in most browsers — locking the background this way would
                // leave the customer back at the top of the page once they close the menu. Pinning
                // body in place at its current scroll offset (and restoring + re-scrolling on
                // close) is the standard fix, and also the one technique that reliably blocks
                // background scroll on iOS Safari, where plain overflow:hidden is known to leak.
                //
                // scrollY itself is captured in toggleMenu() below, synchronously, before `open`
                // changes — reading it here in the $watch callback instead was capturing 0 every
                // time: by the time this reactive callback ran, Alpine had already teleported the
                // (now full-viewport-height) drawer to the end of <body>, and that DOM insertion
                // was enough to trigger the browser's scroll anchoring and jump the page first.
                this.$watch('open', (isOpen) => {
                    if (isOpen) {
                        document.body.style.position = 'fixed';
                        document.body.style.top = `-${this.scrollY}px`;
                        document.body.style.width = '100%';
                    } else {
                        document.body.style.position = '';
                        document.body.style.top = '';
                        document.body.style.width = '';
                        window.scrollTo(0, this.scrollY);
                    }
                });
            },
            toggleMenu() {
                if (!this.open) this.scrollY = window.scrollY;
                this.open = !this.open;
            },
        }" class="sticky top-0 z-50 bg-cream/95 backdrop-blur text-maroon-900 shadow-sm border-b border-gold-300/40">
    <nav class="relative w-full px-3 sm:px-6 lg:px-10 py-2.5 sm:py-3">
        <div class="max-w-[1760px] mx-auto flex items-center justify-between gap-2 lg:gap-4">
        <a href="/" class="flex items-center gap-2 sm:gap-3 z-10 min-w-0 shrink-0">
            <img src="{{ $businessLogo }}" alt="Shree Vinayak Family Shop" class="h-9 w-9 sm:h-11 sm:w-11 lg:h-12 lg:w-12 shrink-0 rounded-full object-cover bg-white">
            {{-- desktop-only stacked wordmark — hidden below sm so mobile shows just the logo icon --}}
            <span class="hidden sm:flex flex-col justify-center leading-tight min-w-0">
                <span class="font-display text-sm lg:text-base font-bold tracking-wide text-gold-600 truncate">Shree Vinayak</span>
                <span class="font-display text-sm lg:text-base font-bold tracking-wide text-maroon-800 truncate">{{ __('Family Shop') }}</span>
            </span>
        </a>

        {{-- delivery-time badge (desktop) — live via $store.shop, hidden when the admin blanks it --}}
        <div x-show="$store.shop.deliveryTimeMinutes > 0" x-cloak
             class="hidden lg:flex items-center gap-1.5 rounded-xl border border-gold-300/60 bg-white px-3 py-2 shadow-sm shrink-0">
            <span class="text-gold-500 text-base leading-none">⚡</span>
            <span class="leading-tight">
                <span class="block text-sm font-bold text-maroon-800"><span x-text="$store.shop.deliveryTimeMinutes"></span> {{ __('mins') }}</span>
                <span class="block text-[10px] text-maroon-400">{{ __('Delivery Time') }}</span>
            </span>
        </div>

        {{-- compact twin of the badge above, mobile only — sits between the logo and the icon
             cluster so that row doesn't read as a big empty gap now that the wordmark is
             logo-only on mobile --}}
        <div x-show="$store.shop.deliveryTimeMinutes > 0" x-cloak
             class="flex lg:hidden items-center gap-1 rounded-lg bg-gold-400 text-maroon-900 px-2.5 py-1.5 text-xs font-bold shrink-0 shadow-sm">
            ⚡ <span x-text="$store.shop.deliveryTimeMinutes"></span> {{ __('mins') }}
        </div>

        {{-- search (desktop center) --}}
        <div class="hidden lg:block flex-1 max-w-xl">
            @include('partials.header-search')
        </div>

        {{-- deliver-to (desktop) --}}
        <div class="hidden lg:block shrink-0 max-w-[230px]">
            @include('partials.deliver-to')
        </div>

        <div class="flex items-center gap-0.5 sm:gap-2 z-10 shrink-0">
            @include('partials.language-switcher')

            {{-- wishlist --}}
            <a href="{{ route('account', ['tab' => 'favorites']) }}" aria-label="My favorites"
               @guest @click="$event.preventDefault(); window.dispatchEvent(new CustomEvent('open-auth-modal'))" @endguest
               class="hidden sm:flex relative w-8 h-8 sm:w-10 sm:h-10 rounded-full items-center justify-center hover:bg-maroon-900/5 transition">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
            </a>

            <div class="relative"
                 x-data="{ count: {{ Auth::check() ? (int) Auth::user()->cart()->sum('quantity') : 0 }}, isLoggedIn: {{ Auth::check() ? 'true' : 'false' }}, bump: false }"
                 @cart-updated.window="
                     if ($event.detail.count > count) { bump = false; $nextTick(() => bump = true); setTimeout(() => bump = false, 500); }
                     count = $event.detail.count;
                 ">
                <a href="{{ route('cart.index') }}" aria-label="View cart"
                   @click="$event.preventDefault(); isLoggedIn ? ($store.cart.open = true) : window.dispatchEvent(new CustomEvent('open-auth-modal'))"
                   class="relative w-8 h-8 sm:w-11 sm:h-11 rounded-full flex items-center justify-center hover:bg-maroon-900/5 transition">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.693 2.602-7.152.232-.94-.437-1.85-1.402-1.85H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    <span x-show="count > 0" x-cloak x-text="count > 9 ? '9+' : count"
                          :class="bump && 'animate-heart-pop'"
                          class="absolute top-0 right-0 min-w-[18px] h-[18px] px-1 rounded-full bg-maroon-700 text-cream text-[10px] font-bold flex items-center justify-center leading-none"></span>
                </a>
            </div>

            @auth
                {{-- notifications bell — the panel is `fixed` with its top/right computed in JS
                     (notificationsBell().positionPanel()) rather than pinned via a CSS anchor.
                     The bell isn't the last icon in the cluster (avatar + hamburger follow it), so
                     a purely CSS-anchored panel either overflowed off-screen on narrow phones (when
                     anchored to the bell's own box) or landed with an awkward gap past the bell
                     (when anchored to <nav>'s far edge). JS positioning aligns the panel's right
                     edge with the bell's right edge whenever there's room, and only slides it
                     rightward the minimum needed to stay on-screen. --}}
                <div x-data="notificationsBell()" @click.outside="open = false" @resize.window="open && positionPanel()">
                    <button @click="toggle()" type="button" aria-label="Notifications" :aria-expanded="open" x-ref="bellButton"
                            class="relative w-8 h-8 sm:w-11 sm:h-11 rounded-full flex items-center justify-center hover:bg-maroon-900/5 transition">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread"
                              class="absolute top-0 right-0 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center leading-none animate-heart-pop"></span>
                    </button>

                    <div x-show="open" x-cloak x-ref="panel"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                         class="fixed w-80 max-w-[calc(100vw-1.5rem)] rounded-2xl shadow-2xl border border-gold-300/50 bg-ivory overflow-hidden origin-top-right">
                        <div class="bg-gradient-to-br from-maroon-800 to-maroon-600 px-5 py-3.5 flex items-center justify-between">
                            <p class="text-cream font-display font-bold text-sm">🔔 Notifications</p>
                            <div class="flex items-center gap-2.5">
                                <span x-show="unread > 0" x-cloak class="text-[10px] font-bold bg-gold-400 text-maroon-900 rounded-full px-2 py-0.5" x-text="`${unread} new`"></span>
                                <button x-show="notifications.length > 0" x-cloak type="button" @click="clearAll()"
                                        class="text-[11px] font-medium text-cream/70 hover:text-cream underline underline-offset-2 transition">
                                    Clear all
                                </button>
                            </div>
                        </div>

                        <div class="max-h-80 overflow-y-auto divide-y divide-gold-100">
                            <template x-for="n in notifications" :key="n.id">
                                <div class="group px-4 py-3 transition-colors duration-500 flex items-start gap-2" :class="!n.read && 'bg-gold-50'">
                                    <div class="min-w-0 flex-1" :class="n.url && 'cursor-pointer'" @click="n.url && (window.location.href = n.url)">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-sm font-semibold text-maroon-800" x-text="n.title"></p>
                                            <span class="text-[10px] text-maroon-400 shrink-0 mt-0.5 whitespace-nowrap" x-text="n.time"></span>
                                        </div>
                                        <p class="text-xs text-maroon-600 mt-1 whitespace-pre-line" x-text="n.message"></p>
                                    </div>
                                    <button type="button" @click="clear(n.id)" aria-label="Clear notification"
                                            class="shrink-0 w-5 h-5 rounded-full flex items-center justify-center text-maroon-300 hover:text-maroon-700 hover:bg-gold-100 transition text-sm leading-none">✕</button>
                                </div>
                            </template>

                            <div x-show="notifications.length === 0" class="px-4 py-8 text-center">
                                <p class="text-2xl mb-1">🔕</p>
                                <p class="text-xs text-maroon-400">No notifications yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endauth

            @guest
                <button @click="authOpen = true" class="btn-gold text-xs sm:text-sm px-3 sm:px-5 py-2 sm:py-2.5 ml-1">
                    {{ __('Login') }}
                </button>
            @else
                <div x-data="{ menuOpen: false }" @click.outside="menuOpen = false" class="relative">
                    <button @click="menuOpen = !menuOpen" aria-label="Account menu" aria-haspopup="true" :aria-expanded="menuOpen"
                        class="w-8 h-8 sm:w-11 sm:h-11 rounded-full border-2 border-gold-400/70 bg-gradient-to-br from-gold-200 to-cream text-maroon-800 flex items-center justify-center transition shadow-sm hover:shadow-md hover:scale-105 font-display font-bold text-xs sm:text-base"
                        x-text="$store.user.name.charAt(0).toUpperCase()">
                    </button>

                    {{-- shahi dropdown --}}
                    <div x-show="menuOpen" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                         class="absolute right-0 mt-3 w-64 rounded-2xl shadow-2xl border border-gold-300/50 bg-ivory overflow-hidden origin-top-right">

                        <div class="bg-gradient-to-br from-maroon-800 to-maroon-600 px-5 py-4">
                            <p class="font-hindi text-gold-300 text-xs tracking-wide">नमस्ते 👑</p>
                            <p class="text-cream font-display font-bold text-base truncate mt-0.5" x-text="$store.user.name"></p>
                        </div>

                        <div class="py-2">
                            <a href="{{ route('account') }}" @click="menuOpen = false" class="flex items-center gap-3 px-5 py-3 text-sm text-maroon-700 hover:bg-gold-50 transition">
                                <svg class="w-5 h-5 text-gold-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                {{ __('My Account') }}
                            </a>
                            <a href="{{ route('account', ['tab' => 'favorites']) }}" @click="menuOpen = false" class="flex items-center gap-3 px-5 py-3 text-sm text-maroon-700 hover:bg-gold-50 transition">
                                <svg class="w-5 h-5 text-gold-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                </svg>
                                {{ __('My Favorites') }}
                            </a>
                            <a href="{{ route('cart.index') }}" @click="menuOpen = false" class="flex items-center gap-3 px-5 py-3 text-sm text-maroon-700 hover:bg-gold-50 transition">
                                <svg class="w-5 h-5 text-gold-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.693 2.602-7.152.232-.94-.437-1.85-1.402-1.85H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                </svg>
                                {{ __('My Cart') }}
                            </a>
                            <a href="{{ route('account', ['tab' => 'orders']) }}" @click="menuOpen = false" class="flex items-center gap-3 px-5 py-3 text-sm text-maroon-700 hover:bg-gold-50 transition">
                                <svg class="w-5 h-5 text-gold-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
                                </svg>
                                {{ __('My Orders') }}
                            </a>
                        </div>

                        <div class="border-t border-gold-200/70"></div>

                        <form method="POST" action="{{ route('logout') }}" class="p-2">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-maroon-600 hover:bg-maroon-50 rounded-xl transition font-medium">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                </svg>
                                {{ __('Logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endguest

            <button @click="toggleMenu()" class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-lg hover:bg-maroon-900/5 transition shrink-0" aria-label="Toggle menu">
                <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                <svg x-show="open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        </div>

        {{-- mobile: deliver-to row (delivery-time badge now lives in the top row, between the
             logo and the icon cluster), then full-width search --}}
        <div class="lg:hidden mt-2.5">
            @include('partials.deliver-to')
        </div>
        <div class="lg:hidden mt-2.5 pb-0.5">
            @include('partials.header-search')
        </div>
    </nav>

    {{-- main menu — a full-screen slide-in drawer, above the navbar and bottom nav (both z-50)
         in stacking order, so it takes over navigation entirely while open rather than trying to
         leave the real navbar peeking through above it. Teleported to <body> (not left nested in
         <header>): a positioned element with an explicit z-index only ranks against ITS OWN
         siblings, and <nav> sits right alongside it inside <header> with no z-index of its own —
         left in place, the drawer would paint over the hamburger button and silently eat its own
         close click. Teleporting escapes that inner stacking context so z-[55] is judged against
         <header>/<bottom-nav> at the top level, where it actually matters. --}}
    <template x-teleport="body">
    <div x-show="open" x-cloak @click="open = false"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[55] bg-maroon-950/50 backdrop-blur-sm"></div>
    </template>

    @php
        // one array driving the icon-badge + staggered-entrance treatment below, instead of six
        // near-identical hand-written <a> blocks — each item's rise-in delay comes straight from
        // its position, so adding/reordering a link never requires touching a delay by hand
        $menuLinks = [
            ['href' => '#home', 'label' => __('Home'), 'paths' => ['M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75']],
            ['href' => route('products.index'), 'label' => __('Shop All'), 'paths' => ['M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m-3.75 0h15l-1.591 10.318a2.25 2.25 0 0 1-2.226 1.932H8.317a2.25 2.25 0 0 1-2.226-1.932L4.5 10.5Z']],
            ['href' => '#range', 'label' => __('Categories'), 'paths' => ['M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.048 8.287 8.287 0 0 0 9 9.6a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z', 'M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z']],
            ['href' => '#bestsellers', 'label' => __('Favourites'), 'paths' => ['M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z']],
            ['href' => '#about', 'label' => __('Our Range'), 'paths' => ['M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z']],
            ['href' => '#contact', 'label' => __('Contact'), 'paths' => ['M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z']],
        ];
        $accountLinks = [
            ['href' => route('account'), 'label' => __('My Account'), 'paths' => ['M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z']],
            ['href' => route('account', ['tab' => 'favorites']), 'label' => __('My Favorites'), 'paths' => ['M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z']],
            ['href' => route('cart.index'), 'label' => __('My Cart'), 'paths' => ['M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.693 2.602-7.152.232-.94-.437-1.85-1.402-1.85H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z']],
            ['href' => route('account', ['tab' => 'orders']), 'label' => __('My Orders'), 'paths' => ['M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z']],
        ];
    @endphp

    <template x-teleport="body">
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[55] w-full max-w-xs bg-ivory shadow-2xl ring-1 ring-black/5 rounded-l-3xl overflow-hidden flex flex-col">

        {{-- header: same dot-grid + glow-blob treatment as the account page's profile banner,
             so this reads as a considered part of the site rather than a bolted-on menu --}}
        <div class="sticky top-0 z-10 relative overflow-hidden bg-gradient-to-br from-maroon-800 via-maroon-700 to-maroon-600 px-5 py-5 flex items-center justify-between shrink-0">
            <div class="absolute inset-0 opacity-15 bg-dot-grid text-gold-200"></div>
            <div class="absolute -top-8 -right-4 w-28 h-28 rounded-full bg-gold-400/30 blur-2xl"></div>
            <div class="absolute -bottom-10 left-8 w-24 h-24 rounded-full bg-gold-500/20 blur-2xl"></div>
            <div class="relative">
                <p class="text-cream font-display font-bold text-xl">{{ __('Menu') }}</p>
                <p class="text-cream/80 text-xs mt-0.5">{{ __('Grocery Store') }} · Shree Vinayak</p>
            </div>
            <button @click="open = false" aria-label="Close menu"
                    class="relative w-9 h-9 rounded-full flex items-center justify-center text-cream/80 hover:text-cream bg-white/5 hover:bg-white/15 hover:rotate-90 transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="relative flex-1 overflow-y-auto overscroll-contain px-3 pt-4 pb-10 flex flex-col gap-0.5 text-sm font-medium"
             style="padding-bottom: max(2.5rem, env(safe-area-inset-bottom));">
            @foreach ($menuLinks as $i => $link)
                <a href="{{ $link['href'] }}" @click="open = false"
                   class="group flex items-center gap-3 px-2.5 py-2.5 rounded-2xl text-maroon-700 hover:bg-gold-50 hover:text-maroon-900 hover:shadow-sm transition-all duration-200 animate-rise-in"
                   style="animation-delay: {{ $i * 45 }}ms">
                    <span class="w-9 h-9 rounded-full bg-gold-50 group-hover:bg-gold-100 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-4 h-4 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                            @foreach ($link['paths'] as $path)
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                            @endforeach
                        </svg>
                    </span>
                    {{ $link['label'] }}
                    <svg class="w-4 h-4 ml-auto text-gold-400 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @endforeach

            <div class="flex items-center justify-between px-2.5 py-2.5 mt-2 border-t border-gold-200/60 pt-4 animate-rise-in" style="animation-delay: {{ count($menuLinks) * 45 }}ms">
                <span class="text-xs font-semibold text-maroon-400 uppercase tracking-wide">{{ __('Language') }}</span>
                @include('partials.language-switcher')
            </div>

            @guest
                <button @click="open = false; authOpen = true"
                        class="btn-gold text-center text-sm px-5 py-3.5 mt-3 mx-2.5 rounded-2xl shadow-md shadow-gold-400/30 hover:shadow-lg hover:shadow-gold-400/40 hover:-translate-y-0.5 transition-all duration-200 animate-rise-in"
                        style="animation-delay: {{ (count($menuLinks) + 1) * 45 }}ms">
                    {{ __('Login') }}
                </button>
            @else
                <div class="border-t border-gold-200/60 mt-2 pt-4">
                    <div class="flex items-center gap-3 px-2.5 pb-2 animate-rise-in" style="animation-delay: {{ (count($menuLinks) + 1) * 45 }}ms">
                        <span class="w-9 h-9 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center font-display font-bold text-maroon-900 text-sm shrink-0" x-text="$store.user.name.charAt(0).toUpperCase()"></span>
                        <p class="font-hindi text-maroon-700 text-sm">नमस्ते, <span class="font-semibold" x-text="$store.user.name.split(' ')[0]"></span> 👑</p>
                    </div>
                    @foreach ($accountLinks as $i => $link)
                        <a href="{{ $link['href'] }}" @click="open = false"
                           class="group flex items-center gap-3 px-2.5 py-2.5 rounded-2xl text-maroon-700 hover:bg-gold-50 hover:text-maroon-900 hover:shadow-sm transition-all duration-200 animate-rise-in"
                           style="animation-delay: {{ (count($menuLinks) + 2 + $i) * 45 }}ms">
                            <span class="w-9 h-9 rounded-full bg-gold-50 group-hover:bg-gold-100 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-4 h-4 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                    @foreach ($link['paths'] as $path)
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                                    @endforeach
                                </svg>
                            </span>
                            {{ $link['label'] }}
                            <svg class="w-4 h-4 ml-auto text-gold-400 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    @endforeach
                    <div class="border-t border-gold-200/60 mt-2 pt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-2.5 py-2.5 rounded-2xl text-maroon-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200 font-semibold">
                                <span class="w-9 h-9 rounded-full bg-maroon-50 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                    </svg>
                                </span>
                                {{ __('Logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>

        {{-- soft fade hinting at more content below, only where the list actually overflows --}}
        <div class="h-6 bg-gradient-to-t from-ivory to-transparent pointer-events-none -mt-6 shrink-0"></div>
    </div>
    </template>
</header>
