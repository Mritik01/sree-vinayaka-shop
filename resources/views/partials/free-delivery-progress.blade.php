{{-- Shared by cart.blade.php and checkout.blade.php — both expose the same Alpine surface:
     subtotal(), crossedFreeDeliveryThreshold, and the global $store.shop fee helpers. Only
     renders anything when the admin has chosen the "Free Above Minimum" delivery strategy. --}}
<template x-if="$store.shop.deliveryFeeStrategy === 'free_above_minimum' && $store.shop.deliveryFreeMinOrder > 0">
    <div class="mt-3">
        {{-- still below the threshold: progress bar + "add ₹X more" --}}
        <div x-show="$store.shop.amountToFreeDelivery(subtotal()) > 0" x-cloak>
            <p class="text-xs sm:text-sm text-maroon-600 font-medium">
                {{ __('Add') }} ₹<span x-text="$store.shop.amountToFreeDelivery(subtotal())"></span>
                {{ __('more to unlock FREE Delivery') }} 🚚
            </p>
            <div class="h-2.5 rounded-full bg-gold-100 overflow-hidden mt-1.5">
                <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-pista-500 transition-all duration-500 ease-out"
                     :style="`width: ${Math.min(100, (subtotal() / $store.shop.deliveryFreeMinOrder) * 100)}%`"></div>
            </div>
        </div>

        {{-- already there: quiet persistent confirmation (the big celebration below is a one-time event) --}}
        <div x-show="$store.shop.amountToFreeDelivery(subtotal()) === 0" x-cloak
             class="flex items-center gap-1.5 text-xs sm:text-sm text-pista-700 font-semibold">
            <span>✅</span><span>{{ __('FREE Delivery unlocked') }} 🚚</span>
        </div>
    </div>
</template>

{{-- one-time celebration, fired by celebrateFreeDelivery() in app.js the moment the threshold
     is actually crossed — confetti (unless the admin picked the "minimal" style) is triggered
     separately in JS; this is just the visual banner + sliding-truck touch --}}
<div x-data="{ show: false }" @free-delivery-unlocked.window="show = true; setTimeout(() => show = false, 4200)"
     x-show="show" x-cloak
     x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 -translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
     class="relative overflow-hidden mt-3 rounded-xl bg-gradient-to-r from-pista-500 to-pista-600 text-white px-4 py-3 shadow-md">
    <p class="font-display font-semibold text-sm sm:text-base flex items-center gap-1.5">
        <span>🎉</span><span x-text="$store.shop.deliverySuccessMessage"></span>
    </p>
    <template x-if="$store.shop.deliverySuccessAnimation !== 'minimal'">
        <span class="truck-slide absolute text-xl sm:text-2xl" aria-hidden="true">🚚</span>
    </template>
</div>

<style>
    @keyframes truck-slide-across {
        0% { left: -10%; opacity: 0; }
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { left: 105%; opacity: 0; }
    }
    .truck-slide {
        bottom: 6px;
        animation: truck-slide-across 2.6s ease-in-out;
    }
    @media (prefers-reduced-motion: reduce) {
        .truck-slide { animation: none; display: none; }
    }
</style>
