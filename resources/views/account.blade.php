@extends('layouts.app')

@section('title', 'My Account — Makhanbhog Sweets')

@section('content')
<section class="relative py-8 sm:py-12 bg-ivory min-h-[80vh]">
    {{-- ambient glow blobs, echoing the rest of the site --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-16 left-[12%] w-72 h-72 rounded-full bg-gold-400/20 blur-3xl"></div>
        <div class="absolute bottom-0 -right-12 w-80 h-80 rounded-full bg-maroon-400/10 blur-3xl"></div>
        <div class="absolute top-1/2 left-0 w-56 h-56 rounded-full bg-pista-400/10 blur-3xl"></div>
    </div>

    <div class="relative max-w-6xl lg:max-w-none mx-auto px-4 sm:px-6 lg:px-10 xl:px-16"
         x-data='accountPage(@json($addresses), @json($reward), {{ json_encode($initialTab) }}, @json($favoriteProducts->pluck("id")))'>

        <nav class="text-sm text-maroon-500 flex items-center gap-2 mb-6">
            <a href="/" class="hover:text-gold-600 transition">Home</a>
            <span class="text-gold-400">✦</span>
            <span class="text-maroon-700 font-medium">My Account</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-[250px_1fr] gap-6 items-start">

            {{-- ── sidebar ─────────────────────────────────────────────── --}}
            <div class="lg:sticky lg:top-28 animate-fade-up">
                {{-- mini profile (desktop only) --}}
                <div class="hidden lg:flex items-center gap-3 bg-white rounded-2xl border border-gold-200/60 shadow-sm p-4 mb-3">
                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 grid place-items-center text-lg font-display font-bold text-maroon-900 ring-2 ring-gold-300/50 ring-offset-2 ring-offset-white shrink-0">
                        {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-display font-semibold text-maroon-800 text-sm truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-maroon-400 truncate">{{ Auth::user()->phone }}</p>
                    </div>
                </div>

                <div class="flex lg:flex-col gap-2 overflow-x-auto lg:overflow-visible pb-1 lg:pb-0 -mx-4 px-4 lg:mx-0 lg:px-0 lg:bg-white lg:rounded-2xl lg:border lg:border-gold-200/60 lg:shadow-sm lg:p-3">
                    @foreach ([['profile', '👤', 'Profile'], ['orders', '🧾', 'My Orders'], ['favorites', '❤️', 'My Favorites'], ['addresses', '📍', 'Addresses'], ['rewards', '🎁', 'Rewards']] as [$key, $icon, $label])
                        <button type="button" @click="tab = '{{ $key }}'"
                                class="group shrink-0 flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 text-left whitespace-nowrap lg:w-full"
                                :class="tab === '{{ $key }}'
                                    ? 'bg-gradient-to-r from-maroon-700 to-maroon-600 text-cream shadow-md'
                                    : 'bg-white lg:bg-transparent text-maroon-600 border lg:border-0 border-gold-200/60 hover:bg-gold-50 hover:translate-x-0.5'">
                            <span class="transition-transform duration-200 group-hover:scale-110">{{ $icon }}</span>
                            <span>{{ $label }}</span>
                            <span x-show="tab === '{{ $key }}'" x-cloak class="hidden lg:block ml-auto w-1.5 h-1.5 rounded-full bg-gold-400"></span>
                            <span x-show="'{{ $key }}' === 'rewards' && reward.available > 0" x-cloak
                                  class="ml-auto lg:ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gold-400 text-maroon-900 animate-pulse" x-text="reward.available"></span>
                        </button>
                    @endforeach

                    <div class="hidden lg:block border-t border-gold-100 my-1.5"></div>

                    <form method="POST" action="{{ route('logout') }}" class="shrink-0 lg:w-full">
                        @csrf
                        <button type="submit"
                                class="group flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-white lg:bg-transparent text-red-500 border lg:border-0 border-red-200 hover:bg-red-50 hover:translate-x-0.5 transition-all duration-200 text-left whitespace-nowrap lg:w-full">
                            <span class="transition-transform duration-200 group-hover:scale-110">🚪</span><span>Log Out</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- ── content ─────────────────────────────────────────────── --}}
            <div class="min-w-0">

                {{-- ═══ profile ═══ --}}
                <div x-show="tab === 'profile'" x-cloak
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">

                    {{-- hero banner card --}}
                    <div class="bg-white rounded-3xl border border-gold-200/60 shadow-sm overflow-hidden animate-rise-in">
                        <div class="relative h-28 sm:h-32 bg-gradient-to-r from-maroon-800 via-maroon-600 to-maroon-800 overflow-hidden">
                            <div class="absolute inset-0 opacity-15" style="background-image: radial-gradient(circle, #e9c873 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>
                            <div class="absolute -top-10 right-[15%] w-40 h-40 rounded-full bg-gold-400/30 blur-2xl"></div>
                            <div class="absolute -bottom-12 left-[30%] w-48 h-48 rounded-full bg-gold-500/20 blur-3xl"></div>
                            {{-- gentle string of glowing bulbs, echoing the homepage canopy --}}
                            <div class="absolute top-3 inset-x-0 flex justify-around px-6">
                                @for ($i = 0; $i < 14; $i++)
                                    <span class="w-1.5 h-1.5 rounded-full bg-gold-300 animate-bulb-glow" style="animation-delay: {{ $i * 0.18 }}s"></span>
                                @endfor
                            </div>
                        </div>

                        <div class="relative px-6 sm:px-8 pb-6">
                            <div class="flex flex-col sm:flex-row sm:items-start gap-4 -mt-12">
                                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 grid place-items-center text-4xl font-display font-bold text-maroon-900 ring-4 ring-white shadow-xl shrink-0 animate-track-pop">
                                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                {{-- pushed down past the banner edge so the maroon name never sits on the maroon banner --}}
                                <div class="min-w-0 sm:mt-14">
                                    <p class="font-display font-bold text-2xl text-maroon-800 truncate">{{ Auth::user()->name }}</p>
                                    <div class="flex items-center gap-2 flex-wrap mt-1.5">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-maroon-600 bg-cream border border-gold-200/70 rounded-full px-3 py-1">📱 {{ Auth::user()->phone }}</span>
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-gold-700 bg-gold-100/70 border border-gold-300/60 rounded-full px-3 py-1">🗓️ Member since {{ Auth::user()->created_at->format('M Y') }}</span>
                                        <span x-show="reward.available > 0" x-cloak class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-gradient-to-r from-pink-500 to-gold-500 rounded-full px-3 py-1 animate-pulse">🎁 Gift waiting!</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- animated stat cards --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-5">
                        @php
                            $statCards = [
                                ['icon' => '🧾', 'value' => $ordersCount, 'label' => 'Orders Placed', 'accent' => 'from-gold-400/15 to-gold-400/0 border-gold-300/60', 'countup' => true],
                                ['icon' => '🛵', 'value' => $deliveredCount, 'label' => 'Sweets Delivered', 'accent' => 'from-pista-400/15 to-pista-400/0 border-pista-400/40', 'countup' => true],
                            ];
                        @endphp
                        @foreach ($statCards as $i => $card)
                            <div class="relative overflow-hidden bg-white bg-gradient-to-br {{ $card['accent'] }} rounded-2xl border p-4 sm:p-5 hover:-translate-y-1 hover:shadow-lg transition-all duration-300 animate-rise-in" style="animation-delay: {{ 0.08 + $i * 0.08 }}s">
                                <span class="text-2xl">{{ $card['icon'] }}</span>
                                <p class="font-display font-bold text-2xl sm:text-3xl text-maroon-800 mt-2" data-countup="{{ $card['value'] }}">0</p>
                                <p class="text-maroon-400 text-xs mt-0.5">{{ $card['label'] }}</p>
                            </div>
                        @endforeach
                        <button type="button" @click="tab = 'favorites'"
                                class="relative overflow-hidden bg-white bg-gradient-to-br from-maroon-400/10 to-maroon-400/0 border-maroon-400/30 rounded-2xl border p-4 sm:p-5 text-left hover:-translate-y-1 hover:shadow-lg transition-all duration-300 animate-rise-in" style="animation-delay: 0.24s">
                            <span class="text-2xl">❤️</span>
                            <p class="font-display font-bold text-2xl sm:text-3xl text-maroon-800 mt-2" x-text="favorites.length"></p>
                            <p class="text-maroon-400 text-xs mt-0.5">Favourites <span class="text-gold-600">→</span></p>
                        </button>
                        <button type="button" @click="tab = 'addresses'"
                                class="relative overflow-hidden bg-white bg-gradient-to-br from-gold-400/15 to-gold-400/0 border-gold-300/60 rounded-2xl border p-4 sm:p-5 text-left hover:-translate-y-1 hover:shadow-lg transition-all duration-300 animate-rise-in" style="animation-delay: 0.32s">
                            <span class="text-2xl">📍</span>
                            <p class="font-display font-bold text-2xl sm:text-3xl text-maroon-800 mt-2" x-text="addresses.length"></p>
                            <p class="text-maroon-400 text-xs mt-0.5">Saved Addresses <span class="text-gold-600">→</span></p>
                        </button>
                    </div>

                    {{-- personal information --}}
                    <div class="bg-white rounded-3xl border border-gold-200/60 shadow-sm p-6 sm:p-8 mt-5 animate-rise-in" style="animation-delay: 0.4s">
                        <div class="flex items-center justify-between pb-4 border-b border-gold-100">
                            <p class="font-display font-semibold text-lg text-maroon-800">Personal Information</p>
                            <span class="text-xl">🪪</span>
                        </div>

                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-6 mt-6">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">Full Name</p>
                                <p class="text-maroon-800 font-medium mt-1.5">{{ Auth::user()->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">Mobile Number</p>
                                <p class="text-maroon-800 font-medium mt-1.5">{{ Auth::user()->phone }} <span class="text-pista-600 text-xs font-semibold ml-1">✓ Verified</span></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">Member Since</p>
                                <p class="text-maroon-800 font-medium mt-1.5">{{ Auth::user()->created_at->format('d M Y') }}</p>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-maroon-400">Default Delivery Address</p>
                                <template x-if="addresses.find((a) => a.is_default)">
                                    <p class="text-maroon-800 font-medium mt-1.5" x-text="addresses.find((a) => a.is_default)?.address_line"></p>
                                </template>
                                <template x-if="!addresses.find((a) => a.is_default)">
                                    <p class="text-maroon-400 mt-1.5">No address saved yet</p>
                                </template>
                                <button type="button" @click="tab = 'addresses'" class="text-xs font-semibold text-gold-600 hover:text-gold-700 mt-2 inline-flex items-center gap-1 transition">
                                    Manage addresses <span>→</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- reward teaser strip --}}
                    <template x-if="reward.configured">
                        <button type="button" @click="tab = 'rewards'"
                                class="w-full mt-5 rounded-2xl overflow-hidden text-left relative group animate-rise-in" style="animation-delay: 0.48s"
                                :class="reward.available > 0 ? 'bg-gradient-to-r from-pink-500 via-gold-500 to-pink-500' : 'bg-gradient-to-r from-maroon-800 to-maroon-600'">
                            <div class="absolute inset-0 opacity-15" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>
                            <div class="relative flex items-center gap-4 px-5 sm:px-6 py-4">
                                <span class="text-3xl shrink-0" :class="reward.available > 0 ? 'animate-gift-burst' : 'animate-gift-sway'">🎁</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-display font-bold text-white" x-text="reward.available > 0 ? 'Your free gift is ready to claim!' : 'Sweet Stamps — your loyalty reward'"></p>
                                    <p class="text-white/80 text-sm truncate" x-text="reward.available > 0
                                        ? 'Claim it at checkout on your next order.'
                                        : (reward.progress + ' of ' + reward.required + ' stamps collected — keep going!')"></p>
                                </div>
                                {{-- mini progress ring --}}
                                <div class="hidden sm:flex items-center gap-3 shrink-0">
                                    <div class="w-24 h-2 rounded-full bg-white/25 overflow-hidden" x-show="reward.available === 0">
                                        <div class="h-full rounded-full bg-white transition-all duration-1000" :style="`width: ${(reward.progress / reward.required) * 100}%`"></div>
                                    </div>
                                    <span class="text-white group-hover:translate-x-1 transition-transform duration-200">→</span>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>

                {{-- ═══ orders ═══ --}}
                <div x-show="tab === 'orders'" x-cloak
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">

                    {{-- list --}}
                    <div x-show="!viewingOrderId"
                         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                            <p class="font-display font-semibold text-lg text-maroon-800 mb-4">My Orders</p>

                            @if ($orders->isEmpty())
                                <div class="text-center py-16 bg-white rounded-2xl border border-gold-200/60">
                                    <p class="text-4xl mb-3 inline-block animate-gift-sway">🧾</p>
                                    <p class="text-maroon-700 font-display">No orders yet</p>
                                    <p class="text-maroon-400 text-sm mt-1 max-w-sm mx-auto">When you place an order, it'll show up here with its live status.</p>
                                    <a href="/#bestsellers" class="btn-gold inline-block mt-6 text-sm px-6 py-2.5">Explore Our Sweets</a>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($orders as $order)
                                        @php
                                            $statusStyles = [
                                                'pending' => 'bg-gold-100 text-gold-600 border-gold-300/60',
                                                'confirmed' => 'bg-pista-100 text-pista-600 border-pista-400/40',
                                                'out_for_delivery' => 'bg-sky-50 text-sky-600 border-sky-200',
                                                'delivered' => 'bg-maroon-100 text-maroon-600 border-maroon-400/30',
                                                'cancelled' => 'bg-red-50 text-red-600 border-red-200',
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Order received',
                                                'confirmed' => 'Confirmed — being prepared',
                                                'out_for_delivery' => 'Out for delivery',
                                                'delivered' => 'Delivered',
                                                'cancelled' => 'Cancelled',
                                            ];
                                        @endphp
                                        <div @click="viewOrder({{ $order->id }})" class="block bg-white rounded-2xl border border-gold-200/60 shadow-sm overflow-hidden hover:border-gold-400 hover:shadow-md transition group cursor-pointer">
                                            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gold-100 bg-cream/50">
                                                <div>
                                                    <p class="font-display text-maroon-800 group-hover:text-gold-600 transition">{{ $order->orderNumber() }}</p>
                                                    <p class="text-xs text-maroon-400 mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }} · {{ $order->payment_method === 'razorpay' ? __('Paid Online') : __('Cash on Delivery') }}</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-block text-xs font-semibold px-3 py-1.5 rounded-full border {{ $statusStyles[$order->status] ?? $statusStyles['pending'] }}">
                                                        {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                                                    </span>
                                                    <a href="{{ route('orders.invoice', $order->id) }}" target="_blank" @click.stop title="Download Invoice"
                                                       class="w-8 h-8 rounded-full inline-flex items-center justify-center text-maroon-400 hover:text-maroon-800 hover:bg-gold-100 transition shrink-0">
                                                        📄
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="px-5 py-4 space-y-2">
                                                @foreach ($order->items as $item)
                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="text-maroon-700">
                                                            {{ $item->product_name }} <span class="text-maroon-400">× {{ $item->quantity }}</span>
                                                            @if ($item->is_gift)
                                                                <span class="ml-1 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gradient-to-r from-pink-500 to-gold-500 text-white">🎁 Gift</span>
                                                            @endif
                                                        </span>
                                                        <span class="text-maroon-800 font-medium">{{ $item->is_gift ? 'FREE' : '₹'.number_format($item->line_total) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="px-5 py-3.5 border-t border-gold-100 flex items-center justify-between text-sm">
                                                <span class="text-maroon-500">
                                                    @if ($order->discount_amount > 0)
                                                        Subtotal ₹{{ number_format($order->subtotal) }} − ₹{{ number_format($order->discount_amount) }} coupon
                                                    @else
                                                        {{ $order->items->sum('quantity') }} item(s)
                                                    @endif
                                                </span>
                                                <span class="font-display font-semibold text-lg text-maroon-800">₹{{ number_format($order->total) }}</span>
                                            </div>
                                            <div class="px-5 py-3 border-t border-gold-100 bg-cream/30 text-center text-xs font-semibold text-gold-600 group-hover:text-gold-700 transition">
                                                {{ in_array($order->status, ['delivered', 'cancelled']) ? 'View details' : '⚡ Track live' }} →
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                    </div>

                    {{-- detail — fetched as HTML and mounted inline, no page navigation --}}
                    <div x-show="viewingOrderId" x-cloak
                         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                        <button type="button" @click="backToOrders()"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-maroon-600 hover:text-maroon-800 mb-4 transition">
                            <span>←</span> My Orders
                        </button>

                        <div x-show="loadingOrder" x-cloak class="flex flex-col items-center gap-3 py-20">
                            <span class="w-8 h-8 rounded-full border-3 border-gold-300 border-t-maroon-700 animate-spin"></span>
                            <p class="text-maroon-400 text-sm">Loading order…</p>
                        </div>

                        <p x-show="orderLoadError" x-cloak class="text-center text-red-600 text-sm py-10" x-text="orderLoadError"></p>

                        <div x-show="!loadingOrder" x-ref="orderDetailPanel"></div>
                    </div>
                </div>

                {{-- ═══ favorites ═══ --}}
                <div x-show="tab === 'favorites'" x-cloak
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                    <p class="font-display font-semibold text-lg text-maroon-800 mb-4">My Favorites</p>

                    @if ($favoriteProducts->isEmpty())
                        <div class="text-center py-16 bg-white rounded-2xl border border-gold-200/60">
                            <p class="text-4xl mb-3 inline-block animate-gift-sway">🤍</p>
                            <p class="text-maroon-700 font-display">No favorites yet</p>
                            <p class="text-maroon-400 text-sm mt-1 max-w-sm mx-auto">Tap the heart icon on any sweet to save it here.</p>
                            <a href="/#bestsellers" class="btn-gold inline-block mt-6 text-sm px-6 py-2.5">Explore Our Sweets</a>
                        </div>
                    @else
                        {{-- driven by the live `favorites` array, so un-hearting the last item swaps this in immediately --}}
                        <div x-show="favorites.length === 0" x-cloak class="text-center py-16 bg-white rounded-2xl border border-gold-200/60">
                            <p class="text-4xl mb-3 inline-block animate-gift-sway">🤍</p>
                            <p class="text-maroon-700 font-display">No favorites yet</p>
                            <p class="text-maroon-400 text-sm mt-1 max-w-sm mx-auto">Tap the heart icon on any sweet to save it here.</p>
                            <a href="/#bestsellers" class="btn-gold inline-block mt-6 text-sm px-6 py-2.5">Explore Our Sweets</a>
                        </div>

                        <div x-show="favorites.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach ($favoriteProducts as $product)
                                <div x-show="isFavorited({{ $product->id }})" x-transition.opacity.duration.300ms>
                                    @include('partials.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- ═══ addresses ═══ --}}
                <div x-show="tab === 'addresses'" x-cloak
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="font-display font-semibold text-lg text-maroon-800">Saved Addresses</p>
                            <p class="text-xs text-maroon-400 mt-0.5">Pick from these at checkout instead of retyping.</p>
                        </div>
                        <button type="button" @click="showAddForm = !showAddForm; resetForm()" class="btn-gold text-sm px-4 py-2">
                            <span x-text="showAddForm ? 'Cancel' : '+ Add Address'"></span>
                        </button>
                    </div>

                    <div x-show="showAddForm" x-cloak x-transition class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 mb-4">
                        <label class="block text-sm font-medium text-maroon-700 mb-1.5">Label <span class="text-maroon-400 font-normal">(optional)</span></label>
                        <input type="text" x-model="form.label" placeholder="e.g. Home, Work" maxlength="40"
                               class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-400/50 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">

                        <label class="block text-sm font-medium text-maroon-700 mb-1.5 mt-3">Address</label>
                        <textarea x-model="form.address_line" rows="3" maxlength="500"
                                  placeholder="House / shop, street, landmark, village or town"
                                  class="w-full rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 placeholder-maroon-400/50 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition"></textarea>

                        <p x-show="formError" x-cloak class="text-red-600 text-xs mt-2" x-text="formError"></p>

                        <div class="flex items-center gap-3 mt-3">
                            <button type="button" @click="editingId ? updateAddress() : addAddress()" :disabled="saving"
                                    class="btn-gold text-sm px-5 py-2 disabled:opacity-60">
                                <span x-text="saving ? 'Saving…' : (editingId ? 'Save Changes' : 'Add Address')"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="addresses.length === 0 && !showAddForm" x-cloak class="text-center py-16 bg-white rounded-2xl border border-gold-200/60">
                        <p class="text-4xl mb-3 inline-block animate-gift-sway">📍</p>
                        <p class="text-maroon-700 font-display">No saved addresses yet</p>
                        <p class="text-maroon-400 text-sm mt-1">Add one so checkout is even faster next time.</p>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(addr, idx) in addresses" :key="addr.id">
                            <div class="bg-white rounded-2xl border shadow-sm p-4 sm:p-5 flex items-start gap-4 hover:shadow-md hover:border-gold-400/70 transition-all duration-200 animate-rise-in"
                                 :style="`animation-delay: ${idx * 0.07}s`"
                                 :class="addr.is_default ? 'border-gold-400/70' : 'border-gold-200/60'">
                                <div class="w-10 h-10 rounded-full grid place-items-center text-lg shrink-0 mt-0.5"
                                     :class="addr.is_default ? 'bg-gold-100 border border-gold-300/70' : 'bg-cream border border-gold-200/60'"
                                     x-text="(addr.label || '').toLowerCase().includes('work') ? '🏢' : '🏠'"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-semibold text-maroon-800" x-text="addr.label || 'Address'"></p>
                                        <span x-show="addr.is_default" x-cloak class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gold-400 text-maroon-900">Default</span>
                                    </div>
                                    <p class="text-sm text-maroon-600 mt-1 whitespace-pre-line" x-text="addr.address_line"></p>
                                    <div class="flex items-center gap-2 mt-3">
                                        <button type="button" @click="startEdit(addr)"
                                                class="text-xs font-semibold text-gold-700 bg-gold-100/70 hover:bg-gold-100 border border-gold-300/60 rounded-full px-3 py-1 transition">✏️ Edit</button>
                                        <button type="button" x-show="!addr.is_default" @click="makeDefault(addr)"
                                                class="text-xs font-semibold text-maroon-600 bg-cream hover:bg-gold-50 border border-gold-200/70 rounded-full px-3 py-1 transition">⭐ Make Default</button>
                                        <button type="button" @click="deleteAddress(addr)"
                                                class="text-xs font-semibold text-red-500 bg-red-50/70 hover:bg-red-50 border border-red-200 rounded-full px-3 py-1 transition">🗑️ Delete</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ═══ rewards ═══ --}}
                <div x-show="tab === 'rewards'" x-cloak
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                    <template x-if="!reward.configured">
                        <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-10 text-center">
                            <p class="text-4xl mb-3 inline-block animate-gift-sway">🎁</p>
                            <p class="text-maroon-700 font-display text-lg">Rewards are coming soon</p>
                            <p class="text-maroon-400 text-sm mt-1">Check back soon for a loyalty gift program!</p>
                        </div>
                    </template>

                    <template x-if="reward.configured">
                        <div class="relative rounded-3xl overflow-hidden shadow-lg"
                             :class="reward.available > 0 ? 'bg-gradient-to-br from-pink-500 via-gold-500 to-pink-500' : 'bg-gradient-to-br from-maroon-700 to-maroon-900'">
                            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 20px 20px;"></div>

                            <div class="relative px-6 sm:px-10 py-10 text-center">
                                {{-- unlocked state --}}
                                <template x-if="reward.available > 0">
                                    <div>
                                        <div class="relative inline-block">
                                            <span class="text-7xl inline-block animate-gift-burst">🎁</span>
                                            <template x-for="i in 8" :key="i">
                                                <span class="absolute top-1/2 left-1/2 w-1.5 h-1.5 rounded-full bg-white animate-gift-sparkle"
                                                      :style="`--tx: ${Math.cos((i / 8) * 2 * Math.PI) * 70}px; --ty: ${Math.sin((i / 8) * 2 * Math.PI) * 70}px; animation-delay: ${i * 0.15}s`"></span>
                                            </template>
                                        </div>
                                        <p class="font-display font-bold text-2xl sm:text-3xl text-white mt-3">Free Gift Unlocked!</p>
                                        <p class="text-white/90 mt-2 max-w-sm mx-auto">
                                            You've earned <span class="font-semibold" x-text="reward.gift_label"></span>
                                            <span x-show="reward.available > 1" x-cloak> ×<span x-text="reward.available"></span></span>
                                            — it'll be waiting for you at checkout on your next order.
                                        </p>
                                        <a href="/#bestsellers" class="btn-gold inline-block mt-6 text-sm px-6 py-2.5">Order Now →</a>
                                    </div>
                                </template>

                                {{-- in-progress state --}}
                                <template x-if="reward.available === 0">
                                    <div>
                                        <span class="text-6xl inline-block animate-gift-sway">🎀</span>
                                        <p class="font-display font-bold text-2xl text-cream mt-3">Collect Sweet Stamps</p>
                                        <p class="text-cream/70 mt-1.5">
                                            <span x-text="reward.progress"></span> of <span x-text="reward.required"></span> orders delivered —
                                            <span x-text="reward.required - reward.progress"></span> more for a free <span x-text="reward.gift_label" class="font-semibold"></span>!
                                        </p>

                                        <div class="flex flex-wrap items-center justify-center gap-2.5 sm:gap-3 mt-6 max-w-lg mx-auto">
                                            <template x-for="i in reward.required" :key="i">
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full grid place-items-center text-lg sm:text-xl border-2 transition"
                                                     :class="i <= reward.progress ? 'bg-gold-400 border-gold-300 animate-stamp-fill' : 'bg-white/10 border-cream/30'"
                                                     :style="i <= reward.progress ? `animation-delay: ${(i - 1) * 0.08}s` : ''">
                                                    <span x-show="i <= reward.progress">🍬</span>
                                                    <span x-show="i > reward.progress" class="opacity-40">🕳️</span>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="w-full max-w-sm mx-auto h-2.5 rounded-full bg-white/15 overflow-hidden mt-6">
                                            <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-300 transition-all duration-1000 ease-out"
                                                 :style="`width: ${(reward.progress / reward.required) * 100}%`"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <p class="text-xs text-maroon-400 text-center mt-4">Stamps are earned once an order is delivered — placing an order isn't enough on its own.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
