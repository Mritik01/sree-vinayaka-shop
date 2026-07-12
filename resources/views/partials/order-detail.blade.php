{{-- order detail — shared by the standalone /orders/{id} page and the inline "My Orders" tab on
     the account page (fetched via GET /orders/{id}/partial and injected + Alpine.initTree()'d) --}}
<div class="relative max-w-lg lg:max-w-5xl mx-auto"
     x-data='orderTrackingPage(@json($orderForJs), {{ $justPlaced ? 'true' : 'false' }})'>

    <div class="grid lg:grid-cols-[1fr_380px] gap-6 items-start">
        <div class="space-y-5 min-w-0">

            {{-- ── live status hero ─────────────────────────────── --}}
            <div class="relative rounded-3xl shadow-lg overflow-hidden">

                {{-- pending: waiting for the shop --}}
                <div x-show="order.status === 'pending'" x-cloak
                     x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-gradient-to-br from-gold-400 to-gold-600 px-6 py-10 text-center overflow-hidden">
                    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>
                    <div class="relative inline-grid place-items-center w-24 h-24">
                        <span class="absolute inset-0 rounded-full bg-white/40 animate-track-ring"></span>
                        <span class="absolute inset-0 rounded-full bg-white/40 animate-track-ring" style="animation-delay: 0.65s"></span>
                        <span class="relative text-6xl drop-shadow">🧾</span>
                    </div>
                    <p class="relative font-display font-bold text-2xl text-maroon-900 mt-4">{{ __('Order Received!') }}</p>
                    <p class="relative text-maroon-800/90 mt-1.5 font-medium">
                        {{ __('Waiting for the shop to confirm') }}
                        <span class="inline-flex ml-0.5">
                            <span class="animate-track-dot">.</span>
                            <span class="animate-track-dot" style="animation-delay: 0.2s">.</span>
                            <span class="animate-track-dot" style="animation-delay: 0.4s">.</span>
                        </span>
                    </p>
                    <p class="relative inline-block bg-white/30 backdrop-blur-sm text-maroon-900 text-sm font-semibold px-4 py-1.5 rounded-full mt-4">💵 {{ __('Pay') }} ₹{{ number_format($order->total) }} {{ __('on delivery') }}</p>
                </div>

                {{-- confirmed: being prepared --}}
                <div x-show="order.status === 'confirmed'" x-cloak
                     x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-gradient-to-br from-pista-400 to-pista-600 px-6 py-10 text-center overflow-hidden">
                    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>
                    <div class="relative inline-grid place-items-center w-24 h-24">
                        <span class="absolute -top-1 left-4 w-2.5 h-2.5 rounded-full bg-white/80 animate-track-steam"></span>
                        <span class="absolute -top-2 left-1/2 w-3 h-3 rounded-full bg-white/80 animate-track-steam" style="animation-delay: 0.6s"></span>
                        <span class="absolute -top-1 right-4 w-2 h-2 rounded-full bg-white/80 animate-track-steam" style="animation-delay: 1.1s"></span>
                        <span class="relative text-6xl drop-shadow">👨‍🍳</span>
                    </div>
                    <p class="relative font-display font-bold text-2xl text-white mt-4">{{ __('Preparing Your Sweets!') }}</p>
                    <p class="relative text-white/90 mt-1.5 font-medium">{{ __("The shop confirmed your order — it's being made fresh.") }}</p>
                    <p x-show="etaText()" class="relative inline-block bg-white/25 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full mt-4">
                        🕐 {{ __('Arriving') }} <span x-text="etaText()"></span>
                    </p>
                </div>

                {{-- out for delivery: scooter on the road --}}
                <div x-show="order.status === 'out_for_delivery'" x-cloak
                     x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-gradient-to-br from-sky-400 to-sky-600 px-6 py-10 text-center overflow-hidden">
                    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, white 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>
                    <div class="relative inline-block">
                        <span class="relative block text-6xl drop-shadow animate-track-scooter" style="transform: scaleX(-1);">🛵</span>
                        <span class="block h-1 w-36 mx-auto mt-2 rounded-full text-white/70 animate-track-road"></span>
                    </div>
                    <p class="relative font-display font-bold text-2xl text-white mt-4">{{ __('Out for Delivery!') }}</p>
                    <p class="relative text-white/90 mt-1.5 font-medium">{{ __('Your mithai is on its way to you.') }} 💵 {{ __('Keep') }} ₹{{ number_format($order->total) }} {{ __('ready.') }}</p>
                    <p x-show="etaText()" class="relative inline-block bg-white/25 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full mt-4">
                        🕐 {{ __('Arriving') }} <span x-text="etaText()"></span>
                    </p>
                </div>

                {{-- delivered --}}
                <div x-show="order.status === 'delivered'" x-cloak
                     x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-gradient-to-br from-maroon-600 to-maroon-800 px-6 py-10 text-center overflow-hidden">
                    <div class="absolute inset-0 opacity-15" style="background-image: radial-gradient(circle, #e9c873 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>
                    <span class="relative inline-block text-6xl drop-shadow animate-track-pop">🎉</span>
                    <p class="relative font-display font-bold text-2xl text-gold-300 mt-4">{{ __('Delivered — Enjoy!') }}</p>
                    <p class="relative text-cream/90 mt-1.5 font-medium">{{ __('Hope every bite tastes like home. Thank you for ordering from Makhanbhog!') }}</p>
                    <a href="/#bestsellers" class="relative btn-gold inline-block mt-5 text-sm px-6 py-2.5">{{ __('Order Again') }}</a>
                </div>

                {{-- cancelled --}}
                <div x-show="order.status === 'cancelled'" x-cloak
                     x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-gradient-to-br from-maroon-900 to-maroon-700 px-6 py-10 text-center overflow-hidden">
                    <span class="relative inline-block text-6xl drop-shadow">🥀</span>
                    <p class="relative font-display font-bold text-2xl text-cream mt-4">{{ __('Order Cancelled') }}</p>
                    <p class="relative text-cream/80 mt-1.5 font-medium" x-text='order.cancelled_by === "customer"
                        ? @json(__('You cancelled this order. We hope to serve you again soon!'))
                        : (order.cancelled_by === "system"
                            ? {{ json_encode(__('We\'re sorry — this order took too long to deliver, so it was automatically cancelled. Please call us at 8920937331, or feel free to place a fresh order.'), 15, 512) }}
                            : @json(__('The shop couldn\'t take this order. Please call us at 8920937331 if you need help.')))'></p>
                    <a href="/#bestsellers" class="relative btn-gold inline-block mt-5 text-sm px-6 py-2.5">{{ __('Browse Sweets') }}</a>
                </div>
            </div>

            {{-- ── progress timeline ─────────────────────────────── --}}
            <div x-show="order.status !== 'cancelled'" class="bg-white rounded-2xl border border-gold-200/60 shadow-sm px-4 sm:px-6 py-6">
                <div class="flex items-start">
                    @php
                        $steps = [
                            1 => ['icon' => '🧾', 'label' => __('Placed')],
                            2 => ['icon' => '👨‍🍳', 'label' => __('Preparing')],
                            3 => ['icon' => '🛵', 'label' => __('On the way')],
                            4 => ['icon' => '🎁', 'label' => __('Delivered')],
                        ];
                    @endphp
                    @foreach ($steps as $i => $step)
                        @if ($i > 1)
                            {{-- connector bar; fills gold once the step it leads to is done --}}
                            <div class="flex-1 h-1.5 rounded-full mt-[1.4rem] mx-1 sm:mx-2 bg-gold-100 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-600 transition-all duration-1000 ease-out"
                                     :style="stepState({{ $i }}) === 'done' ? 'width: 100%' : (stepState({{ $i }}) === 'active' ? 'width: 45%' : 'width: 0%')"></div>
                            </div>
                        @endif
                        <div class="flex flex-col items-center w-14 sm:w-20 shrink-0">
                            <div class="w-11 h-11 rounded-full grid place-items-center text-lg border-2 transition-colors duration-500"
                                 :class="{
                                     'bg-pista-500 border-pista-500': stepState({{ $i }}) === 'done',
                                     'bg-gold-100 border-gold-500 animate-track-breathe': stepState({{ $i }}) === 'active',
                                     'bg-white border-gold-200': stepState({{ $i }}) === 'todo',
                                 }">
                                <template x-if="stepState({{ $i }}) === 'done'">
                                    <svg class="w-5 h-5 text-white animate-track-pop" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </template>
                                <template x-if="stepState({{ $i }}) !== 'done'">
                                    <span :class="stepState({{ $i }}) === 'todo' && 'grayscale opacity-40'">{{ $step['icon'] }}</span>
                                </template>
                            </div>
                            <p class="text-[11px] sm:text-xs font-semibold mt-2 text-center leading-tight"
                               :class="stepState({{ $i }}) === 'todo' ? 'text-maroon-400/60' : 'text-maroon-700'">{{ $step['label'] }}</p>
                            <p class="text-[10px] sm:text-[11px] text-maroon-400 mt-0.5 h-3.5" x-text="stepTime({{ $i }}) || ''"></p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ── note for the shop ─────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 sm:p-6">
                <p class="font-display text-lg text-maroon-800">📝 {{ __('Note for the Shop') }}</p>
                <template x-if="!['delivered', 'cancelled'].includes(order.status)">
                    <div>
                        <textarea x-model="noteDraft" rows="2" maxlength="500"
                                  placeholder="{{ __('e.g. Call when you arrive, less sugar, leave with the neighbour…') }}"
                                  class="mt-3 w-full rounded-xl border border-gold-300/70 px-3.5 py-2.5 text-sm text-maroon-800 placeholder-maroon-400/50 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition resize-none"></textarea>
                        <p x-show="noteError" x-cloak class="text-red-600 text-xs mt-1.5" x-text="noteError"></p>
                        <div class="flex items-center gap-3 mt-2">
                            <button @click="saveNote()" :disabled="savingNote || noteDraft === (order.customer_note || '')" type="button"
                                    class="btn-gold text-sm px-5 py-2 disabled:opacity-50 disabled:hover:scale-100">
                                <span x-show="!savingNote">{{ __('Save Note') }}</span>
                                <span x-show="savingNote" x-cloak>{{ __('Saving…') }}</span>
                            </button>
                            <span x-show="noteSaved" x-cloak x-transition class="text-pista-600 text-sm font-semibold">✓ {{ __('Saved — the shop can see it') }}</span>
                        </div>
                    </div>
                </template>
                <template x-if="['delivered', 'cancelled'].includes(order.status)">
                    <p class="text-sm text-maroon-500 mt-2" x-text='order.customer_note || @json(__('No note was added to this order.'))'></p>
                </template>
            </div>

            {{-- ── cancel order ──────────────────────────────────── --}}
            <div x-show="order.can_cancel" x-cloak class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 sm:p-6">
                <div x-show="!confirmingCancel">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-display text-maroon-800">{{ __('Changed your mind?') }}</p>
                            <p class="text-xs text-maroon-400 mt-0.5">{{ __('You can cancel free of charge until the order leaves the shop.') }}</p>
                        </div>
                        <button @click="confirmingCancel = true; cancelError = ''" type="button"
                                class="text-sm font-semibold text-red-500 hover:text-red-600 border border-red-200 hover:bg-red-50 rounded-xl px-4 py-2 transition">
                            {{ __('Cancel Order') }}
                        </button>
                    </div>
                </div>
                <div x-show="confirmingCancel" x-cloak x-transition>
                    <p class="font-display text-maroon-800">{{ __('Cancel order') }} #<span x-text="order.id"></span>?</p>
                    <p class="text-sm text-maroon-500 mt-1">{{ __("The sweets won't be prepared and nothing is charged.") }}</p>
                    <p x-show="cancelError" x-cloak class="text-red-600 text-sm mt-2" x-text="cancelError"></p>
                    <div class="flex items-center gap-3 mt-4">
                        <button @click="cancelOrder()" :disabled="cancelling" type="button"
                                class="bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl px-5 py-2.5 transition disabled:opacity-60">
                            <span x-show="!cancelling">{{ __('Yes, cancel it') }}</span>
                            <span x-show="cancelling" x-cloak>{{ __('Cancelling…') }}</span>
                        </button>
                        <button @click="confirmingCancel = false" type="button"
                                class="text-sm font-semibold text-maroon-600 hover:text-maroon-800 border border-gold-300/70 hover:bg-cream rounded-xl px-5 py-2.5 transition">
                            {{ __('Keep my order') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── right column: order summary ───────────────────────── --}}
        <div class="space-y-5 min-w-0">
            <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gold-100 bg-cream/50">
                    <p class="font-display text-maroon-800">{{ __('Order') }} #{{ $order->id }}</p>
                    <p class="text-xs text-maroon-400">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <div class="px-5 py-4 space-y-2.5">
                    @foreach ($order->items as $item)
                        <div class="flex items-center justify-between text-sm {{ $item->is_gift ? 'bg-gradient-to-r from-pink-50 to-gold-50 -mx-2 px-2 py-1 rounded-lg' : '' }}">
                            <span class="text-maroon-700">
                                {{ $item->product_name }}
                                @if ($item->portionLabel())
                                    <span class="text-maroon-400">({{ $item->portionLabel() }})</span>
                                @endif
                                <span class="text-maroon-400">× {{ $item->quantity }}</span>
                                @if ($item->is_gift)
                                    <span class="ml-1 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gradient-to-r from-pink-500 to-gold-500 text-white">🎁 {{ __('Gift') }}</span>
                                @endif
                            </span>
                            <span class="text-maroon-800 font-medium">{{ $item->is_gift ? __('FREE') : '₹'.number_format($item->line_total) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="px-5 py-4 border-t border-gold-100 space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-maroon-500">{{ __('Subtotal') }}</span><span class="text-maroon-800">₹{{ number_format($order->subtotal) }}</span></div>
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-between"><span class="text-pista-600">{{ __('Coupon') }} ({{ $order->coupon->code ?? __('applied') }})</span><span class="text-pista-600">−₹{{ number_format($order->discount_amount) }}</span></div>
                    @endif
                    <div class="flex justify-between font-semibold text-base pt-1.5 border-t border-gold-100"><span class="text-maroon-800">{{ __('Total') }}</span><span class="text-maroon-800">₹{{ number_format($order->total) }}</span></div>
                    <p class="text-xs text-maroon-400 pt-1">💵 {{ __('Cash on Delivery') }}</p>
                </div>
                <div class="px-5 py-3.5 border-t border-gold-100">
                    <a href="{{ route('orders.invoice', $order->id) }}" target="_blank"
                       class="w-full inline-flex items-center justify-center gap-2 text-sm font-semibold text-maroon-700 hover:text-maroon-900 border border-gold-300/70 hover:border-gold-400 hover:bg-cream/60 rounded-xl py-2.5 transition">
                        📄 {{ __('Download Invoice (PDF)') }}
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5">
                <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">{{ __('Delivering To') }}</p>
                <p class="text-sm text-maroon-800 font-medium mt-2">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                <p class="text-sm text-maroon-600 mt-1 whitespace-pre-line">{{ $order->delivery_address }}</p>
            </div>

            <p class="text-center text-xs text-maroon-400">
                {{ __('Questions about your order?') }} <a href="tel:+918920937331" class="text-gold-600 font-semibold hover:text-gold-700">{{ __('Call the shop') }}</a>
            </p>
        </div>
    </div>
</div>
