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
                {{-- persistent notification center (order cancellations, etc.) — stays until marked read --}}
                <div class="relative" x-data="adminNotificationsBell()" @click.outside="open = false">
                    <button @click="toggle()" type="button" aria-label="Notifications" :aria-expanded="open"
                            class="relative p-2 rounded-full hover:bg-cream transition text-maroon-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
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
                         class="absolute right-0 mt-3 w-80 max-w-[calc(100vw-2rem)] rounded-2xl shadow-2xl border border-gold-300/50 bg-ivory overflow-hidden origin-top-right z-40">
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

                {{-- live support-chat shortcut — opens the floating widget (see below) instead of
                     navigating away, so replying never means leaving whatever page you're on --}}
                <button type="button" @click="window.dispatchEvent(new CustomEvent('open-support-widget'))" aria-label="Support chat"
                        class="relative p-2 rounded-full hover:bg-cream transition text-maroon-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                    <span x-show="supportUnread > 0" x-cloak
                          x-transition:enter="transition ease-out duration-300" x-transition:enter-start="scale-0" x-transition:enter-end="scale-100"
                          class="absolute -top-0.5 -right-0.5 min-w-[20px] h-5 px-1 rounded-full bg-red-600 text-white text-[11px] font-bold flex items-center justify-center"
                          x-text="supportUnread > 9 ? '9+' : supportUnread"></span>
                </button>

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
                {{-- a customer wrote in — ping + slide-in card opening the widget's thread view
                     directly (not a page navigation, so whatever the admin was doing stays put) --}}
                <template x-for="toast in supportToasts" :key="toast._key">
                    <button type="button"
                            @click="window.dispatchEvent(new CustomEvent('open-support-widget', { detail: { orderId: toast.order_id, summary: toast } })); supportToasts = supportToasts.filter((t) => t._key !== toast._key)"
                            class="block w-full text-left bg-white border-2 border-gold-400 rounded-xl shadow-xl p-4 hover:bg-cream/60 transition"
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
                    </button>
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

            {{-- ── global support-chat widget ──────────────────────────────────────────
                 Nested in this same adminNotifier() scope so it can read `supportUnread`
                 directly without a second poll. Fixed/floating like the customer-facing chat
                 widget: launcher is the header icon above; opens as a compact, resizable,
                 minimizable panel that floats over whatever admin page is behind it — an admin
                 can jump to Orders, look something up, and come straight back without losing
                 the conversation. Positioned at bottom-24 (not bottom-6) so it never collides
                 with the floating "scroll to refresh" button that shares this corner. --}}
            <div x-data="adminSupportWidget()" @keydown.escape.window="open && !minimized && closeChat()">

                {{-- minimized "resume" bar --}}
                <button x-show="open && minimized" x-cloak @click="restoreChat()" type="button"
                        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                        class="fixed bottom-24 right-6 z-[110] flex items-center gap-2.5 pl-2.5 pr-4 py-2.5 rounded-full bg-gradient-to-r from-maroon-700 to-maroon-800 text-cream shadow-xl shadow-maroon-900/30 hover:shadow-2xl active:scale-95 transition"
                        aria-label="{{ __('Resume support chat') }}">
                    <span class="w-8 h-8 shrink-0 rounded-full bg-gold-500 grid place-items-center text-base shadow-inner">💬</span>
                    <span class="text-sm font-semibold max-w-[9rem] truncate"
                          x-text="view === 'thread' && activeOrder ? activeOrder.customer_name : @js(__('Support Chat'))"></span>
                    <span x-show="supportUnread > 0" x-cloak class="min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold grid place-items-center animate-bounce"
                          x-text="supportUnread > 9 ? '9+' : supportUnread"></span>
                    <svg class="w-4 h-4 shrink-0 text-cream/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                    </svg>
                </button>

                {{-- panel --}}
                <div x-show="open && !minimized" x-cloak x-ref="widgetPanel"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-4 sm:scale-95"
                     :style="sizeStyle()"
                     class="fixed inset-x-0 top-0 h-[100dvh] z-[120] flex flex-col bg-ivory overflow-hidden
                            sm:inset-auto sm:top-auto sm:bottom-24 sm:right-6 sm:max-h-[calc(100dvh-3rem)] sm:rounded-3xl sm:border sm:border-gold-300/50 sm:shadow-2xl">

                    {{-- resize grip — desktop only, drags the panel's top-left corner --}}
                    <div @mousedown="startResize($event)"
                         class="hidden sm:flex absolute -top-0.5 -left-0.5 w-5 h-5 z-10 cursor-nwse-resize items-center justify-center text-cream/40 hover:text-cream/90 transition"
                         title="{{ __('Drag to resize') }}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" d="M20 4 4 20M20 12 12 20" />
                        </svg>
                    </div>

                    {{-- header --}}
                    <div class="relative shrink-0 bg-gradient-to-r from-maroon-800 to-maroon-700 px-4 py-3.5 flex items-center gap-3 overflow-hidden">
                        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #e9c873 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>

                        <button x-show="view === 'thread'" x-cloak @click="backToInbox()" type="button" aria-label="{{ __('All conversations') }}"
                                class="relative shrink-0 w-9 h-9 grid place-items-center rounded-full text-cream/80 hover:text-cream hover:bg-cream/10 active:scale-90 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                        </button>
                        <span x-show="view === 'inbox'" class="relative w-10 h-10 shrink-0 rounded-full bg-gold-500 grid place-items-center text-xl shadow-inner">💬</span>
                        <span x-show="view === 'thread'" x-cloak
                              class="relative w-10 h-10 shrink-0 rounded-full bg-gradient-to-br from-maroon-600 to-maroon-900 text-cream font-display font-bold grid place-items-center text-lg"
                              x-text="(activeOrder?.customer_name || '?').trim().charAt(0).toUpperCase()"></span>

                        <div class="relative min-w-0 flex-1">
                            <p class="font-display font-semibold text-cream leading-tight truncate"
                               x-text="view === 'thread' ? (activeOrder?.customer_name || @js(__('Support Chat'))) : @js(__('Support Chat'))"></p>
                            <p class="text-[11px] text-cream/70 truncate"
                               x-text="view === 'thread' ? (activeOrder?.order_number || '') : (conversations.length + ' ' + @js(__('conversations')))"></p>
                        </div>

                        <div class="relative flex items-center gap-0.5 shrink-0">
                            <button @click="minimizeChat()" type="button" aria-label="{{ __('Minimize chat') }}"
                                    class="w-9 h-9 grid place-items-center rounded-full text-cream/80 hover:text-cream hover:bg-cream/10 active:scale-90 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4"><path stroke-linecap="round" d="M5 12h14"/></svg>
                            </button>
                            <button @click="toggleExpand()" type="button"
                                    :aria-label="expanded ? @js(__('Restore chat size')) : @js(__('Maximize chat'))"
                                    class="hidden sm:grid w-9 h-9 place-items-center rounded-full text-cream/80 hover:text-cream hover:bg-cream/10 active:scale-90 transition">
                                <svg x-show="!expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/>
                                </svg>
                                <svg x-show="expanded" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"/>
                                </svg>
                            </button>
                            <button @click="closeChat()" type="button" aria-label="{{ __('Close chat') }}"
                                    class="w-9 h-9 grid place-items-center rounded-full text-cream/80 hover:text-cream hover:bg-cream/10 active:scale-90 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- inbox view: conversation list --}}
                    <div x-show="view === 'inbox'" class="flex-1 overflow-y-auto overscroll-contain">
                        <div x-show="conversations.length === 0" x-cloak class="h-full flex flex-col items-center justify-center text-center px-6">
                            <span class="text-4xl mb-2">💬</span>
                            <p class="text-sm text-maroon-400">{{ __('No support conversations yet') }}</p>
                        </div>
                        <div class="divide-y divide-gold-100">
                            <template x-for="c in conversations" :key="c.order_id">
                                <button type="button" @click="openThread(c.order_id, c)"
                                        class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-cream/60 transition text-left"
                                        :class="c.unread > 0 && 'bg-gold-50/60'">
                                    <span class="w-10 h-10 shrink-0 rounded-full bg-gradient-to-br from-maroon-600 to-maroon-800 text-cream font-display font-bold grid place-items-center"
                                          x-text="(c.customer_name || '?').trim().charAt(0).toUpperCase()"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="font-semibold text-maroon-800 truncate text-sm" x-text="c.customer_name"></p>
                                            <span class="text-[11px] text-maroon-400 font-mono shrink-0" x-text="c.order_number"></span>
                                        </div>
                                        <p class="text-xs mt-0.5 truncate" :class="c.unread > 0 ? 'text-maroon-800 font-semibold' : 'text-maroon-500'">
                                            <span x-show="c.last_sender === 'admin'" class="text-maroon-400 font-normal">{{ __('You:') }} </span><span x-text="c.snippet"></span>
                                        </p>
                                    </div>
                                    <div class="shrink-0 text-right space-y-1">
                                        <p class="text-[10px] text-maroon-400" x-text="c.last_at"></p>
                                        <span x-show="c.unread > 0" x-cloak
                                              class="inline-flex min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold items-center justify-center"
                                              x-text="c.unread > 9 ? '9+' : c.unread"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- thread view: one order's messages --}}
                    <template x-if="view === 'thread'">
                        <div class="flex-1 flex flex-col overflow-hidden">
                            {{-- quick actions: keeps "manage the order while chatting" one tap away --}}
                            <div class="shrink-0 px-4 py-2 border-b border-gold-100 bg-cream/60 flex items-center gap-2 text-xs">
                                <a x-show="activeOrder?.customer_phone" x-cloak :href="'tel:' + activeOrder?.customer_phone"
                                   class="font-semibold text-maroon-700 border border-gold-300/70 hover:border-gold-400 hover:bg-white rounded-lg px-3 py-1.5 transition">📞 {{ __('Call') }}</a>
                                <a :href="'/admin/orders/' + activeOrderId" target="_blank" rel="noopener"
                                   class="font-semibold text-maroon-700 border border-gold-300/70 hover:border-gold-400 hover:bg-white rounded-lg px-3 py-1.5 transition">🧾 {{ __('View Order') }}</a>
                            </div>

                            <div x-ref="widgetThread" class="flex-1 overflow-y-auto overscroll-contain px-4 py-4">
                                <div x-show="messages.length === 0" x-cloak class="h-full flex flex-col items-center justify-center text-center">
                                    <span class="text-4xl">💬</span>
                                    <p class="text-sm text-maroon-400 mt-2">{{ __('No messages yet — say hello!') }}</p>
                                </div>
                                <template x-for="m in messages" :key="m.id">
                                    <div>
                                        <div x-show="m.showDay" class="text-center my-3">
                                            <span class="text-[10px] font-semibold text-maroon-400 bg-cream border border-gold-200/60 rounded-full px-3 py-1" x-text="m.day"></span>
                                        </div>
                                        <div class="flex items-end gap-2 mt-2" :class="m.sender === 'admin' ? 'justify-end' : 'justify-start'">
                                            <span x-show="m.sender === 'customer'"
                                                  class="w-7 h-7 shrink-0 rounded-full bg-maroon-100 border border-maroon-400/30 grid place-items-center text-[11px] font-bold text-maroon-700 mb-4"
                                                  x-text="(activeOrder?.customer_name || '?').trim().charAt(0).toUpperCase()"></span>
                                            <div class="max-w-[75%]">
                                                <a x-show="m.image_url" x-cloak :href="m.image_url" target="_blank" rel="noopener"
                                                   class="block max-w-[210px] rounded-2xl overflow-hidden border border-gold-200/60 shadow-sm mb-1">
                                                    <img :src="m.image_url" class="w-full h-auto object-cover" loading="lazy" alt="">
                                                </a>
                                                <div x-show="m.message" x-cloak
                                                     class="px-3.5 py-2.5 text-sm leading-relaxed whitespace-pre-line break-words shadow-sm"
                                                     :class="m.sender === 'admin'
                                                         ? 'bg-gradient-to-br from-maroon-700 to-maroon-800 text-cream rounded-2xl rounded-br-md'
                                                         : 'bg-white text-maroon-800 border border-gold-200/70 rounded-2xl rounded-bl-md'"
                                                     x-text="m.message"></div>
                                                <p class="text-[10px] text-maroon-400 mt-1 px-1" :class="m.sender === 'admin' && 'text-right'" x-text="m.time"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- composer --}}
                            <div class="shrink-0 border-t border-gold-200/60 bg-white px-3 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                                <p x-show="sendError" x-cloak class="text-red-600 text-xs mb-2 px-1" x-text="sendError"></p>
                                <div x-show="pendingImagePreview" x-cloak class="relative inline-block mb-2 ml-1">
                                    <img :src="pendingImagePreview" class="h-16 w-16 object-cover rounded-xl border border-gold-300/60 shadow-sm">
                                    <button @click="clearPendingImage()" type="button" aria-label="{{ __('Remove photo') }}"
                                            class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-maroon-800 text-cream text-xs grid place-items-center shadow">✕</button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input x-ref="widgetImageInput" type="file" accept="image/*" class="hidden" @change="onImageSelected($event)">
                                    <button @click="$refs.widgetImageInput.click()" type="button" aria-label="{{ __('Attach a photo') }}"
                                            class="shrink-0 w-10 h-10 rounded-full grid place-items-center text-maroon-500 hover:text-maroon-700 hover:bg-cream transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 4.5h16.5a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5Zm10.125 3.375a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    </button>
                                    <input x-ref="widgetInput" x-model="draft" @keydown.enter.prevent="send()"
                                           type="text" maxlength="1000" placeholder="{{ __('Type a reply…') }}"
                                           class="flex-1 min-w-0 rounded-full border border-gold-300/70 bg-cream/50 px-4 py-2.5 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                                    <button @click="send()" :disabled="sending || (!draft.trim() && !pendingImage)" type="button" aria-label="{{ __('Send message') }}"
                                            class="shrink-0 w-11 h-11 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 text-maroon-900 grid place-items-center shadow-md hover:shadow-lg active:scale-90 transition disabled:opacity-40 disabled:shadow-none">
                                        <svg class="w-5 h-5 translate-x-[1px]" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
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
