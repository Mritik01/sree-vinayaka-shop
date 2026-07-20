@php
    // same sparkle flight-path array used by rider-rating-popup.blade.php and the product page's
    // own review form — duplicated here rather than extracted since it's a tiny, fully static
    // 8-element array with nothing else to share
    $sparkles = [
        ['tx' => '-78px', 'ty' => '-50px', 'emoji' => '✨', 'delay' => '0s'],
        ['tx' => '-34px', 'ty' => '-66px', 'emoji' => '🌟', 'delay' => '.05s'],
        ['tx' => '14px',  'ty' => '-72px', 'emoji' => '✨', 'delay' => '.1s'],
        ['tx' => '62px',  'ty' => '-56px', 'emoji' => '💛', 'delay' => '.04s'],
        ['tx' => '98px',  'ty' => '-22px', 'emoji' => '✨', 'delay' => '.12s'],
        ['tx' => '-100px','ty' => '-8px',  'emoji' => '🎉', 'delay' => '.08s'],
        ['tx' => '-58px', 'ty' => '32px',  'emoji' => '✨', 'delay' => '.15s'],
        ['tx' => '48px',  'ty' => '36px',  'emoji' => '🌟', 'delay' => '.1s'],
    ];
@endphp

{{-- product rating popup — opened via the open-product-rating window event, dispatched by
     orderTrackingPage().openProductRatingPopup() when the customer taps "⭐ Rate Products" /
     "View Your Reviews" in the delivered-status hero. Unlike rider-rating-popup, this never
     auto-opens — click-triggered only, so it doesn't stack a second interruption on top of the
     rider-rating popup's own auto-prompt at the same delivery moment.
     Each product rates independently and instantly (tap a star, done) via the SAME endpoint the
     product detail page's own review form already posts to (/product/{slug}/reviews) — no new
     backend, ratings are product-scoped (not order-scoped), matching that existing system. --}}
<div x-data="productRatingPopup()" x-show="open" x-cloak
     class="fixed inset-0 z-[150] flex items-center justify-center p-4"
     @keydown.escape.window="close()">
    <div x-show="open"
         class="absolute inset-0 bg-maroon-900/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="close()"></div>

    <div x-show="open"
         class="relative w-full max-w-lg bg-white/90 backdrop-blur-xl border border-white/40 shadow-2xl rounded-3xl overflow-hidden flex flex-col max-h-[85vh]"
         x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

        <button type="button" @click="close()" aria-label="{{ __('Close') }}"
                class="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-white/70 hover:bg-white text-maroon-600 flex items-center justify-center shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="px-7 pt-9 pb-2 text-center shrink-0">
            <p class="text-4xl">🍬</p>
            <p class="font-display font-bold text-xl text-maroon-800 mt-3">{{ __('Rate Your Sweets') }}</p>
            <p class="text-xs text-maroon-400 mt-1.5">{{ __('Your feedback helps other customers choose well.') }}</p>
        </div>

        <div class="px-7 overflow-y-auto divide-y divide-gold-200/50">
            <template x-for="product in products" :key="product.productId">
                <div class="py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 shrink-0 rounded-lg overflow-hidden bg-cream border border-gold-200/60">
                            <template x-if="product.imageUrl">
                                <img :src="product.imageUrl" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!product.imageUrl">
                                <div class="w-full h-full grid place-items-center text-base">🍬</div>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-maroon-800 truncate" x-text="product.name"></p>
                            <p x-show="product.portionLabel" class="text-xs text-maroon-400" x-text="product.portionLabel"></p>
                        </div>
                        <span x-show="product.justSaved" x-cloak class="text-xs font-semibold text-pista-600 shrink-0">{{ __('Saved') }} ✓</span>
                    </div>

                    <div class="relative w-fit flex gap-1 mt-2.5">
                        <template x-for="i in 5" :key="i">
                            <button type="button" @click="setRating(product, i)"
                                    @mouseenter="product.hoverRating = i" @mouseleave="product.hoverRating = 0"
                                    :aria-label="'Rate ' + i + ' out of 5'"
                                    class="transition transform duration-150"
                                    :class="[(product.hoverRating || product.rating) >= i ? 'scale-110' : '', product.justRatedIndex === i ? 'animate-star-select' : '']">
                                <svg class="w-7 h-7 transition-colors duration-150"
                                     :class="(product.hoverRating || product.rating) >= i ? 'text-gold-500' : 'text-gold-300'"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                                </svg>
                            </button>
                        </template>

                        <template x-if="product.showBurst">
                            <div class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                @foreach ($sparkles as $sparkle)
                                    <span class="absolute text-lg animate-sparkle-burst"
                                          style="--tx: {{ $sparkle['tx'] }}; --ty: {{ $sparkle['ty'] }}; animation-delay: {{ $sparkle['delay'] }};">{{ $sparkle['emoji'] }}</span>
                                @endforeach
                            </div>
                        </template>
                    </div>

                    <textarea x-show="product.rating > 0" x-cloak x-model="product.comment" rows="2" maxlength="1000"
                              placeholder="{{ __('Add a short review (optional)…') }}"
                              class="w-full mt-2.5 rounded-xl bg-white/80 border border-gold-300/60 px-3.5 py-2.5 text-sm text-maroon-700 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 resize-none"></textarea>
                    <button type="button" x-show="product.rating > 0 && product.comment !== product.savedComment" x-cloak
                            @click="saveComment(product)" :disabled="product.savingComment"
                            class="text-xs font-semibold text-maroon-500 hover:text-maroon-700 mt-1.5 disabled:opacity-50">
                        <span x-show="!product.savingComment">{{ __('Save note') }}</span>
                        <span x-show="product.savingComment" x-cloak>{{ __('Saving…') }}</span>
                    </button>
                    <p x-show="product.error" x-cloak x-text="product.error" class="text-xs text-red-600 font-medium mt-1.5"></p>
                </div>
            </template>
        </div>

        <div class="px-7 py-5 flex items-center justify-between border-t border-gold-200/50 shrink-0">
            <p class="text-xs text-maroon-400" x-text="ratedCount() + ' {{ __('of') }} ' + products.length + ' {{ __('rated') }}'"></p>
            <button type="button" @click="close()" class="btn-gold text-sm px-6 py-2">{{ __('Done') }}</button>
        </div>
    </div>
</div>
