@php
    // same sparkle flight-path array used by the product review star-picker's 5★ celebration
    // burst (product-show.blade.php) — duplicated here rather than extracted since it's a tiny,
    // fully static 8-element array with nothing else to share
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

{{-- rider rating popup — opened via the open-rider-rating window event, dispatched by
     orderTrackingPage() the moment its poll (or initial load) sees a delivered, unrated order.
     Shown once per order (see startSorryCollapse()-style localStorage gating in app.js); after
     that, only the small reminder pill in the delivered-status hero re-opens it on demand.
     z-[150] — deliberately above every other overlay in the app (highest existing is z-[145] on
     the legal-document-modal), since this is meant to be the single most prominent moment. --}}
<div x-data="riderRatingPopup()" x-show="open" x-cloak
     class="fixed inset-0 z-[150] flex items-center justify-center p-4"
     @keydown.escape.window="close()">
    <div x-show="open"
         class="absolute inset-0 bg-maroon-900/80 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="close()"></div>

    {{-- true glass-on-glass card, not just a blurred backdrop behind an opaque box --}}
    <div x-show="open"
         class="relative w-full max-w-md bg-white/90 backdrop-blur-xl border border-white/40 shadow-2xl rounded-3xl overflow-hidden"
         x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

        <button type="button" @click="close()" aria-label="{{ __('Close') }}"
                class="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-white/70 hover:bg-white text-maroon-600 flex items-center justify-center shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- ── step 1: rate ─────────────────────────────────────────── --}}
        <div x-show="step === 'rate'" class="px-7 pt-9 pb-7 text-center">
            <p class="text-4xl">🎉</p>
            <p class="font-display font-bold text-xl text-maroon-800 mt-3">{{ __('Your order has been delivered!') }}</p>

            <div class="flex flex-col items-center mt-5">
                <div class="relative shrink-0">
                    <img x-show="riderPhoto" :src="riderPhoto" class="w-16 h-16 rounded-full object-cover border-2 border-gold-400 shadow">
                    <span x-show="!riderPhoto" class="w-16 h-16 rounded-full bg-gold-200 border-2 border-gold-400 flex items-center justify-center font-display font-bold text-xl text-gold-700"
                          x-text="riderName ? riderName.charAt(0).toUpperCase() : '🛵'"></span>
                </div>
                <p class="text-sm text-maroon-700 mt-3 leading-relaxed max-w-xs">
                    <span x-text="@js(__('How was your delivery experience with')) + ' ' + riderName + '?'"></span>
                </p>
                <p class="text-xs text-maroon-400 mt-1.5 max-w-xs">{{ __('Your feedback helps us recognize great delivery partners and improve our service.') }}</p>
            </div>

            {{-- large interactive 5-star picker — same proven pattern as the product review
                 form's star input (product-show.blade.php), just sized up for a full-screen moment --}}
            <div class="relative w-fit flex gap-1.5 mt-6 mx-auto">
                <template x-for="i in 5" :key="i">
                    <button type="button" @click="setRating(i)" @mouseenter="hoverRating = i" @mouseleave="hoverRating = 0"
                            :aria-label="'Rate ' + i + ' out of 5'"
                            class="transition transform duration-150" :class="[(hoverRating || rating) >= i ? 'scale-110' : '', justRatedIndex === i ? 'animate-star-select' : '']">
                        <svg class="w-11 h-11 transition-colors duration-150" :class="(hoverRating || rating) >= i ? 'text-gold-500' : 'text-gold-300'" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                        </svg>
                    </button>
                </template>

                <template x-if="showBurst">
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
                        @foreach ($sparkles as $sparkle)
                            <span class="absolute text-lg animate-sparkle-burst"
                                  style="--tx: {{ $sparkle['tx'] }}; --ty: {{ $sparkle['ty'] }}; animation-delay: {{ $sparkle['delay'] }};">{{ $sparkle['emoji'] }}</span>
                        @endforeach
                    </div>
                </template>
            </div>

            <textarea x-show="rating > 0" x-cloak x-model="comment" rows="2" maxlength="1000" placeholder="{{ __('Add a short review (optional)…') }}"
                      class="w-full mt-5 rounded-xl bg-white/80 border border-gold-300/60 px-4 py-3 text-sm text-maroon-700 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400 resize-none"></textarea>

            <p x-show="error" x-cloak x-text="error" class="text-xs text-red-600 font-medium mt-3"></p>

            <div class="flex items-center gap-3 mt-6">
                <button type="button" @click="submit()" :disabled="!rating || submitting"
                        class="flex-1 bg-maroon-700 hover:bg-maroon-800 text-cream font-semibold rounded-xl py-3 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!submitting">{{ __('Submit Rating') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('Submitting…') }}</span>
                </button>
                <button type="button" @click="skip()" class="text-sm font-semibold text-maroon-400 hover:text-maroon-600 transition px-2">
                    {{ __('Skip') }}
                </button>
            </div>
        </div>

        {{-- ── step 2: result ───────────────────────────────────────── --}}
        <div x-show="step === 'result'" x-cloak class="px-7 py-10 text-center">
            <template x-if="rating >= 3">
                <div>
                    <p class="text-5xl animate-track-pop">🎉</p>
                    <p class="font-display font-bold text-lg text-maroon-800 mt-3">{{ __('Thank you!') }}</p>
                    <p class="text-sm text-maroon-600 mt-1.5 max-w-xs mx-auto leading-relaxed">
                        {{ __("Your appreciation will make our delivery partner's day.") }} ❤️
                    </p>
                </div>
            </template>
            <template x-if="rating > 0 && rating < 3">
                <div>
                    <p class="text-5xl animate-droop">😔</p>
                    <p class="font-display font-bold text-lg text-maroon-800 mt-3">{{ __("We're sorry") }}</p>
                    <p class="text-sm text-maroon-600 mt-1.5 max-w-xs mx-auto leading-relaxed">
                        {{ __("We're sorry your experience wasn't perfect. We'll use your feedback to improve our service.") }}
                    </p>
                </div>
            </template>
            <button type="button" @click="close()" class="btn-gold mt-6 px-8 py-2.5 text-sm">{{ __('Done') }}</button>
        </div>
    </div>
</div>
