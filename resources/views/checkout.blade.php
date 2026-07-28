@extends('layouts.app')

@section('title', 'Checkout — Shri Vinayak Family Shop')

@section('content')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<section class="relative py-14 bg-ivory min-h-[70vh]">
    <div class="max-w-6xl lg:max-w-none mx-auto px-4 sm:px-6 lg:px-10 xl:px-16">
        <nav class="text-sm text-maroon-500 flex items-center gap-2 mb-6">
            <a href="/" class="hover:text-gold-600 transition">{{ __('Home') }}</a>
            <span class="text-gold-400">✦</span>
            <a href="/cart" class="hover:text-gold-600 transition">{{ __('Cart') }}</a>
            <span class="text-gold-400">✦</span>
            <span class="text-maroon-700 font-medium">{{ __('Checkout') }}</span>
        </nav>

        <h1 class="font-display text-2xl sm:text-4xl text-maroon-800">{{ __('Checkout') }}</h1>

        <div x-cloak x-data='checkoutPage(@json($cartItemsForJs), @json($appliedCoupon), @json($addresses), @json($defaultName), @json($defaultPhone), @json($reward), @json($buyNowProductId), @json($buyNowQuantity), @json($availableCoupons), @json($buyNowPortion))'>
            {{-- grid-cols-1 is explicit (not just the implicit default) on purpose: without it,
                 mobile falls back to the browser's implicit auto-track grid sizing, which lets a
                 child's intrinsic min-content width push the whole row past the viewport instead
                 of shrinking to fit — the actual source of the horizontal-scroll bug on mobile --}}
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-4 sm:gap-6 lg:gap-8 mt-6 sm:mt-8 items-start">
                <div class="space-y-4 sm:space-y-5 min-w-0">
                    {{-- review items --}}
                    <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-4 sm:p-6">
                        <div class="flex items-center justify-between">
                            <p class="font-display text-lg text-maroon-800">{{ __('Review Items') }}</p>
                            <a href="/cart" x-show="!buyNowProductId" class="text-xs text-maroon-500 hover:text-gold-600 underline transition">{{ __('Edit cart') }}</a>
                        </div>
                        <div class="mt-3 sm:mt-4 divide-y divide-gold-100">
                            <template x-for="item in items" :key="item.id">
                                <div class="flex items-center gap-3 sm:gap-3.5 py-3">
                                    <img :src="item.image" :alt="item.name" class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg object-cover border border-gold-200/60 shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-maroon-800 font-medium truncate text-sm sm:text-base" x-text="item.name"></p>
                                        <p class="text-xs text-maroon-400" x-text="(item.type === 'loose' ? portionLabel(item.portion, item.unit) : item.weight) + ' × ' + item.quantity"></p>
                                    </div>
                                    <p class="text-maroon-800 font-semibold shrink-0 text-sm sm:text-base">₹<span x-text="item.price * item.quantity"></span></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- recipient details --}}
                    <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-4 sm:p-6">
                        <p class="font-display text-lg text-maroon-800">{{ __('Recipient Details') }}</p>
                        <p class="text-xs text-maroon-400 mt-1">{{ __('Ordering for someone else? Update the name and number below.') }}</p>
                        <div class="grid sm:grid-cols-2 gap-3 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-maroon-700 mb-1.5">{{ __('Name') }}</label>
                                <input type="text" x-model="customerName" placeholder="{{ __('Who is this order for?') }}"
                                       class="w-full rounded-lg border border-gold-300/70 bg-cream/60 px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-maroon-700 mb-1.5">{{ __('Phone') }}</label>
                                <input type="tel" x-model="customerPhone" maxlength="10" inputmode="numeric" placeholder="{{ __('10-digit mobile number') }}"
                                       class="w-full rounded-lg border border-gold-300/70 bg-cream/60 px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                            </div>
                        </div>
                    </div>

                    {{-- delivery address --}}
                    <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-4 sm:p-6">
                        <p class="font-display text-lg text-maroon-800">{{ __('Delivery Address') }}</p>

                        <div class="mt-4 space-y-2.5" x-show="addresses.length > 0">
                            <template x-for="addr in addresses" :key="addr.id">
                                <div @click="selectAddress(addr)"
                                     class="relative rounded-xl border p-3 sm:p-3.5 cursor-pointer transition"
                                     :class="selectedAddressId === addr.id && !showNewAddressForm ? 'border-gold-500 ring-2 ring-gold-300 bg-gold-50/40' : 'border-gold-200/60 hover:border-gold-400'">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-maroon-800 flex items-center gap-2 flex-wrap">
                                                <span x-text='addr.label || @json(__('Address'))'></span>
                                                <span x-show="addr.is_default" x-cloak class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-gold-100 text-gold-700 border border-gold-300/60">{{ __('Default') }}</span>
                                                <span x-show="!addr.within_range" x-cloak class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">{{ __('Outside delivery area') }}</span>
                                            </p>
                                            <p class="text-xs text-maroon-500 mt-1 break-words" x-text="addr.address_line"></p>
                                        </div>
                                        <div class="flex items-center gap-2.5 shrink-0 text-xs">
                                            <button type="button" x-show="!addr.is_default" x-cloak @click.stop="makeDefault(addr)" class="text-maroon-400 hover:text-gold-600 underline transition py-1">{{ __('Make default') }}</button>
                                            <button type="button" @click.stop="deleteAddress(addr)" aria-label="Delete address" class="text-maroon-300 hover:text-red-600 transition p-1 -m-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9M4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <button type="button" x-show="!showNewAddressForm" @click="openNewAddressForm()"
                                    class="text-sm font-semibold text-gold-600 hover:text-gold-700 underline underline-offset-2 transition py-1">
                                + {{ __('Add a new address') }}
                            </button>
                        </div>

                        {{-- new address form --}}
                        <div x-show="showNewAddressForm" x-cloak class="mt-4" :class="addresses.length > 0 && 'pt-4 border-t border-gold-100'">
                            <div x-show="addresses.length > 0" class="flex justify-end mb-2">
                                <button type="button" @click="showNewAddressForm = false; selectedAddressId = addresses.find(a => a.is_default)?.id ?? addresses[0]?.id ?? null"
                                        class="text-xs text-maroon-400 hover:text-maroon-600 underline transition">{{ __('Use a saved address instead') }}</button>
                            </div>

                            <label class="block text-sm font-medium text-maroon-700 mb-1.5">{{ __('Label') }} <span class="text-maroon-400 font-normal">({{ __('optional') }})</span></label>
                            <input type="text" x-model="newAddressLabel" placeholder="{{ __('e.g. Home, Work') }}"
                                   class="w-full rounded-lg border border-gold-300/70 bg-cream/60 px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">

                            <label class="block text-sm font-medium text-maroon-700 mb-1.5 mt-3">{{ __('Delivery Address') }}</label>
                            <textarea x-model="newAddressText" @input="checkoutError = ''" rows="3"
                                      placeholder="{{ __('House / shop, street, landmark, village or town') }}"
                                      class="w-full rounded-lg border border-gold-300/70 bg-cream/60 px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition"></textarea>

                            {{-- live delivery-area status (only when the Siswa Bazar-only restriction is on) --}}
                            <div x-show="$store.shop.restricted" x-cloak class="mt-3">
                                <div x-show="locationStatus === 'idle'" x-cloak class="rounded-lg bg-gold-100/60 border border-gold-300/60 text-maroon-700 text-xs px-3.5 py-2.5">
                                    📍 {{ __('We currently deliver only within') }} <span class="font-semibold" x-text="$store.shop.radiusKm"></span> {{ __('km of Siswa Bazar.') }}
                                    <button @click="checkDeliveryArea()" type="button" class="font-semibold text-gold-600 hover:text-gold-700 underline underline-offset-2 transition">
                                        {{ __('Check if we deliver to you') }}
                                    </button>
                                </div>
                                <div x-show="locationStatus === 'checking'" x-cloak class="rounded-lg bg-gold-100/60 border border-gold-300/60 text-maroon-700 text-xs px-3.5 py-2.5">
                                    📍 {{ __('Checking your location…') }}
                                </div>
                                <div x-show="locationStatus === 'inside'" x-cloak class="rounded-lg bg-pista-100 border border-pista-400/40 text-pista-600 text-xs px-3.5 py-2.5">
                                    ✅ {{ __('Great — you\'re') }} <span class="font-semibold" x-text="locationDistanceKm"></span> {{ __('km from our shop, inside the delivery area.') }}
                                </div>
                                <div x-show="locationStatus === 'outside'" x-cloak class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs px-3.5 py-2.5">
                                    🚫 {{ __('Sorry — you\'re about') }} <span class="font-semibold" x-text="locationDistanceKm"></span> {{ __('km away, outside our') }}
                                    <span x-text="$store.shop.radiusKm"></span> {{ __('km delivery area around Siswa Bazar.') }}
                                </div>
                                <div x-show="locationStatus === 'denied'" x-cloak class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs px-3.5 py-2.5">
                                    ⚠️ {{ __("We couldn't access your location. Please allow location access in your browser — we need it to confirm you're inside our") }}
                                    <span x-text="$store.shop.radiusKm"></span> {{ __('km delivery area.') }}
                                    <button @click="checkDeliveryArea()" type="button" class="font-semibold underline underline-offset-2">{{ __('Try again') }}</button>
                                </div>
                            </div>

                            {{-- when there's no delivery-radius restriction, sharing exact location is still
                                 useful (helps the delivery rider find the address) but purely optional --}}
                            <div x-show="!$store.shop.restricted" x-cloak class="mt-3">
                                <div x-show="locationStatus === 'idle'" x-cloak class="rounded-lg bg-gold-100/60 border border-gold-300/60 text-maroon-700 text-xs px-3.5 py-2.5 flex items-center justify-between gap-3 flex-wrap">
                                    <span>📍 {{ __('Share your exact location to help our delivery rider find you faster.') }}</span>
                                    <button @click="captureLocationOptional()" type="button" class="shrink-0 font-semibold text-gold-600 hover:text-gold-700 underline underline-offset-2 transition py-1">
                                        {{ __('Share location') }}
                                    </button>
                                </div>
                                <div x-show="locationStatus === 'checking'" x-cloak class="rounded-lg bg-gold-100/60 border border-gold-300/60 text-maroon-700 text-xs px-3.5 py-2.5">
                                    📍 {{ __('Getting your location…') }}
                                </div>
                                <div x-show="locationStatus === 'captured'" x-cloak class="rounded-lg bg-pista-100 border border-pista-400/40 text-pista-600 text-xs px-3.5 py-2.5">
                                    ✅ {{ __("Location shared — this'll help our delivery rider find you faster.") }}
                                </div>
                                <div x-show="locationStatus === 'denied'" x-cloak class="rounded-lg bg-cream border border-gold-200/60 text-maroon-500 text-xs px-3.5 py-2.5">
                                    {{ __("No worries — we'll call to confirm your address before delivering.") }}
                                    <button @click="captureLocationOptional()" type="button" class="font-semibold text-gold-600 underline underline-offset-2">{{ __('Try again') }}</button>
                                </div>
                            </div>

                            <label class="flex items-center gap-2 mt-3 text-sm text-maroon-600">
                                <input type="checkbox" x-model="saveNewAddress" class="rounded border-gold-300 text-gold-600 focus:ring-gold-400">
                                {{ __('Save this address for next time') }}
                            </label>
                        </div>
                    </div>

                </div>

                {{-- order summary --}}
                <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm p-4 sm:p-6 lg:sticky lg:top-28 min-w-0">
                    <p class="font-display text-lg text-maroon-800">{{ __('Order Summary') }}</p>

                    <div class="flex items-center justify-between mt-4 sm:mt-5 text-sm">
                        <span class="text-maroon-500">{{ __('Subtotal') }}</span>
                        <span class="text-maroon-800 font-semibold">₹<span x-text="subtotal()"></span></span>
                    </div>

                    @include('partials.free-delivery-progress')

                    <div x-show="coupon" x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="flex items-center justify-between mt-2.5 text-sm">
                        <span class="text-green-700">{{ __('Coupon discount') }}</span>
                        <span class="text-green-700 font-semibold">−₹<span x-text="discount()"></span></span>
                    </div>

                    {{-- operational fees — itemized separately per fee, never merged into one number --}}
                    <div x-show="deliveryFee() > 0" x-cloak class="flex items-center justify-between mt-2.5 text-sm">
                        <span class="text-maroon-500">{{ __('Delivery Fee') }}</span>
                        <span class="text-maroon-800 font-semibold">₹<span x-text="deliveryFee()"></span></span>
                    </div>
                    <div x-show="rainFee() > 0" x-cloak class="flex items-center justify-between mt-2.5 text-sm">
                        <span class="text-sky-700">🌧️ {{ __('Rain Fee') }}</span>
                        <span class="text-sky-700 font-semibold">₹<span x-text="rainFee()"></span></span>
                    </div>
                    <div x-show="highDemandFee() > 0" x-cloak class="flex items-center justify-between mt-2.5 text-sm">
                        <span class="text-amber-700">⚡ {{ __('High Demand Fee') }}</span>
                        <span class="text-amber-700 font-semibold">₹<span x-text="highDemandFee()"></span></span>
                    </div>

                    <div class="mt-4">
                        <p class="text-sm text-maroon-500 mb-2">{{ __('Payment') }}</p>
                        {{-- admin-configurable (Configuration → Payment Methods); a lone remaining
                             method spans the full row instead of leaving an empty half-width gap --}}
                        <div class="grid gap-2 sm:gap-2.5" :class="($store.shop.codEnabled && $store.shop.razorpayEnabled) ? 'grid-cols-2' : 'grid-cols-1'">
                            <button type="button" x-show="$store.shop.codEnabled" @click="paymentMethod = 'cod'"
                                    class="rounded-lg border px-2.5 sm:px-3 py-2.5 text-xs sm:text-sm font-medium text-center transition leading-snug"
                                    :class="paymentMethod === 'cod' ? 'border-gold-500 ring-2 ring-gold-300 bg-gold-50/40 text-maroon-800' : 'border-gold-200/60 text-maroon-500 hover:border-gold-400'">
                                {{ __('Cash on Delivery') }}
                            </button>
                            <button type="button" x-show="$store.shop.razorpayEnabled" @click="paymentMethod = 'razorpay'"
                                    class="rounded-lg border px-2.5 sm:px-3 py-2.5 text-xs sm:text-sm font-medium text-center transition leading-snug"
                                    :class="paymentMethod === 'razorpay' ? 'border-gold-500 ring-2 ring-gold-300 bg-gold-50/40 text-maroon-800' : 'border-gold-200/60 text-maroon-500 hover:border-gold-400'">
                                {{ __('Pay Online') }}
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-gold-200/70 mt-4 pt-4 flex items-center justify-between">
                        <span class="text-maroon-800 font-medium">{{ __('Total') }}</span>
                        <span class="font-display font-semibold text-xl sm:text-2xl text-maroon-800">₹<span x-text="total()"></span></span>
                    </div>

                    {{-- coupon --}}
                    <div class="mt-5">
                        {{-- coupons an admin assigned to this customer specifically — offered up front so
                             they don't need to know or type a code --}}
                        <div x-show="!coupon && availableCoupons.length > 0" x-cloak
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="space-y-2 mb-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gold-600">🎁 {{ __('Your Coupons') }}</p>
                            <template x-for="c in availableCoupons" :key="c.code">
                                <div x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 -translate-x-2 scale-95"
                                     x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                                     x-transition:leave-end="opacity-0 translate-x-2 scale-95"
                                     class="flex items-center justify-between gap-3 rounded-lg border border-gold-400/70 bg-gold-100/50 px-3 py-2.5">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-maroon-800 tracking-wide" x-text="c.code"></p>
                                        <p class="text-xs text-maroon-500 truncate" x-text="c.description"></p>
                                    </div>
                                    <button type="button" @click="applyCoupon(c.code)" :disabled="applyingCoupon"
                                            class="shrink-0 text-xs font-semibold px-3.5 py-1.5 rounded-lg bg-maroon-700 text-cream hover:bg-maroon-800 transition disabled:opacity-50">
                                        {{ __('Apply') }}
                                    </button>
                                </div>
                            </template>
                        </div>

                        <template x-if="!coupon">
                            <div x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95">
                                <form @submit.prevent="applyCoupon()" class="flex gap-2">
                                    <input type="text" x-model="couponCode" @input="couponError = ''"
                                           placeholder="{{ __('Coupon code') }}" autocomplete="off"
                                           class="flex-1 min-w-0 rounded-lg border border-gold-300/70 bg-cream/60 px-3 py-2.5 text-sm text-maroon-800 placeholder-maroon-300 uppercase tracking-wide focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition">
                                    <button type="submit" :disabled="applyingCoupon || !couponCode.trim()"
                                            class="shrink-0 rounded-lg border border-gold-500 text-gold-600 hover:bg-gold-500 hover:text-cream font-semibold text-sm px-4 py-2 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                            x-text='applyingCoupon ? @json(__('Applying…')) : @json(__('Apply'))'></button>
                                </form>
                                <p x-show="couponError" x-cloak x-transition class="text-xs text-red-600 mt-2" x-text="couponError"></p>
                            </div>
                        </template>
                        <template x-if="coupon">
                            <div x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="flex items-center justify-between gap-3 rounded-lg border border-gold-400/70 bg-gold-100/50 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-maroon-800 tracking-wide flex items-center gap-1.5">
                                        <span>🎉</span><span x-text="coupon.code"></span>
                                    </p>
                                    <p class="text-xs text-maroon-500 truncate" x-text="coupon.description"></p>
                                </div>
                                <button @click="removeCoupon()" aria-label="Remove coupon"
                                        class="shrink-0 text-xs font-semibold text-maroon-400 hover:text-maroon-700 underline underline-offset-2 transition">{{ __('Remove') }}</button>
                            </div>
                        </template>
                    </div>

                    {{-- free gift reward — visually distinct from the plain coupon block above it --}}
                    <div x-show="reward.configured && reward.available > 0" x-cloak class="mt-3">
                        <template x-if="!claimGift">
                            <button type="button" @click="claimGift = true"
                                    class="relative w-full text-left rounded-xl border-2 border-pink-400/70 bg-gradient-to-r from-pink-50 via-gold-50 to-pink-50 px-4 py-3 overflow-hidden group animate-gift-glow">
                                <div class="absolute inset-0 opacity-40 bg-gradient-to-r from-pink-200/0 via-white/70 to-pink-200/0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                                <div class="relative flex items-center gap-3">
                                    <span class="text-2xl shrink-0">🎁</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-maroon-800 flex items-center gap-1.5">
                                            {{ __('Free Gift Unlocked!') }}
                                            <span x-show="reward.available > 1" x-cloak class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded-full bg-pink-500 text-white" x-text="'×' + reward.available + ' {{ __('available') }}'"></span>
                                        </p>
                                        <p class="text-xs text-maroon-500 truncate">{{ __('Tap to add your free') }} <span x-text="reward.gift_label" class="font-semibold"></span> {{ __('to this order') }}</p>
                                    </div>
                                    <span class="shrink-0 text-xs font-bold text-pink-600 uppercase tracking-wide">{{ __('Claim') }} →</span>
                                </div>
                            </button>
                        </template>
                        <template x-if="claimGift">
                            <div class="flex items-center justify-between gap-3 rounded-xl border-2 border-pink-400/70 bg-gradient-to-r from-pink-100 via-gold-100 to-pink-100 px-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="text-2xl shrink-0">🎁</span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-maroon-800 flex items-center gap-1.5"><span>{{ __('Free Gift Applied') }}</span></p>
                                        <p class="text-xs text-maroon-500 truncate" x-text="reward.gift_label + ' — {{ __('FREE') }}'"></p>
                                    </div>
                                </div>
                                <button @click="claimGift = false" aria-label="Remove free gift"
                                        class="shrink-0 text-xs font-semibold text-maroon-400 hover:text-maroon-700 underline underline-offset-2 transition">{{ __('Remove') }}</button>
                            </div>
                        </template>
                    </div>

                    {{-- reward progress teaser — only when not yet eligible, to keep the loyalty program visible every checkout --}}
                    <div x-show="reward.configured && reward.available === 0 && reward.progress > 0" x-cloak class="mt-3 flex items-center gap-2 text-xs text-maroon-400">
                        <span>🍬</span>
                        <span><span x-text="reward.required - reward.progress"></span> {{ __('more orders until your next free gift') }}</span>
                    </div>

                    {{-- desktop-only CTA — on mobile this is replaced by the floating bottom bar
                         below, so a tap doesn't require scrolling all the way past the address form --}}
                    <div x-show="$store.shop.accepting && $store.shop.highDemandMode !== 'stop'" x-cloak class="hidden lg:block">
                        <p x-show="checkoutError" x-cloak x-transition class="text-xs text-red-600 mt-4" x-text="checkoutError"></p>

                        <button @click="checkout()"
                                :disabled="checkingOut || (showNewAddressForm && $store.shop.restricted && locationStatus === 'outside') || (!showNewAddressForm && selectedAddress && $store.shop.restricted && !selectedAddress.within_range)"
                                type="button"
                                class="btn-gold w-full text-center mt-4 inline-flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100"
                                :class="checkingOut && 'cursor-wait'">
                            <span x-text='checkingOut ? @json(__('Placing order…')) : (paymentMethod === "razorpay" ? @json(__('Pay Online & Place Order')) : @json(__('Place Order — Cash on Delivery')))'></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                        <p class="text-xs text-maroon-400 text-center mt-3" x-show="paymentMethod === 'cod'">{{ __("Pay in cash when your sweets arrive. We'll call you if we need directions.") }}</p>
                        <p class="text-xs text-maroon-400 text-center mt-3" x-show="paymentMethod === 'razorpay'" x-cloak>{{ __('You\'ll be redirected to a secure Razorpay checkout to complete payment.') }}</p>
                    </div>

                    {{-- mobile: COD/Razorpay notes still show inline here; the error message itself
                         moved to a floating toast above the fixed CTA bar (below) so it's visible
                         no matter how far down the page the user has scrolled --}}
                    <div x-show="$store.shop.accepting && $store.shop.highDemandMode !== 'stop'" x-cloak class="lg:hidden">
                        <p class="text-xs text-maroon-400 text-center mt-4" x-show="paymentMethod === 'cod'">{{ __("Pay in cash when your sweets arrive. We'll call you if we need directions.") }}</p>
                        <p class="text-xs text-maroon-400 text-center mt-4" x-show="paymentMethod === 'razorpay'" x-cloak>{{ __('You\'ll be redirected to a secure Razorpay checkout to complete payment.') }}</p>
                    </div>

                    <div x-show="!$store.shop.accepting" x-cloak class="mt-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3.5 text-center">
                        🚫 {{ __('We\'re not accepting online orders right now. Please check back soon.') }}
                    </div>
                    <div x-show="$store.shop.accepting && $store.shop.highDemandMode === 'stop'" x-cloak class="mt-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3.5 text-center">
                        <span x-text="$store.shop.highDemandStopMessage"></span>
                    </div>
                </div>
            </div>

            {{-- extra clearance (on top of the body's own floating-pill padding) so the bar
                 below never covers the order summary card --}}
            <div class="h-10 lg:hidden"></div>

            {{-- floating "Place Order" bar — mobile only, shares this same x-data scope (not a
                 separate component) so it always reflects the real selected address/payment
                 method/total. Mirrors the site's floating "View Cart" pill (same fixed/inset
                 positioning + safe-area handling) but sized as a primary checkout CTA showing
                 the live total; tapping anywhere on it places the order directly. --}}
            <div x-show="$store.shop.accepting && $store.shop.highDemandMode !== 'stop'" x-cloak
                 x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                 class="lg:hidden fixed inset-x-0 z-[55] px-4 pointer-events-none flex flex-col items-stretch gap-2"
                 style="bottom: calc(5rem + env(safe-area-inset-bottom));">
                {{-- floating error toast — sits right above the CTA regardless of scroll position,
                     so an error (e.g. "choose a delivery address") is never hidden below the fold.
                     Auto-dismisses after 5s; the shrinking bar makes that countdown visible. --}}
                <div x-show="checkoutError" x-cloak
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                     class="pointer-events-auto rounded-xl bg-red-600 text-cream shadow-2xl shadow-maroon-900/25 overflow-hidden">
                    <p class="text-sm font-medium text-center px-4 py-3" x-text="checkoutError"></p>
                    <div class="h-1 bg-black/20">
                        <div class="h-full bg-white" :style="`width: ${checkoutErrorProgress}%`"></div>
                    </div>
                </div>

                <button type="button" @click="checkout()"
                        :disabled="checkingOut || items.length === 0 || (showNewAddressForm && $store.shop.restricted && locationStatus === 'outside') || (!showNewAddressForm && selectedAddress && $store.shop.restricted && !selectedAddress.within_range)"
                        :class="checkingOut && 'cursor-wait'"
                        class="pointer-events-auto w-full flex items-center justify-between gap-3 pl-5 pr-2 py-2 rounded-2xl bg-gradient-to-r from-gold-400 to-gold-600 shadow-2xl shadow-maroon-900/25 active:scale-[0.98] transition-transform disabled:opacity-60 disabled:active:scale-100">
                    <span class="text-left leading-tight text-maroon-900">
                        <span class="block text-[11px] font-medium text-maroon-900/70">{{ __('Total') }}</span>
                        <span class="block font-display font-bold text-lg">₹<span x-text="total()"></span></span>
                    </span>
                    <span class="flex items-center gap-1.5 font-display font-semibold text-[13px] bg-maroon-800 text-cream rounded-xl pl-4 pr-3.5 py-3">
                        <span x-text='checkingOut ? @json(__('Placing…')) : (paymentMethod === "razorpay" ? @json(__('Pay & Place Order')) : @json(__('Place Order')))'></span>
                        <svg x-show="!checkingOut" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                        <svg x-show="checkingOut" x-cloak class="w-4 h-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </div>
</section>
@endsection
