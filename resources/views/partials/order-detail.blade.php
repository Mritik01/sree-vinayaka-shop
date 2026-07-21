{{-- order detail — shared by the standalone /orders/{id} page and the inline "My Orders" tab on
     the account page (fetched via GET /orders/{id}/partial and injected + Alpine.initTree()'d) --}}
<div class="relative max-w-lg lg:max-w-none mx-auto"
     x-data='orderTrackingPage(@json($orderForJs), {{ $justPlaced ? 'true' : 'false' }}, @json(__("in ~:mins min", ["mins" => "␟"])), @json(__("any moment now")))'>

    {{-- ── unavailable-items apology card ─────────────────────────────
         Appears the instant an admin removes something and stays as long as the order has any
         removed item — full width, above both columns, so it's the first thing visible whether
         the page is stacked (mobile) or side-by-side (desktop). One persistent card with a
         tappable header (never swaps to a differently-shaped element — that was what made the
         old expand/collapse look janky, since two separate x-show elements briefly overlapped in
         the layout while cross-fading) — only the detail region's height animates, via the
         "grid-template-rows: 0fr -> 1fr" technique, which smoothly grows/shrinks to the content's
         real height with no JS measurement needed. Opens fully expanded with a 10s countdown bar,
         then auto-collapses; a fresh removal detected mid-poll re-opens it and restarts the
         countdown — see startSorryCollapse() in orderTrackingPage() (app.js), called both from
         init() (already-removed items on first load) and poll() (a newly-detected removal). --}}
    <div x-show="order.items.some(i => i.removed)" x-cloak
         x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 -translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         :class="justRemovedItems ? 'animate-track-breathe' : ''"
         class="relative rounded-3xl shadow-lg overflow-hidden mb-5 bg-gradient-to-br from-gold-100 via-cream to-gold-50 border-2 border-gold-300/70">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #0f3d22 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>

        {{-- header — always visible, toggles the detail region below --}}
        <button type="button" @click="sorryExpanded ? collapseSorry() : reopenSorry()"
                class="relative w-full flex items-center gap-3 px-6 pt-5 pb-3 text-left">
            <span class="text-3xl sm:text-4xl shrink-0">💛</span>
            <div class="min-w-0 flex-1">
                <p class="font-display font-bold text-lg text-maroon-800">{{ __("We're Sorry!") }}</p>
                <p x-show="!sorryExpanded" x-cloak class="text-sm text-maroon-600 mt-0.5 truncate"
                   x-text="order.items.filter(i => i.removed).length + ' {{ __('item(s) removed, tap to view') }}'"></p>
            </div>
            <svg class="w-5 h-5 text-maroon-400 shrink-0 transition-transform duration-300" :class="sorryExpanded ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </button>

        {{-- detail region — height-animated via grid-template-rows; min-h-0 on the inner overflow
             wrapper is required for the trick to actually collapse below its content's size --}}
        <div class="relative grid transition-[grid-template-rows] duration-500 ease-in-out"
             :style="`grid-template-rows: ${sorryExpanded ? '1fr' : '0fr'}`">
            <div class="min-h-0 overflow-hidden">
                {{-- no negative top margin here on purpose — the parent is overflow-hidden (for
                     the height-collapse animation), and a negative margin would get clipped by
                     it, cutting off the top of this text. Spacing to the header above is tightened
                     via that button's own pb-3 instead. --}}
                <div class="px-6 pb-5">
                    <p class="text-sm text-maroon-700 leading-relaxed">
                        {{ __("Unfortunately, a few items from your order are currently unavailable. We've removed them from your order so we can deliver the remaining items without delay. Thank you for your understanding.") }} 🙏
                    </p>

                    <div class="mt-4 space-y-2.5">
                        <template x-for="item in order.items.filter(i => i.removed)" :key="item.id">
                            <div class="flex items-center gap-3 bg-white/70 rounded-xl px-3.5 py-2.5">
                                <div class="w-11 h-11 shrink-0 rounded-lg overflow-hidden bg-cream border border-gold-200/60">
                                    <template x-if="item.image_url">
                                        <img :src="item.image_url" class="w-full h-full object-cover grayscale">
                                    </template>
                                    <template x-if="!item.image_url">
                                        <div class="w-full h-full grid place-items-center text-base">🍬</div>
                                    </template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-maroon-800 truncate" x-text="item.name"></p>
                                    <p class="text-xs text-maroon-500 mt-0.5">
                                        <span x-text="@js(__('Qty removed')) + ': ' + item.quantity"></span>
                                        <span> · </span>
                                        <span x-text="item.removal_reason_label"></span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- 10s auto-collapse countdown — only runs on the initial timed sequence
                     (sorryCountingDown), not after a manual reopen. A single CSS width transition
                     (100% -> 0%, kicked off a frame after mount so the browser actually animates
                     it) rather than a JS tick loop, so it's smooth with no interval overhead --}}
                <div x-show="sorryCountingDown" x-cloak class="relative h-1 bg-gold-200/70">
                    <div class="h-full bg-gold-500 transition-[width] ease-linear"
                         :style="`width: ${sorryProgressReady ? 0 : 100}%; transition-duration: ${sorryProgressReady ? '10000ms' : '0ms'}`"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── order updated celebration card ─────────────────────────────
         Appears the instant an admin adds product(s) to this order (see
         Admin\OrderController::addItems()) and stays as long as the order has any admin-added
         item — same collapsible shape as the "We're Sorry" removed-items card above (tappable
         header, height-animated detail region via grid-template-rows, 10s countdown then
         auto-collapse, re-opens + restarts the countdown on a fresh add), just pista/gold instead
         of muted — see startUpdatedCollapse()/collapseUpdated()/reopenUpdated() in
         orderTrackingPage() (app.js), called both from init() (already-added items on first
         load) and poll() (a newly-detected add). The one-time "beautiful animation" moment lives
         separately in partials/order-updated-popup.blade.php, triggered via
         maybeShowOrderUpdated() — this card is the persistent record so a customer who wasn't
         watching live still sees exactly what was added, even after collapsing/dismissing it. --}}
    <div x-show="order.items.some(i => i.added)" x-cloak
         x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 -translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         :class="justAddedItems ? 'animate-track-breathe' : ''"
         class="relative rounded-3xl shadow-lg overflow-hidden mb-5 bg-gradient-to-br from-pista-100 via-cream to-gold-50 border-2 border-pista-300/70">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #3d7a52 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>

        {{-- header — always visible, toggles the detail region below --}}
        <button type="button" @click="updatedExpanded ? collapseUpdated() : reopenUpdated()"
                class="relative w-full flex items-center gap-3 px-6 pt-5 pb-3 text-left">
            <span class="text-3xl sm:text-4xl shrink-0">🎉</span>
            <div class="min-w-0 flex-1">
                <p class="font-display font-bold text-lg text-maroon-800">{{ __('Your order has been updated!') }}</p>
                <p x-show="!updatedExpanded" x-cloak class="text-sm text-maroon-600 mt-0.5 truncate"
                   x-text="order.items.filter(i => i.added).length + ' {{ __('item(s) added, tap to view') }}'"></p>
                <p x-show="updatedExpanded" x-cloak class="text-sm text-maroon-600 mt-0.5">{{ __("At your request, we've added the following item(s) to your order.") }}</p>
            </div>
            <svg class="w-5 h-5 text-maroon-400 shrink-0 transition-transform duration-300" :class="updatedExpanded ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </button>

        {{-- detail region — height-animated via grid-template-rows; min-h-0 on the inner overflow
             wrapper is required for the trick to actually collapse below its content's size --}}
        <div class="relative grid transition-[grid-template-rows] duration-500 ease-in-out"
             :style="`grid-template-rows: ${updatedExpanded ? '1fr' : '0fr'}`">
            <div class="min-h-0 overflow-hidden">
                <div class="px-6 pb-5">
                    <div class="space-y-2.5">
                        <template x-for="item in order.items.filter(i => i.added)" :key="item.id">
                            <div class="flex items-center gap-3 bg-white/70 rounded-xl px-3.5 py-2.5">
                                <div class="w-11 h-11 shrink-0 rounded-lg overflow-hidden bg-cream border border-pista-200/60">
                                    <template x-if="item.image_url">
                                        <img :src="item.image_url" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!item.image_url">
                                        <div class="w-full h-full grid place-items-center text-base">🍬</div>
                                    </template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-maroon-800 truncate">
                                        <span x-text="item.name"></span>
                                        <template x-if="item.portion_label">
                                            <span class="text-maroon-400" x-text="'(' + item.portion_label + ')'"></span>
                                        </template>
                                    </p>
                                    <p class="text-xs text-maroon-500 mt-0.5" x-text="'× ' + item.quantity"></p>
                                </div>
                                <span class="text-sm font-semibold text-maroon-800 shrink-0" x-text="'₹' + item.line_total.toLocaleString('en-IN')"></span>
                            </div>
                        </template>
                    </div>

                    <p class="text-xs text-pista-700 bg-pista-50 rounded-lg px-3 py-2 mt-4">
                        ✅ {{ __('Your invoice and order total have been updated accordingly.') }}
                    </p>
                </div>

                {{-- 10s auto-collapse countdown — same mechanic as the We're Sorry card's --}}
                <div x-show="updatedCountingDown" x-cloak class="relative h-1 bg-pista-200/70">
                    <div class="h-full bg-pista-500 transition-[width] ease-linear"
                         :style="`width: ${updatedProgressReady ? 0 : 100}%; transition-duration: ${updatedProgressReady ? '10000ms' : '0ms'}`"></div>
                </div>
            </div>
        </div>
    </div>

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
                    <p class="relative inline-block bg-white/30 backdrop-blur-sm text-maroon-900 text-sm font-semibold px-4 py-1.5 rounded-full mt-4">💵 {{ __('Pay') }} ₹{{ number_format($order->balanceDueOnDelivery()) }} {{ __('on delivery') }}</p>
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
                        <span class="relative text-6xl drop-shadow">📦</span>
                    </div>
                    <p class="relative font-display font-bold text-2xl text-white mt-4">{{ __('Packing Your Order!') }}</p>
                    <p class="relative text-white/90 mt-1.5 font-medium">{{ __("The shop confirmed your order — it's being packed for you.") }}</p>
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
                    <p class="relative text-white/90 mt-1.5 font-medium">{{ __('Your order is on its way to you.') }} 💵 {{ __('Keep') }} ₹{{ number_format($order->balanceDueOnDelivery()) }} {{ __('ready.') }}</p>
                    <p x-show="etaText()" class="relative inline-block bg-white/25 backdrop-blur-sm text-white text-sm font-semibold px-4 py-1.5 rounded-full mt-4">
                        🕐 {{ __('Arriving') }} <span x-text="etaText()"></span>
                    </p>
                </div>

                {{-- delivered --}}
                <div x-show="order.status === 'delivered'" x-cloak
                     x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-gradient-to-br from-maroon-600 to-maroon-800 px-6 py-10 text-center overflow-hidden">
                    <div class="absolute inset-0 opacity-15" style="background-image: radial-gradient(circle, #fcd34d 1.5px, transparent 1.5px); background-size: 18px 18px;"></div>
                    <span class="relative inline-block text-6xl drop-shadow animate-track-pop">🎉</span>
                    <p class="relative font-display font-bold text-2xl text-gold-300 mt-4">{{ __('Delivered — Enjoy!') }}</p>
                    <p class="relative text-cream/90 mt-1.5 font-medium">{{ __('Hope every bite tastes like home. Thank you for ordering from Shree Vinayak!') }}</p>
                    <div class="relative flex items-center justify-center gap-3 mt-5 flex-wrap">
                        {{-- primary CTA — only shows when this order has at least one ratable
                             product (non-removed, still pointing at a live product). Label swaps
                             once every distinct product on this order has been rated — ratings are
                             product-scoped (the existing reviews table, reused as-is), so this
                             reflects "have you rated this product before," not just via this order.
                             Click-triggered only, deliberately no auto-open — the delivered moment
                             already has one auto-popup (rider rating); a second would be intrusive. --}}
                        <button type="button" x-show="order.ratable_product_count > 0" x-cloak
                                @click="openProductRatingPopup()"
                                class="btn-gold inline-block text-sm px-6 py-2.5">
                            <span x-show="!order.all_products_rated">⭐ {{ __('Rate Products') }}</span>
                            <span x-show="order.all_products_rated" x-cloak>{{ __('View Your Reviews') }}</span>
                        </button>

                        {{-- Order Again — demoted to a secondary ghost action so "Rate Products"
                             is the primary CTA after delivery, per this feature's design --}}
                        <a href="/#bestsellers"
                           class="inline-flex items-center gap-1.5 text-sm font-semibold text-gold-300 hover:text-gold-200 border border-gold-300/50 hover:border-gold-300 rounded-full px-5 py-2.5 transition">
                            {{ __('Order Again') }}
                        </a>

                        {{-- the low-key, always-available nudge for anyone who skipped the rider-rating
                             popup once — never auto-opens anything itself, just re-dispatches the same
                             event the popup's own auto-trigger uses --}}
                        <button type="button" x-show="order.needs_rating" x-cloak
                                @click="window.dispatchEvent(new CustomEvent('open-rider-rating', { detail: { orderId: order.id, riderName: order.rider_name, riderPhoto: order.rider_photo_url } }))"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-gold-300 hover:text-gold-200 border border-gold-300/50 hover:border-gold-300 rounded-full px-5 py-2.5 transition">
                            ⭐ {{ __('Rate your delivery') }}
                        </button>
                    </div>
                </div>

                {{-- cancelled --}}
                @php
                    // precomputed as plain variables (rather than nested inline expressions inside
                    // @json() below) so Blade's directive-paren-matching has nothing complex to trip
                    // over — this exact spot previously broke silently when the phone interpolation
                    // was nested directly inside @json(...): Blade's paren-counting got confused by
                    // the array literal/?? operator, producing a JS syntax error that Alpine swallows,
                    // rendering this whole message as an empty string with no visible error anywhere
                    $businessPhoneOrFallback = $businessPhone['display'] ?? __('our shop');
                    $systemCancelledMessage = __('We\'re sorry — this order took too long to deliver, so it was automatically cancelled. Please call us at :phone, or feel free to place a fresh order.', ['phone' => $businessPhoneOrFallback]);
                    $adminCancelledMessage = __('The shop couldn\'t take this order. Please call us at :phone if you need help.', ['phone' => $businessPhoneOrFallback]);
                @endphp
                <div x-show="order.status === 'cancelled'" x-cloak
                     x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-gradient-to-br from-maroon-900 to-maroon-700 px-6 py-10 text-center overflow-hidden">
                    <span class="relative inline-block text-6xl drop-shadow">🥀</span>
                    <p class="relative font-display font-bold text-2xl text-cream mt-4">{{ __('Order Cancelled') }}</p>
                    <p class="relative text-cream/80 mt-1.5 font-medium" x-text='order.cancelled_by === "customer"
                        ? @json(__('You cancelled this order. We hope to serve you again soon!'))
                        : (order.cancelled_by === "system"
                            ? @json($systemCancelledMessage)
                            : @json($adminCancelledMessage))'></p>
                    <a href="/#bestsellers" class="relative btn-gold inline-block mt-5 text-sm px-6 py-2.5">{{ __('Browse Store') }}</a>
                </div>
            </div>

            {{-- ── progress timeline ─────────────────────────────── --}}
            <div x-show="order.status !== 'cancelled'" class="bg-white rounded-2xl border border-gold-200/60 shadow-sm px-4 sm:px-6 py-6">
                <div class="flex items-start">
                    @php
                        $steps = [
                            1 => ['icon' => '🧾', 'label' => __('Placed')],
                            2 => ['icon' => '📦', 'label' => __('Packing')],
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

            {{-- mobile-only: same delivery-partner card as below, but positioned right after the
                 tracking section (status hero + timeline) instead of down with the order summary —
                 on phones this is the first thing a customer sees once a rider is assigned, not
                 something to scroll past "Note for the Shop"/"Cancel Order" for. lg:hidden here /
                 hidden lg:block on the sidebar copy below keeps the desktop layout unchanged. --}}
            <div x-show="order.rider_name" x-cloak
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 -translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                 :class="justAssignedRider ? 'animate-track-breathe' : ''"
                 class="lg:hidden bg-gradient-to-br from-gold-50 to-cream rounded-2xl border border-gold-300/70 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="relative shrink-0">
                        <img x-show="order.rider_photo_url" :src="order.rider_photo_url" class="w-14 h-14 rounded-full object-cover border-2 border-gold-400">
                        <span x-show="!order.rider_photo_url" class="w-14 h-14 rounded-full bg-gold-200 border-2 border-gold-400 flex items-center justify-center font-display font-bold text-lg text-gold-700" x-text="order.rider_name ? order.rider_name.charAt(0).toUpperCase() : '🛵'"></span>
                        <span x-show="justAssignedRider" x-cloak class="absolute -bottom-1 -right-1 text-lg animate-track-scooter">🛵</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">{{ __('Your Delivery Partner') }}</p>
                        <p class="text-sm text-maroon-800 font-semibold mt-0.5" x-text="order.rider_name"></p>
                        <a x-show="order.rider_phone" :href="'tel:' + order.rider_phone" class="text-xs text-gold-600 hover:text-gold-700 font-medium" x-text="order.rider_phone"></a>
                    </div>
                </div>
                <p x-show="order.status !== 'delivered' && order.status !== 'cancelled'" x-cloak class="text-sm text-maroon-700 mt-3 leading-relaxed">
                    <span x-text="{{ Illuminate\Support\Js::from(__("Hi, I'm :name. I'll be delivering your order", ['name' => '␟'])) }}.replace('␟', order.rider_name)"></span>
                    <span x-show="etaText()" x-text="' ' + etaText()"></span>
                    <span> {{ __('and will reach you soon. Thank you for shopping with us!') }} 😊</span>
                </p>
                {{-- a rider was already on the case when this got cancelled — the upbeat "on my way"
                     line above would read as tone-deaf here, so swap in something that acknowledges
                     the mix-up instead --}}
                <p x-show="order.status === 'cancelled'" x-cloak class="text-sm text-maroon-700 mt-3 leading-relaxed">
                    <span x-text="{{ Illuminate\Support\Js::from(__("Hi, I'm :name. I was already on my way with your order", ['name' => '␟'])) }}.replace('␟', order.rider_name)"></span>
                    <span> {{ __('… and then it got cancelled on me. 😢') }}</span>
                    <span> {{ __('Hope to deliver something tastier to you next time!') }}</span>
                </p>
            </div>

            {{-- ── note for the shop ─────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 sm:p-6">
                <p class="font-display text-lg text-maroon-800">📝 {{ __('Note for the Shop') }}</p>
                <template x-if="!['out_for_delivery', 'delivered', 'cancelled'].includes(order.status)">
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
                <template x-if="order.status === 'out_for_delivery'">
                    <div>
                        <p class="text-sm text-maroon-500 mt-2" x-text='order.customer_note || @json(__('No note was added to this order.'))'></p>
                        <p class="text-xs text-gold-600 font-medium mt-2">🚴 {{ __("Locked — your order is already on its way") }}</p>
                    </div>
                </template>
                <template x-if="['delivered', 'cancelled'].includes(order.status)">
                    <p class="text-sm text-maroon-500 mt-2" x-text='order.customer_note || @json(__('No note was added to this order.'))'></p>
                </template>

                {{-- persistent status — always visible regardless of page-load timing; the
                     one-time "beautiful animation" moment lives separately in
                     partials/note-decision-popup.blade.php, triggered via maybeShowNoteDecision()
                     in orderTrackingPage() (app.js) --}}
                <template x-if="order.note_status === 'pending'">
                    <p class="text-xs text-gold-600 font-medium mt-3">⏳ {{ __('Waiting for the shop to review your note') }}</p>
                </template>
                <template x-if="order.note_status === 'accepted'">
                    <div class="mt-3 flex items-start gap-2 bg-pista-50 border border-pista-300/60 rounded-xl px-3.5 py-2.5">
                        <span class="text-lg shrink-0">✅</span>
                        <div>
                            <p class="text-sm font-semibold text-pista-700">{{ __('Accepted by the shop') }}</p>
                            <p x-show="order.note_decision_message" x-cloak class="text-xs text-maroon-600 mt-0.5" x-text="order.note_decision_message"></p>
                        </div>
                    </div>
                </template>
                <template x-if="order.note_status === 'denied'">
                    <div class="mt-3 flex items-start gap-2 bg-red-50 border border-red-300/60 rounded-xl px-3.5 py-2.5">
                        <span class="text-lg shrink-0">😔</span>
                        <div>
                            <p class="text-sm font-semibold text-red-700">{{ __("Couldn't be accommodated this time") }}</p>
                            <p x-show="order.note_decision_message" x-cloak class="text-xs text-maroon-600 mt-0.5" x-text="order.note_decision_message"></p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- ── cancel order ──────────────────────────────────── --}}
            <div x-show="cancelVisible()" x-cloak class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5 sm:p-6">
                <div x-show="!confirmingCancel">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-display text-maroon-800">{{ __('Changed your mind?') }}</p>
                            <p class="text-xs text-maroon-400 mt-0.5">{{ __('You can cancel free of charge within') }} {{ \App\Models\Order::CUSTOMER_CANCEL_WINDOW_MINUTES }} {{ __('minutes of placing the order.') }}</p>
                            <p x-show="cancelCountdownText()" x-cloak class="text-xs text-gold-600 font-semibold mt-1" x-text="cancelCountdownText()"></p>
                        </div>
                        <button @click="confirmingCancel = true; cancelError = ''" type="button"
                                class="text-sm font-semibold text-red-500 hover:text-red-600 border border-red-200 hover:bg-red-50 rounded-xl px-4 py-2 transition">
                            {{ __('Cancel Order') }}
                        </button>
                    </div>
                </div>
                <div x-show="confirmingCancel" x-cloak x-transition>
                    <p class="font-display text-maroon-800">{{ __('Cancel order') }} <span x-text="order.order_number"></span>?</p>
                    <p class="text-sm text-maroon-500 mt-1">{{ __("The sweets won't be prepared and nothing is charged.") }}</p>
                    <label class="block text-xs font-medium text-maroon-500 mt-3 mb-1.5">{{ __('Reason (optional) — helps the shop improve') }}</label>
                    <input type="text" x-model="cancelReason" maxlength="255" placeholder="{{ __('e.g. Ordered by mistake, found it cheaper elsewhere…') }}"
                           class="w-full rounded-lg border border-gold-300/70 px-3.5 py-2 text-sm text-maroon-800 placeholder-maroon-400/50 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
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
                    <p class="font-display text-maroon-800">{{ $order->orderNumber() }}</p>
                    <p class="text-xs text-maroon-400">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                </div>
                {{-- items + totals are bound to the polled `order` object (not static Blade) so
                     an admin removing an item shows up here within one 5s poll tick, same as a
                     status change already does — see orderTrackingPage().poll() in app.js and
                     OrderTrackingController::payload(), which now includes items/subtotal/
                     discount_amount/fees/total. Removed items stay in the list (struck through,
                     badged "Removed") so nothing silently vanishes from the order history, while
                     the printed totals below already exclude them (recalculated server-side). --}}
                <div class="px-5 py-4 space-y-2.5">
                    <template x-for="item in order.items" :key="item.id">
                        <div class="flex items-center justify-between text-sm gap-2"
                             :class="(item.is_gift &amp;&amp; !item.removed) ? 'bg-gradient-to-r from-pink-50 to-gold-50 -mx-2 px-2 py-1 rounded-lg' : ''">
                            {{-- the "Removed" badge is a true sibling of the struck-through name
                                 span below, not nested inside it — a couple of WebKit versions
                                 don't reliably respect a child's own text-decoration-line:none
                                 override against an ancestor's line-through, so the badge has to
                                 sit outside that element's DOM subtree entirely to guarantee it
                                 never gets a line drawn through it --}}
                            <span class="inline-flex items-center flex-wrap gap-1">
                                <span :class="item.removed ? 'text-maroon-400 line-through' : 'text-maroon-700'">
                                    <span x-text="item.name"></span>
                                    <template x-if="item.portion_label">
                                        <span class="text-maroon-400" x-text="'(' + item.portion_label + ')'"></span>
                                    </template>
                                    <span class="text-maroon-400" x-text="'× ' + item.quantity"></span>
                                    <template x-if="item.is_gift &amp;&amp; !item.removed">
                                        <span class="ml-1 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gradient-to-r from-pink-500 to-gold-500 text-white">🎁 {{ __('Gift') }}</span>
                                    </template>
                                </span>
                                <template x-if="item.removed">
                                    <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-red-100 text-red-600">{{ __('Removed') }}</span>
                                </template>
                            </span>
                            <span :class="item.removed ? 'text-maroon-300 line-through' : 'text-maroon-800 font-medium'"
                                  x-text="item.is_gift ? @js(__('FREE')) : ('₹' + item.line_total.toLocaleString('en-IN'))"></span>
                        </div>
                    </template>
                    <p x-show="order.items.some(i => i.removed)" x-cloak class="text-xs text-gold-700 bg-gold-50 rounded-lg px-3 py-2 mt-1">
                        ℹ️ {{ __('Your order total has been updated after removing unavailable item(s).') }}
                    </p>
                </div>
                <div class="px-5 py-4 border-t border-gold-100 space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-maroon-500">{{ __('Subtotal') }}</span><span class="text-maroon-800" x-text="'₹' + order.subtotal.toLocaleString('en-IN')"></span></div>
                    <template x-if="order.discount_amount > 0">
                        <div class="flex justify-between">
                            <span class="text-pista-600" x-text="@js(__('Coupon')) + ' (' + (order.coupon_code || @js(__('applied'))) + ')'"></span>
                            <span class="text-pista-600" x-text="'−₹' + order.discount_amount.toLocaleString('en-IN')"></span>
                        </div>
                    </template>
                    <template x-for="fee in order.fees" :key="fee.label">
                        <div class="flex justify-between"><span class="text-maroon-500" x-text="fee.label"></span><span class="text-maroon-800" x-text="'₹' + fee.amount.toLocaleString('en-IN')"></span></div>
                    </template>
                    <div class="flex justify-between font-semibold text-base pt-1.5 border-t border-gold-100"><span class="text-maroon-800">{{ __('Total') }}</span><span class="text-maroon-800" x-text="'₹' + order.total.toLocaleString('en-IN')"></span></div>
                    <p class="text-xs text-maroon-400 pt-1">
                        @if ($order->payment_method === 'razorpay' && $order->payment_status === 'paid')
                            {{-- balance_due is 0 for an ordinary settled payment (unchanged copy);
                                 only shows a "pay the difference" line when an admin added items
                                 to this order after it was already paid — see
                                 Order::balanceDueOnDelivery() --}}
                            <template x-if="order.balance_due === 0">
                                <span>💳 {{ __('Paid Online') }}</span>
                            </template>
                            <template x-if="order.balance_due > 0">
                                <span>💳 <span x-text="@js(__('Paid')) + ' ₹' + order.amount_paid.toLocaleString('en-IN')"></span> · <span x-text="@js(__('Pay')) + ' ₹' + order.balance_due.toLocaleString('en-IN') + ' ' + @js(__('on delivery'))"></span></span>
                            </template>
                        @elseif ($order->payment_method === 'razorpay')
                            💳 {{ __('Paid Online') }} ({{ __('payment ' . $order->payment_status) }})
                        @elseif ($order->codPaymentReceived())
                            ✅ {{ __('Cash on Delivery') }} — {{ __('Payment Received') }}
                        @else
                            💵 {{ __('Cash on Delivery') }}
                        @endif
                    </p>
                </div>
                <div class="px-5 py-3.5 border-t border-gold-100">
                    <a href="{{ route('orders.invoice', $order->id) }}" target="_blank"
                       class="w-full inline-flex items-center justify-center gap-2 text-sm font-semibold text-maroon-700 hover:text-maroon-900 border border-gold-300/70 hover:border-gold-400 hover:bg-cream/60 rounded-xl py-2.5 transition">
                        📄 {{ __('Download Invoice (PDF)') }}
                    </a>
                </div>
            </div>

            {{-- ── delivery partner: appears the moment a rider is assigned, stays as a normal
                 persistent part of the page from then on. The entrance itself (fade/slide/scale in)
                 is the "celebration" — no popup. justAssignedRider (set for ~6s after a genuine
                 assignment/reassignment, see orderTrackingPage().poll() in app.js) layers on a
                 temporary glow pulse + bouncing scooter, then the card settles quietly.
                 Desktop-only here — see the lg:hidden twin right after the tracking section above,
                 which is what phones show instead. --}}
            <div x-show="order.rider_name" x-cloak
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 -translate-y-3 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                 :class="justAssignedRider ? 'animate-track-breathe' : ''"
                 class="hidden lg:block bg-gradient-to-br from-gold-50 to-cream rounded-2xl border border-gold-300/70 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="relative shrink-0">
                        <img x-show="order.rider_photo_url" :src="order.rider_photo_url" class="w-14 h-14 rounded-full object-cover border-2 border-gold-400">
                        <span x-show="!order.rider_photo_url" class="w-14 h-14 rounded-full bg-gold-200 border-2 border-gold-400 flex items-center justify-center font-display font-bold text-lg text-gold-700" x-text="order.rider_name ? order.rider_name.charAt(0).toUpperCase() : '🛵'"></span>
                        <span x-show="justAssignedRider" x-cloak class="absolute -bottom-1 -right-1 text-lg animate-track-scooter">🛵</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">{{ __('Your Delivery Partner') }}</p>
                        <p class="text-sm text-maroon-800 font-semibold mt-0.5" x-text="order.rider_name"></p>
                        <a x-show="order.rider_phone" :href="'tel:' + order.rider_phone" class="text-xs text-gold-600 hover:text-gold-700 font-medium" x-text="order.rider_phone"></a>
                    </div>
                </div>
                <p x-show="order.status !== 'delivered' && order.status !== 'cancelled'" x-cloak class="text-sm text-maroon-700 mt-3 leading-relaxed">
                    <span x-text="{{ Illuminate\Support\Js::from(__("Hi, I'm :name. I'll be delivering your order", ['name' => '␟'])) }}.replace('␟', order.rider_name)"></span>
                    <span x-show="etaText()" x-text="' ' + etaText()"></span>
                    <span> {{ __('and will reach you soon. Thank you for shopping with us!') }} 😊</span>
                </p>
                {{-- a rider was already on the case when this got cancelled — the upbeat "on my way"
                     line above would read as tone-deaf here, so swap in something that acknowledges
                     the mix-up instead --}}
                <p x-show="order.status === 'cancelled'" x-cloak class="text-sm text-maroon-700 mt-3 leading-relaxed">
                    <span x-text="{{ Illuminate\Support\Js::from(__("Hi, I'm :name. I was already on my way with your order", ['name' => '␟'])) }}.replace('␟', order.rider_name)"></span>
                    <span> {{ __('… and then it got cancelled on me. 😢') }}</span>
                    <span> {{ __('Hope to deliver something tastier to you next time!') }}</span>
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5">
                <p class="text-xs text-maroon-400 uppercase tracking-wide font-semibold">{{ __('Delivering To') }}</p>
                <p class="text-sm text-maroon-800 font-medium mt-2">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                <p class="text-sm text-maroon-600 mt-1 whitespace-pre-line">{{ $order->delivery_address }}</p>
            </div>

            @if ($businessPhone)
                <p class="text-center text-xs text-maroon-400">
                    {{ __('Questions about your order?') }} <a href="{{ $businessPhone['tel'] }}" class="text-gold-600 font-semibold hover:text-gold-700">{{ __('Call the shop') }}</a>
                </p>
            @endif
        </div>
    </div>

    {{-- ── support chat: launcher + minimized bar + window ────────────
         full-screen sheet on phones, floating/resizable panel bottom-right on sm+. Positioned
         well above the floating "View Cart" pill (which sits at bottom-20 on this page) so the
         two never overlap — see the bottom-36 offset below. --}}
    <div x-data="supportChat({{ $order->id }}, {{ ($autoOpenChat ?? false) ? 'true' : 'false' }})" @keydown.escape.window="panelOpen && closeChat()">

        {{-- launcher: fresh, chat fully closed --}}
        <button x-show="!panelOpen" x-cloak @click="openChat()" type="button"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                class="fixed bottom-36 right-4 lg:bottom-6 lg:right-6 z-[60] flex items-center gap-2 pl-4 pr-5 py-3 rounded-full bg-gradient-to-r from-maroon-700 to-maroon-800 text-cream shadow-xl shadow-maroon-900/30 hover:shadow-2xl hover:scale-105 active:scale-95 transition"
                aria-label="{{ __('Chat with support') }}">
            <span class="relative grid place-items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                </svg>
                <span x-show="unread > 0" x-cloak class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold grid place-items-center animate-bounce"
                      x-text="unread > 9 ? '9+' : unread"></span>
            </span>
            <span class="text-sm font-semibold">{{ __('Help') }}</span>
        </button>

        {{-- minimized "resume" bar: conversation open, just collapsed — the order page behind
             is fully usable while this shows --}}
        <button x-show="panelOpen && minimized" x-cloak @click="restoreChat()" type="button"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
                class="fixed bottom-36 right-4 lg:bottom-6 lg:right-6 z-[110] flex items-center gap-2.5 pl-2.5 pr-4 py-2.5 rounded-full bg-gradient-to-r from-maroon-700 to-maroon-800 text-cream shadow-xl shadow-maroon-900/30 hover:shadow-2xl active:scale-95 transition"
                aria-label="{{ __('Resume chat with support') }}">
            <span class="relative w-8 h-8 shrink-0 rounded-full bg-gold-500 grid place-items-center text-base shadow-inner">🍬</span>
            <span class="text-sm font-semibold">{{ __('Support chat') }}</span>
            <span x-show="unread > 0" x-cloak class="min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold grid place-items-center animate-bounce"
                  x-text="unread > 9 ? '9+' : unread"></span>
            <svg class="w-4 h-4 shrink-0 text-cream/70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
            </svg>
        </button>

        {{-- chat window --}}
        <div x-show="panelOpen && !minimized" x-cloak x-ref="panel"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-4 sm:scale-95"
             :style="sizeStyle()"
             class="fixed inset-x-0 top-0 h-[100dvh] z-[120] flex flex-col bg-ivory overflow-hidden
                    sm:inset-auto sm:top-auto sm:bottom-6 sm:right-6 sm:max-h-[calc(100dvh-3rem)] sm:rounded-3xl sm:border sm:border-gold-300/50 sm:shadow-2xl">

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
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #fcd34d 1.5px, transparent 1.5px); background-size: 16px 16px;"></div>
                <span class="relative w-10 h-10 shrink-0 rounded-full bg-gold-500 grid place-items-center text-xl shadow-inner">🍬</span>
                <div class="relative min-w-0 flex-1">
                    <p class="font-display font-semibold text-cream leading-tight">{{ __('Shree Vinayak Support') }}</p>
                    <p class="text-[11px] text-cream/70 truncate">{{ $order->orderNumber() }} · {{ __('we usually reply in a few minutes') }}</p>
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

            {{-- messages --}}
            <div x-ref="chatThread" class="flex-1 overflow-y-auto overscroll-contain px-4 py-4">

                {{-- empty state: warm welcome + one-tap starters --}}
                <div x-show="loaded && messages.length === 0" x-cloak class="h-full flex flex-col items-center justify-center text-center px-4">
                    <span class="text-5xl">🤝</span>
                    <p class="font-display text-lg text-maroon-800 mt-3">{{ __('Hi') }} {{ Str::of($order->customer_name)->before(' ') }}!</p>
                    <p class="text-sm text-maroon-500 mt-1">{{ __('Facing an issue with this order? Tell us — a real person from the shop will reply right here.') }}</p>
                    <div class="flex flex-wrap justify-center gap-2 mt-5">
                        @foreach ([__('Where is my order?'), __('I have a payment issue'), __('Wrong or missing item'), __('Quality issue with the sweets')] as $starter)
                            <button @click="send(@js($starter))" type="button"
                                    class="text-xs font-semibold text-maroon-700 bg-white border border-gold-300/70 hover:border-gold-500 hover:bg-gold-50 rounded-full px-3.5 py-2 active:scale-95 transition">
                                {{ $starter }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <template x-for="m in messages" :key="m.id">
                    <div>
                        <div x-show="m.showDay" class="text-center my-3">
                            <span class="text-[10px] font-semibold text-maroon-400 bg-cream border border-gold-200/60 rounded-full px-3 py-1" x-text="m.day"></span>
                        </div>
                        <div class="flex items-end gap-2 mt-2" :class="m.sender === 'customer' ? 'justify-end' : 'justify-start'">
                            <span x-show="m.sender === 'admin'" class="w-7 h-7 shrink-0 rounded-full bg-gold-100 border border-gold-300/60 grid place-items-center text-sm mb-4">🍬</span>
                            <div class="max-w-[80%] sm:max-w-[75%]">
                                <a x-show="m.image_url" x-cloak :href="m.image_url" target="_blank" rel="noopener"
                                   class="block max-w-[210px] rounded-2xl overflow-hidden border border-gold-200/60 shadow-sm mb-1">
                                    <img :src="m.image_url" class="w-full h-auto object-cover" loading="lazy" alt="">
                                </a>
                                <div x-show="m.message" x-cloak
                                     class="px-3.5 py-2.5 text-sm leading-relaxed whitespace-pre-line break-words shadow-sm"
                                     :class="m.sender === 'customer'
                                         ? 'bg-gradient-to-br from-maroon-700 to-maroon-800 text-cream rounded-2xl rounded-br-md'
                                         : 'bg-white text-maroon-800 border border-gold-200/70 rounded-2xl rounded-bl-md'"
                                     x-text="m.message"></div>
                                <p class="text-[10px] text-maroon-400 mt-1 px-1" :class="m.sender === 'customer' && 'text-right'" x-text="m.time"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- composer --}}
            <div class="shrink-0 border-t border-gold-200/60 bg-white px-3 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                <p x-show="sendError" x-cloak class="text-red-600 text-xs mb-2 px-1" x-text="sendError"></p>

                {{-- pending photo preview — shown above the input once one's picked, before send --}}
                <div x-show="pendingImagePreview" x-cloak class="relative inline-block mb-2 ml-1">
                    <img :src="pendingImagePreview" class="h-16 w-16 object-cover rounded-xl border border-gold-300/60 shadow-sm">
                    <button @click="clearPendingImage()" type="button" aria-label="{{ __('Remove photo') }}"
                            class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-maroon-800 text-cream text-xs grid place-items-center shadow">✕</button>
                </div>

                <div class="flex items-center gap-2">
                    {{-- hidden file input: plain accept="image/*" (no `capture`) lets mobile browsers
                         offer both "Take Photo" and "Choose from Library" instead of camera-only --}}
                    <input x-ref="imageInput" type="file" accept="image/*" class="hidden" @change="onImageSelected($event)">
                    <button @click="$refs.imageInput.click()" type="button" aria-label="{{ __('Attach a photo') }}"
                            class="shrink-0 w-10 h-10 rounded-full grid place-items-center text-maroon-500 hover:text-maroon-700 hover:bg-cream transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 4.5h16.5a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5Zm10.125 3.375a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </button>
                    {{-- text-base (16px) so iOS doesn't zoom the page when the input focuses --}}
                    <input x-ref="chatInput" x-model="draft" @keydown.enter.prevent="send()"
                           type="text" maxlength="1000" placeholder="{{ __('Type your message…') }}"
                           class="flex-1 min-w-0 rounded-full border border-gold-300/70 bg-cream/50 px-4 py-2.5 text-base sm:text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                    <button @click="send()" :disabled="sending || (!draft.trim() && !pendingImage)" type="button" aria-label="{{ __('Send message') }}"
                            class="shrink-0 w-11 h-11 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 text-maroon-900 grid place-items-center shadow-md hover:shadow-lg active:scale-90 transition disabled:opacity-40 disabled:shadow-none">
                        <svg class="w-5 h-5 translate-x-[1px]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('partials.rider-rating-popup')
    @include('partials.product-rating-popup')
    @include('partials.note-decision-popup')
    @include('partials.order-updated-popup')
</div>
