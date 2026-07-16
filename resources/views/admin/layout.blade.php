<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Makhanbhog Sweets</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.order-status-i18n')
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="antialiased font-body bg-cream min-h-screen">
    @php $latestOrderId = (int) (\App\Models\Order::max('id') ?? 0); @endphp
    <div class="flex h-screen overflow-hidden"
         x-data="adminNotifier({{ $latestOrderId }}, {{ request()->routeIs('admin.orders.index') ? 'true' : 'false' }})">
        <aside class="w-60 shrink-0 h-screen sticky top-0 bg-maroon-800 text-cream flex flex-col overflow-y-auto">
            <div class="px-6 py-6 border-b border-cream/10">
                <p class="font-display font-bold text-lg">Makhanbhog <span class="text-gold-400">Sweets</span></p>
                <p class="text-cream/50 text-xs mt-0.5">{{ __('Admin Panel') }}</p>
            </div>
            <nav class="flex-1 px-3 py-5 space-y-1 text-sm">
                @php
                    $navItems = [
                        ['route' => 'admin.dashboard', 'label' => __('Dashboard'), 'icon' => '📊'],
                        ['route' => 'admin.orders.index', 'label' => __('Orders'), 'icon' => '🧾'],
                        ['route' => 'admin.support.index', 'label' => __('Support Chat'), 'icon' => '💬', 'badge' => 'supportUnread'],
                        ['route' => 'admin.transactions.index', 'label' => __('Transactions'), 'icon' => '💳'],
                        ['route' => 'admin.customers.index', 'label' => __('Customers'), 'icon' => '👥'],
                        ['route' => 'admin.visitors.index', 'label' => __('Visitors'), 'icon' => '🌐'],
                        ['route' => 'admin.leads.index', 'label' => __('Shadi/Function Leads'), 'icon' => '💍'],
                        ['route' => 'admin.products.index', 'label' => __('Products'), 'icon' => '🍬'],
                        ['route' => 'admin.bestsellers.index', 'label' => __('Bestsellers'), 'icon' => '⭐'],
                        ['route' => 'admin.festival-special.index', 'label' => __('Festival Special'), 'icon' => '🎉'],
                        ['route' => 'admin.announcement.edit', 'label' => __('Announcement'), 'icon' => '📢'],
                        ['route' => 'admin.coupons.index', 'label' => __('Coupons'), 'icon' => '🏷️'],
                        ['route' => 'admin.riders.index', 'label' => __('Delivery Riders'), 'icon' => '🛵'],
                        ['route' => 'admin.configuration', 'label' => __('Configuration'), 'icon' => '⚙️'],
                    ];

                    // only a Super Admin can manage other admin accounts — see EnsureSuperAdmin
                    if (Auth::guard('admin')->user()?->isSuperAdmin()) {
                        $navItems[] = ['route' => 'admin.admins.index', 'label' => __('Admin Accounts'), 'icon' => '🛡️'];
                    }
                @endphp
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg transition {{ request()->routeIs($item['route'].'*') ? 'bg-gold-500 text-maroon-900 font-semibold' : 'text-cream/80 hover:bg-cream/10 hover:text-cream' }}">
                        <span>{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                        @if (!empty($item['badge']))
                            <span x-show="{{ $item['badge'] }} > 0" x-cloak
                                  class="ml-auto min-w-[20px] h-5 px-1 rounded-full bg-red-600 text-white text-[11px] font-bold flex items-center justify-center"
                                  x-text="{{ $item['badge'] }} > 9 ? '9+' : {{ $item['badge'] }}"></span>
                        @endif
                    </a>
                @endforeach
            </nav>
            <div class="px-3.5 pb-3 flex items-center gap-1 text-xs font-bold">
                <span class="text-cream/40 mr-1">🌐</span>
                <form method="POST" action="{{ route('admin.locale.switch', 'en') }}">
                    @csrf
                    <button type="submit" class="px-2 py-1 rounded transition {{ app()->getLocale() === 'en' ? 'bg-cream/15 text-cream' : 'text-cream/50 hover:text-cream' }}">EN</button>
                </form>
                <form method="POST" action="{{ route('admin.locale.switch', 'hi') }}">
                    @csrf
                    <button type="submit" class="font-hindi px-2 py-1 rounded transition {{ app()->getLocale() === 'hi' ? 'bg-cream/15 text-cream' : 'text-cream/50 hover:text-cream' }}">हिं</button>
                </form>
            </div>
            <div class="px-3 py-5 border-t border-cream/10">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg text-cream/70 hover:bg-cream/10 hover:text-cream transition text-sm">
                        <span>🚪</span><span>{{ __('Log Out') }}</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 min-w-0 h-screen overflow-y-auto" x-data="{ scrolledDown: false }" @scroll="scrolledDown = $event.target.scrollTop > 240">
            <header class="sticky top-0 z-30 bg-white border-b border-gold-200/60 px-8 py-4 flex items-center justify-between">
                <h1 class="font-display text-xl text-maroon-800">@yield('page-title', __('Dashboard'))</h1>

                <div class="flex items-center gap-1">
                {{-- live support-chat shortcut --}}
                <a href="{{ route('admin.support.index') }}" aria-label="Support chat"
                   class="relative p-2 rounded-full hover:bg-cream transition text-maroon-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                    <span x-show="supportUnread > 0" x-cloak
                          x-transition:enter="transition ease-out duration-300" x-transition:enter-start="scale-0" x-transition:enter-end="scale-100"
                          class="absolute -top-0.5 -right-0.5 min-w-[20px] h-5 px-1 rounded-full bg-red-600 text-white text-[11px] font-bold flex items-center justify-center"
                          x-text="supportUnread > 9 ? '9+' : supportUnread"></span>
                </a>

                {{-- live new-order bell --}}
                <button @click="openOrders()" type="button" aria-label="New orders"
                        class="relative p-2 rounded-full hover:bg-cream transition text-maroon-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <span x-show="pendingCount > 0" x-cloak
                          x-transition:enter="transition ease-out duration-300" x-transition:enter-start="scale-0" x-transition:enter-end="scale-100"
                          class="absolute -top-0.5 -right-0.5 min-w-[20px] h-5 px-1 rounded-full bg-red-600 text-white text-[11px] font-bold flex items-center justify-center"
                          x-text="pendingCount > 9 ? '9+' : pendingCount"></span>
                </button>
                </div>
            </header>

            {{-- new order popup: plays a chime for 10s and asks admin to accept/reject --}}
            <div x-show="activeOrder" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-maroon-900/60 backdrop-blur-sm"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     @click="dismiss()"></div>

                <template x-if="activeOrder">
                    <div class="relative bg-cream rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
                         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                        <div class="relative px-6 py-6 text-center overflow-hidden transition-colors duration-300"
                             :class="activeOrder.is_gift_order ? 'bg-gradient-to-r from-pink-500 via-gold-500 to-pink-500' : 'bg-gradient-to-r from-gold-400 to-gold-600'">
                            <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>
                            <p class="relative text-4xl animate-bounce" x-text="activeOrder.is_gift_order ? '🎁' : '🔔'"></p>
                            <p class="relative font-display font-bold text-xl mt-2" :class="activeOrder.is_gift_order ? 'text-white' : 'text-maroon-900'">
                                <span x-text='activeOrder.is_gift_order ? @json(__('Free Gift Order!')) : @json(__('New Order Received!'))'></span>
                            </p>
                            <p x-show="activeOrder.is_gift_order" x-cloak class="relative text-white/90 text-xs font-semibold mt-1">{{ __('This customer is claiming their loyalty reward') }} 🎉</p>
                        </div>

                        <div class="p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-display text-lg text-maroon-800" x-text="activeOrder.customer_name"></p>
                                    <p class="text-sm text-maroon-500" x-text="activeOrder.customer_phone"></p>
                                </div>
                                <p class="font-display font-bold text-2xl text-maroon-800 shrink-0">₹<span x-text="activeOrder.total"></span></p>
                            </div>

                            <div class="mt-4 bg-white rounded-xl border border-gold-200/60 divide-y divide-gold-100 max-h-40 overflow-y-auto">
                                <template x-for="item in activeOrder.items" :key="item.name">
                                    <div class="flex items-center justify-between px-4 py-2.5 text-sm" :class="item.is_gift && 'bg-gradient-to-r from-pink-50 to-gold-50'">
                                        <span class="text-maroon-700" x-text="item.name"></span>
                                        <span class="text-maroon-500">× <span x-text="item.quantity"></span></span>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4">
                                <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">{{ __('Delivery Address') }}</p>
                                <p class="text-sm text-maroon-700 mt-1" x-text="activeOrder.delivery_address"></p>
                                <p x-show="activeOrder.distance_km !== null" x-cloak class="text-xs text-maroon-500 mt-1.5">
                                    📍 <span x-text="activeOrder.distance_km"></span> {{ __('km from the shop') }}
                                </p>
                            </div>

                            <div class="mt-5">
                                <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">{{ __('Delivery time') }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <template x-for="mins in [20, 30, 45, 60]" :key="mins">
                                        <button type="button" @click="etaMinutes = mins"
                                                class="flex-1 text-sm font-semibold rounded-lg py-2 border transition"
                                                :class="etaMinutes === mins ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/80 hover:border-gold-400'">
                                            <span x-text="mins"></span> {{ __('min') }}
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center gap-3">
                                <button @click="respond('confirmed')" :disabled="acting" type="button"
                                        class="flex-1 bg-pista-500 hover:bg-pista-600 text-white font-semibold rounded-xl py-3 transition disabled:opacity-60">
                                    ✓ {{ __('Accept') }}
                                </button>
                                <button @click="respond('cancelled')" :disabled="acting" type="button"
                                        class="flex-1 bg-white border-2 border-red-300 hover:bg-red-50 text-red-600 font-semibold rounded-xl py-3 transition disabled:opacity-60">
                                    ✕ {{ __('Reject') }}
                                </button>
                            </div>
                            <button @click="dismiss()" type="button" class="w-full text-center text-xs text-maroon-400 hover:text-maroon-600 mt-3 transition">
                                {{ __('Decide later') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- top-right toast stack: support-chat messages + auto-cancel warnings share one
                 container so simultaneous toasts stack instead of overlapping --}}
            <div class="fixed top-4 right-4 z-[80] space-y-2.5 w-full max-w-sm px-4 sm:px-0">
                {{-- a customer wrote in — ping + slide-in card linking to the thread --}}
                <template x-for="toast in supportToasts" :key="toast._key">
                    <a :href="`/admin/support/${toast.order_id}`"
                       class="block bg-white border-2 border-gold-400 rounded-xl shadow-xl p-4 hover:bg-cream/60 transition"
                       x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-6" x-transition:enter-end="opacity-100 translate-x-0"
                       x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <div class="flex items-start gap-3">
                            <span class="text-xl shrink-0">💬</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-display font-semibold text-maroon-800 text-sm">
                                    <span x-text="toast.customer_name"></span>
                                    <span class="text-maroon-400 font-normal text-xs" x-text="toast.order_number"></span>
                                </p>
                                <p class="text-xs text-maroon-600 mt-0.5 truncate" x-text="toast.snippet"></p>
                                <p class="text-xs font-semibold text-gold-600 mt-1.5">{{ __('Reply') }} →</p>
                            </div>
                            <button type="button" @click.prevent.stop="supportToasts = supportToasts.filter((t) => t._key !== toast._key)"
                                    class="shrink-0 text-maroon-300 hover:text-maroon-600 transition text-lg leading-none">✕</button>
                        </div>
                    </a>
                </template>

                {{-- auto-cancel warnings: orders the shop failed to deliver within 90 min, self-cancelled --}}
                <template x-for="warning in autoCancelWarnings" :key="warning._key">
                    <div class="bg-white border-2 border-red-300 rounded-xl shadow-xl p-4 flex items-start gap-3"
                         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-6" x-transition:enter-end="opacity-100 translate-x-0"
                         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <span class="text-xl shrink-0">⏰</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-display font-semibold text-red-700 text-sm">{{ __('Order auto-cancelled') }}</p>
                            <p class="text-xs text-maroon-600 mt-0.5">
                                <span x-text="warning.order_number"></span> (<span x-text="warning.customer_name"></span>, ₹<span x-text="warning.total"></span>)
                                {{ __("wasn't delivered within 90 minutes and was cancelled automatically.") }}
                            </p>
                            <a :href="`/admin/orders/${warning.id}`" class="text-xs font-semibold text-gold-600 hover:text-gold-700 underline underline-offset-2 mt-1.5 inline-block">{{ __('View order') }} →</a>
                        </div>
                        <button type="button" @click="autoCancelWarnings = autoCancelWarnings.filter((w) => w._key !== warning._key)"
                                class="shrink-0 text-maroon-300 hover:text-maroon-600 transition text-lg leading-none">✕</button>
                    </div>
                </template>
            </div>

            <main class="p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-lg bg-pista-100 border border-pista-400/40 text-pista-600 text-sm px-4 py-3">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </main>

            {{-- floating refresh button — appears once scrolled down a page, handy on touchscreen
                 laptops where reaching for a physical refresh key mid-scroll is awkward --}}
            <button type="button" @click="location.reload()" x-show="scrolledDown" x-cloak
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-75"
                    aria-label="Refresh page"
                    class="fixed bottom-6 right-6 z-40 w-12 h-12 rounded-full bg-maroon-700 text-cream shadow-xl hover:bg-maroon-800 active:scale-90 transition flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
            </button>
        </div>
    </div>
</body>
</html>
