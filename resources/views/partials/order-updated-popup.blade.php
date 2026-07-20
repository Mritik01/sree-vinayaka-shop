{{-- order-updated popup — opened via the open-order-updated window event, dispatched by
     orderTrackingPage().maybeShowOrderUpdated() the moment its poll (or initial load) sees
     admin-added item(s) it hasn't shown a popup for yet. Shown once per add-event
     (localStorage-gated by the order's items_added_at fingerprint — see maybeShowOrderUpdated
     in app.js); the persistent "🎉 Your order has been updated!" card in order-detail.blade.php
     stays visible regardless, so the update is never lost even after this popup is dismissed.
     Modeled directly on partials/note-decision-popup.blade.php's glassmorphism shell — z-[150],
     same backdrop/card treatment, same enter/leave transitions — but lists the added items. --}}
<div x-data="orderUpdatedPopup()" x-show="open" x-cloak
     class="fixed inset-0 z-[150] flex items-center justify-center p-4"
     @keydown.escape.window="close()">
    <div x-show="open"
         class="absolute inset-0 bg-maroon-900/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="close()"></div>

    <div x-show="open"
         class="relative w-full max-w-sm bg-white/90 backdrop-blur-xl border border-white/40 shadow-2xl rounded-3xl overflow-hidden"
         x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

        <button type="button" @click="close()" aria-label="{{ __('Close') }}"
                class="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-white/70 hover:bg-white text-maroon-600 flex items-center justify-center shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="px-7 pt-10 pb-7 text-center">
            <p class="text-5xl animate-track-pop">🎉</p>
            <p class="font-display font-bold text-lg text-maroon-800 mt-3">{{ __('Your order has been updated!') }}</p>
            <p class="text-sm text-maroon-600 mt-1.5 max-w-xs mx-auto leading-relaxed">{{ __("At your request, we've added the following item(s) to your order.") }}</p>

            <div class="mt-5 space-y-2 text-left max-h-52 overflow-y-auto">
                <template x-for="item in items" :key="item.id">
                    <div class="flex items-center gap-3 bg-pista-50 rounded-xl px-3 py-2.5">
                        <div class="w-10 h-10 shrink-0 rounded-lg overflow-hidden bg-white border border-pista-200/60">
                            <template x-if="item.image_url">
                                <img :src="item.image_url" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!item.image_url">
                                <div class="w-full h-full grid place-items-center text-sm">🍬</div>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-maroon-800 truncate">
                                <span x-text="item.name"></span>
                                <template x-if="item.portion_label">
                                    <span class="text-maroon-400" x-text="'(' + item.portion_label + ')'"></span>
                                </template>
                            </p>
                            <p class="text-xs text-maroon-500" x-text="'× ' + item.quantity"></p>
                        </div>
                        <span class="text-sm font-semibold text-maroon-800 shrink-0" x-text="'₹' + item.line_total.toLocaleString('en-IN')"></span>
                    </div>
                </template>
            </div>

            <p class="text-xs text-pista-700 bg-pista-50 rounded-lg px-3 py-2 mt-4">
                ✅ {{ __('Your invoice and order total have been updated accordingly.') }}
            </p>

            <button type="button" @click="close()" class="btn-gold mt-6 px-8 py-2.5 text-sm">{{ __('Got it') }}</button>
        </div>
    </div>
</div>
