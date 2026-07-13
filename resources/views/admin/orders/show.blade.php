@extends('admin.layout')

@section('title', 'Order #' . $order->id)
@section('page-title', __('Order') . ' #' . $order->id)

@section('content')
    @php
        $steps = [
            1 => ['icon' => '🧾', 'label' => __('Placed')],
            2 => ['icon' => '👨‍🍳', 'label' => __('Confirmed')],
            3 => ['icon' => '🛵', 'label' => __('Out for Delivery')],
            4 => ['icon' => '🎁', 'label' => __('Delivered')],
        ];
        $cancelledReasons = [
            'customer' => __('— by the customer (no COD restriction applied)'),
            'admin' => __('— by the shop'),
            'system' => __('— auto-cancelled after 90 min (no COD restriction applied)'),
        ];
    @endphp

    <a href="{{ route('admin.orders.index') }}" class="text-sm text-maroon-500 hover:text-maroon-700 transition">← {{ __('Back to Orders') }}</a>

    <div class="max-w-6xl mx-auto space-y-5 mt-4" x-data='adminOrderShowPage(@json($orderForJs), {{ $order->id }}, @json($cancelledReasons))'>
        @if ($order->is_gift_order)
            <div class="rounded-xl bg-gradient-to-r from-pink-500 via-gold-500 to-pink-500 text-white px-5 py-3.5 flex items-center gap-2.5 shadow-md">
                <span class="text-xl">🎁</span>
                <p class="font-semibold text-sm">{{ __("This order claimed a loyalty free gift — it's included in the items below at ₹0.") }}</p>
            </div>
        @endif

        <div x-show="order.status === 'cancelled' && order.cancelled_by === 'system'" x-cloak class="rounded-xl bg-red-50 border-2 border-red-300 text-red-700 px-5 py-3.5 flex items-center gap-2.5 shadow-sm">
            <span class="text-xl">⏰</span>
            <p class="font-semibold text-sm">{{ __("This order was auto-cancelled — it wasn't delivered within 90 minutes of being placed. No COD restriction was applied to the customer.") }}</p>
        </div>

        {{-- header --}}
        <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-6 animate-fade-up">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="font-display text-2xl text-maroon-800">{{ __('Order') }} #{{ $order->id }}</p>
                    <p class="text-sm text-maroon-500 mt-1">
                        {{ __('Placed') }} {{ $order->created_at->format('d M Y, h:i A') }} · {{ strtoupper($order->payment_method ?? 'COD') }}
                        @if ($order->payment_method === 'razorpay')
                            <span class="inline-block text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full border {{ ['paid' => 'bg-pista-100 text-pista-600 border-pista-400/40', 'failed' => 'bg-red-50 text-red-600 border-red-200'][$order->payment_status] ?? 'bg-gold-100 text-gold-600 border-gold-300/60' }}">
                                {{ $order->payment_status }}
                            </span>
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border" :class="statusBadgeClasses()" x-text="statusLabel()"></span>
                    <p class="font-display font-bold text-3xl text-maroon-800 mt-2">₹{{ number_format($order->total) }}</p>
                </div>
            </div>
        </div>

        {{-- progress stepper --}}
        <div x-show="order.status !== 'cancelled'" x-cloak class="bg-white rounded-2xl border border-gold-200/60 shadow-sm px-4 sm:px-6 py-6 animate-fade-up" style="animation-delay: 60ms">
            <div class="flex items-start">
                @foreach ($steps as $i => $step)
                    @if ($i > 1)
                        <div class="flex-1 h-1.5 rounded-full mt-[1.4rem] mx-1 sm:mx-2 bg-gold-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-600 transition-all duration-1000"
                                 :style="'width: ' + (rank() >= {{ $i }} ? 100 : 0) + '%'"></div>
                        </div>
                    @endif
                    <div class="flex flex-col items-center w-16 sm:w-24 shrink-0">
                        <div class="w-11 h-11 rounded-full grid place-items-center text-lg border-2"
                             :class="{
                                 'bg-pista-500 border-pista-500': stepState({{ $i }}) === 'done',
                                 'bg-gold-100 border-gold-500 animate-track-breathe': stepState({{ $i }}) === 'active',
                                 'bg-white border-gold-200': stepState({{ $i }}) === 'todo',
                             }">
                            <template x-if="stepState({{ $i }}) === 'done'">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </template>
                            <template x-if="stepState({{ $i }}) !== 'done'">
                                <span :class="stepState({{ $i }}) === 'todo' ? 'grayscale opacity-40' : ''">{{ $step['icon'] }}</span>
                            </template>
                        </div>
                        <p class="text-[11px] sm:text-xs font-semibold mt-2 text-center leading-tight" :class="stepState({{ $i }}) === 'todo' ? 'text-maroon-400/60' : 'text-maroon-700'">{{ $step['label'] }}</p>
                        <p class="text-[10px] sm:text-[11px] text-maroon-400 mt-0.5 h-3.5" x-text="stepTime({{ $i }})"></p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid lg:grid-cols-[1fr_360px] gap-5 items-start">
            <div class="space-y-5">
                {{-- items --}}
                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm overflow-hidden animate-fade-up" style="animation-delay: 100ms">
                    <div class="px-5 py-4 border-b border-gold-100">
                        <p class="font-display text-maroon-800">{{ __('Items') }}</p>
                    </div>
                    <div class="divide-y divide-gold-50">
                        @foreach ($order->items as $item)
                            <div class="flex items-center gap-3.5 px-5 py-3.5 {{ $item->is_gift ? 'bg-gradient-to-r from-pink-50 to-gold-50' : '' }}">
                                <div class="w-12 h-12 shrink-0 rounded-lg overflow-hidden bg-cream border border-gold-100">
                                    @if ($item->product)
                                        <img src="{{ asset($item->product->image) }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full grid place-items-center text-lg">🍬</div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-maroon-800 truncate">
                                        {{ $item->product_name }}
                                        @if ($item->is_gift)
                                            <span class="ml-1 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gradient-to-r from-pink-500 to-gold-500 text-white">🎁 {{ __('Free Gift') }}</span>
                                        @endif
                                        @unless ($item->product)
                                            <span class="text-maroon-300 text-xs">({{ __('product removed') }})</span>
                                        @endunless
                                    </p>
                                    <p class="text-xs text-maroon-500 mt-0.5">
                                        ₹{{ $item->product_price }}
                                        @if ($item->portionLabel())
                                            ({{ $item->portionLabel() }})
                                        @endif
                                        × {{ $item->quantity }}
                                    </p>
                                </div>
                                <p class="text-sm font-semibold text-maroon-800 shrink-0">{{ $item->is_gift ? __('FREE') : '₹'.number_format($item->line_total) }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-5 py-4 border-t border-gold-100 space-y-1.5 text-sm ml-auto max-w-xs">
                        <div class="flex justify-between"><span class="text-maroon-500">{{ __('Subtotal') }}</span><span class="text-maroon-800">₹{{ number_format($order->subtotal) }}</span></div>
                        @if ($order->discount_amount > 0)
                            <div class="flex justify-between"><span class="text-pista-600">{{ __('Coupon discount') }} ({{ $order->coupon->code ?? __('removed') }})</span><span class="text-pista-600">−₹{{ number_format($order->discount_amount) }}</span></div>
                        @endif
                        <div class="flex justify-between font-semibold text-base pt-1.5 border-t border-gold-100"><span class="text-maroon-800">{{ __('Total') }}</span><span class="text-maroon-800">₹{{ number_format($order->total) }}</span></div>
                    </div>
                </div>

                @if ($order->customer_note)
                    <div class="bg-gold-50 rounded-2xl border border-gold-300/60 shadow-sm p-5 animate-fade-up" style="animation-delay: 140ms">
                        <p class="font-display text-maroon-800 mb-2">📝 {{ __('Customer Note') }}</p>
                        <p class="text-sm text-maroon-700 whitespace-pre-line">{{ $order->customer_note }}</p>
                    </div>
                @endif

                @if ($order->status !== 'cancelled' || $order->rider || $order->delivery_photo_path)
                    <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 animate-fade-up" style="animation-delay: 180ms">
                        <p class="font-display text-maroon-800 mb-3">🛵 {{ __('Delivery Rider') }}</p>
                        @if ($order->status === 'cancelled')
                            @if ($order->rider)
                                <p class="text-sm text-maroon-700">{{ __('Rider') }}: <span class="font-medium">{{ $order->rider->name }}</span></p>
                            @endif
                        @else
                            <form method="POST" action="{{ route('admin.orders.rider', $order) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="rider_id" class="flex-1 rounded-lg border border-gold-300/70 px-3 py-2 text-sm text-maroon-800 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                                    <option value="">{{ __('Unassigned') }}</option>
                                    @foreach ($riders as $rider)
                                        <option value="{{ $rider->id }}" {{ $order->rider_id === $rider->id ? 'selected' : '' }}>{{ $rider->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="shrink-0 text-sm font-semibold px-3.5 py-2 rounded-lg bg-maroon-700 hover:bg-maroon-800 text-cream transition">{{ __('Assign') }}</button>
                            </form>
                        @endif
                        <template x-if="order.delivery_photo_url">
                            <div>
                                <a :href="order.delivery_photo_url" target="_blank" rel="noopener" class="block mt-3 group">
                                    <img :src="order.delivery_photo_url" alt="Delivery proof for order #{{ $order->id }}"
                                         class="w-full max-h-80 object-cover rounded-xl border border-gold-200/60 group-hover:opacity-90 transition">
                                </a>
                                <p class="text-xs text-maroon-400 mt-2">{{ __('Uploaded') }} <span x-text="order.delivery_photo_uploaded_at"></span> — {{ __('auto-removed after 3 days.') }} <span class="text-maroon-300">({{ __('tap to view full size') }})</span></p>
                            </div>
                        </template>
                    </div>
                @endif
            </div>

            <div class="space-y-5">
                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 animate-fade-up" style="animation-delay: 100ms">
                    <p class="font-display text-maroon-800 mb-3">{{ __('Customer') }}</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 shrink-0 rounded-full bg-maroon-700 text-cream flex items-center justify-center font-display font-bold">
                            {{ strtoupper(substr($order->customer_name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-maroon-800 truncate">{{ $order->customer_name }}</p>
                            <a href="tel:+91{{ preg_replace('/\D/', '', $order->customer_phone) }}" class="text-sm text-maroon-500 hover:text-gold-600 transition">{{ $order->customer_phone }}</a>
                        </div>
                    </div>
                    @if ($order->user && $order->user->cod_blocked_orders > 0)
                        <p class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-200 mt-3">
                            ⚠️ {{ __('COD blocked for next :count orders', ['count' => $order->user->cod_blocked_orders]) }}
                        </p>
                    @endif
                    @if ($order->delivery_address)
                        <p class="text-xs text-maroon-400 mt-4 uppercase tracking-wide font-semibold">{{ __('Delivery Address') }}</p>
                        <p class="text-sm text-maroon-600 mt-1 whitespace-pre-line">{{ $order->delivery_address }}</p>
                    @endif
                    @if ($order->latitude !== null && $order->longitude !== null)
                        <p class="text-sm text-maroon-500 mt-2">
                            📍 @if ($order->distance_km !== null){{ number_format($order->distance_km, 1) }} {{ __('km from shop') }} · @endif<a href="https://www.google.com/maps?q={{ $order->latitude }},{{ $order->longitude }}" target="_blank" rel="noopener" class="text-gold-600 hover:text-gold-700 underline underline-offset-2">{{ __('View on map') }}</a>
                        </p>
                    @endif
                </div>

                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 animate-fade-up" style="animation-delay: 140ms">
                    <div class="flex items-center justify-between mb-4">
                        <p class="font-display text-maroon-800">{{ __('Status') }}</p>
                        <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border" :class="statusBadgeClasses()" x-text="statusLabel()"></span>
                    </div>

                    @if ($order->eta_minutes && $etaEndsAt)
                        {{-- live digital-clock countdown, ticking every second — the same clock the customer sees on their tracking page --}}
                        <div x-show="['confirmed', 'out_for_delivery'].includes(order.status)" x-cloak x-data="etaCountdown({{ $etaEndsAt }})" class="mb-4 -mt-2">
                            <div class="flex items-center gap-3 rounded-xl px-4 py-3 transition-colors duration-300"
                                 :class="overdue ? 'bg-red-50 border border-red-200' : 'bg-maroon-900 border border-maroon-800'">
                                <span class="text-xl" x-text="overdue ? '⚠️' : '⏱️'"></span>
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-wide" :class="overdue ? 'text-red-500' : 'text-gold-300/70'" x-text='overdue ? @json(__('Running late by')) : @json(__('Arriving in'))'></p>
                                    <p class="font-mono text-2xl font-bold tabular-nums tracking-wider leading-tight" :class="overdue ? 'text-red-600' : 'text-gold-300'" x-text="clock"></p>
                                </div>
                            </div>
                            <p class="text-xs text-maroon-400 mt-2">{{ __('Promised') }} {{ $order->eta_minutes }} {{ __('min') }}{{ $order->confirmed_at ? ' ('.__('confirmed').' '.$order->confirmed_at->format('h:i A').')' : '' }}</p>
                        </div>
                        <p x-show="order.status === 'delivered'" x-cloak class="text-xs text-maroon-500 mb-4 -mt-2">⏱️ {{ __('Promised in') }} {{ $order->eta_minutes }} {{ __('min') }}{{ $order->confirmed_at ? ' ('.__('confirmed').' '.$order->confirmed_at->format('h:i A').')' : '' }}</p>
                    @endif

                    <div x-show="order.status === 'pending'" x-cloak>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" x-data="{ eta: 30 }">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="confirmed">
                            <input type="hidden" name="eta_minutes" :value="eta">
                            <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold mb-2">{{ __('Delivery time') }}</p>
                            <div class="grid grid-cols-4 gap-2 mb-3">
                                @foreach ([20, 30, 45, 60] as $mins)
                                    <button type="button" @click="eta = {{ $mins }}"
                                            class="text-sm font-semibold rounded-lg py-2 border transition"
                                            :class="eta === {{ $mins }} ? 'bg-maroon-700 text-cream border-maroon-700' : 'bg-white text-maroon-600 border-gold-200/80 hover:border-gold-400'">
                                        {{ $mins }}{{ __('m') }}
                                    </button>
                                @endforeach
                            </div>
                            <button type="submit" class="w-full bg-pista-500 hover:bg-pista-600 text-white font-semibold rounded-xl py-2.5 transition text-sm">✓ {{ __('Confirm Order') }}</button>
                        </form>
                    </div>
                    <div x-show="order.status === 'confirmed'" x-cloak>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="out_for_delivery">
                            <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold rounded-xl py-2.5 transition text-sm">🛵 {{ __('Out for Delivery') }}</button>
                        </form>
                    </div>
                    <div x-show="order.status === 'out_for_delivery'" x-cloak>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="delivered">
                            <button type="submit" class="w-full bg-maroon-700 hover:bg-maroon-800 text-cream font-semibold rounded-xl py-2.5 transition text-sm">✓ {{ __('Mark Delivered') }}</button>
                        </form>
                    </div>
                    <p x-show="order.status === 'delivered'" x-cloak class="text-sm text-maroon-500">{{ __('Delivered') }} <span x-text="order.delivered_at_full"></span> 🎉</p>
                    <p x-show="order.status === 'cancelled'" x-cloak class="text-sm text-red-600">
                        {{ __('Cancelled') }} <span x-text="order.cancelled_at_full"></span>
                        <span x-text="cancelledReasonText()"></span>
                    </p>

                    <div x-show="['pending', 'confirmed', 'out_for_delivery'].includes(order.status)" x-cloak>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-3"
                              onsubmit="return confirm('{{ __('Cancel order') }} #{{ $order->id }}? {{ $order->payment_method === 'cod' ? __('The customer will see it as cancelled by the shop, and their COD will be restricted for the next 2 orders.') : __('The customer will see it as cancelled by the shop.') }}');">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" class="w-full bg-white border border-red-300 hover:bg-red-50 text-red-600 font-semibold rounded-xl py-2.5 transition text-sm">✕ {{ __('Cancel Order') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
