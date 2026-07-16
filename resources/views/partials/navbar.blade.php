{{-- Announcement bar --}}
<div x-show="$store.shop.accepting" x-cloak class="bg-maroon-900 text-gold-100 text-xs sm:text-sm text-center py-2 px-4 font-medium tracking-wide">
    ✨ {{ __('Fresh mithai made every morning in Thuthibari — order online, pay cash on delivery') }}<span x-show="$store.shop.restricted" x-cloak> · 📍 {{ __('delivering within') }} <span x-text="$store.shop.radiusKm"></span> {{ __('km of Thuthibari only') }}</span>
</div>
<div x-show="!$store.shop.accepting" x-cloak class="bg-red-700 text-white text-xs sm:text-sm text-center py-2 px-4 font-semibold tracking-wide">
    🚫 {{ __("We're not accepting online orders right now — please check back soon. You can still browse our sweets!") }}
</div>

<header x-data="{ open: false }" class="sticky top-0 z-50 bg-cream/95 backdrop-blur text-maroon-900 shadow-sm border-b border-gold-300/40">
    <nav class="relative w-full flex items-center justify-between px-3 sm:px-10 lg:px-16 py-3 gap-2">
        <a href="/" class="flex items-center gap-2 sm:gap-3 z-10 min-w-0">
            <img src="{{ asset('images/logo-circle.png') }}" alt="Makhanbhog Sweets" class="h-9 w-9 sm:h-12 sm:w-12 lg:h-14 lg:w-14 shrink-0">
            <span class="font-display text-sm sm:text-lg lg:text-xl font-bold tracking-wide leading-tight truncate">Makhanbhog <span class="text-gold-600">Sweets</span></span>
        </a>

        <div class="hidden md:flex items-center gap-8 text-sm font-medium absolute left-1/2 -translate-x-1/2">
            <a href="#home" class="hover:text-gold-600 transition">{{ __('Home') }}</a>
            <a href="{{ route('products.index') }}" class="hover:text-gold-600 transition">{{ __('Shop All') }}</a>
            <a href="#range" class="hover:text-gold-600 transition">{{ __('Our Kitchen') }}</a>
            <a href="#bestsellers" class="hover:text-gold-600 transition">{{ __('Favourites') }}</a>
            <a href="#about" class="hover:text-gold-600 transition">{{ __('Our Story') }}</a>
            <a href="#contact" class="hover:text-gold-600 transition">{{ __('Contact') }}</a>
        </div>

        <div class="flex items-center gap-0.5 sm:gap-3 z-10 shrink-0">
            @include('partials.language-switcher')

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
                {{-- notifications bell --}}
                <div class="relative" x-data="notificationsBell()" @click.outside="open = false">
                    <button @click="toggle()" type="button" aria-label="Notifications" :aria-expanded="open"
                            class="relative w-8 h-8 sm:w-11 sm:h-11 rounded-full flex items-center justify-center hover:bg-maroon-900/5 transition">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread"
                              class="absolute top-0 right-0 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center leading-none animate-heart-pop"></span>
                    </button>

                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                         class="absolute right-0 mt-3 w-80 max-w-[calc(100vw-2rem)] rounded-2xl shadow-2xl border border-gold-300/50 bg-ivory overflow-hidden origin-top-right">
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
                        class="w-8 h-8 sm:w-11 sm:h-11 rounded-full border-2 border-gold-400/70 bg-gradient-to-br from-gold-200 to-cream text-maroon-800 flex items-center justify-center transition shadow-sm hover:shadow-md hover:scale-105 font-display font-bold text-xs sm:text-base">
                        {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
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
                            <p class="text-cream font-display font-bold text-base truncate mt-0.5">{{ Auth::user()->name }}</p>
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

            <button @click="open = !open" class="md:hidden w-8 h-8 flex items-center justify-center rounded-lg hover:bg-maroon-900/5 transition shrink-0" aria-label="Toggle menu">
                <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                <svg x-show="open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </nav>

    <div x-show="open" x-cloak x-transition class="md:hidden bg-cream border-t border-gold-300/40 px-4 py-4 flex flex-col gap-4 text-sm font-medium">
        <a href="#home" @click="open = false" class="hover:text-gold-600 transition">{{ __('Home') }}</a>
        <a href="{{ route('products.index') }}" @click="open = false" class="hover:text-gold-600 transition">{{ __('Shop All') }}</a>
        <a href="#range" @click="open = false" class="hover:text-gold-600 transition">{{ __('Our Kitchen') }}</a>
        <a href="#bestsellers" @click="open = false" class="hover:text-gold-600 transition">{{ __('Favourites') }}</a>
        <a href="#about" @click="open = false" class="hover:text-gold-600 transition">{{ __('Our Story') }}</a>
        <a href="#contact" @click="open = false" class="hover:text-gold-600 transition">{{ __('Contact') }}</a>

        <div class="border-t border-gold-300/40 pt-4 flex items-center gap-3 text-sm font-semibold">
            <span class="text-maroon-400">{{ __('Language') }}:</span>
            @include('partials.language-switcher')
        </div>

        @guest
            <button @click="open = false; authOpen = true" class="btn-gold text-center text-sm px-5 py-2.5">{{ __('Login') }}</button>
        @else
            <div class="border-t border-gold-300/40 pt-4 mt-1">
                <p class="font-hindi text-gold-600 text-xs mb-2">नमस्ते, {{ explode(' ', Auth::user()->name)[0] }} 👑</p>
                <a href="{{ route('account') }}" @click="open = false" class="block py-2 hover:text-gold-600 transition">{{ __('My Account') }}</a>
                <a href="{{ route('account', ['tab' => 'favorites']) }}" @click="open = false" class="block py-2 hover:text-gold-600 transition">{{ __('My Favorites') }}</a>
                <a href="{{ route('cart.index') }}" @click="open = false" class="block py-2 hover:text-gold-600 transition">{{ __('My Cart') }}</a>
                <a href="{{ route('account', ['tab' => 'orders']) }}" @click="open = false" class="block py-2 hover:text-gold-600 transition">{{ __('My Orders') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="pt-1">
                    @csrf
                    <button type="submit" class="text-maroon-600 font-semibold py-2">{{ __('Logout') }}</button>
                </form>
            </div>
        @endguest
    </div>
</header>
