<section class="bg-white pb-20 pt-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="relative rounded-2xl bg-cream border-2 border-gold-300/60 px-8 sm:px-16 py-14 grid grid-cols-1 md:grid-cols-2 gap-10 items-center overflow-hidden">
            <div class="relative z-10 text-center md:text-left">
                <p class="text-gold-600 font-semibold tracking-widest uppercase text-sm mb-3">{{ __('Stay in Touch') }}</p>
                <h2 class="font-display text-3xl sm:text-5xl font-bold text-maroon-900 leading-tight">{{ __('Join the Makhanbhog Parivaar') }}</h2>
                <p class="text-maroon-500 mt-4">{{ __('Be the first to hear about festive specials, new sweets, and seasonal treats.') }}</p>

                <form x-data="{ sent: false }" @submit.prevent="sent = true" class="mt-8 flex flex-col sm:flex-row gap-3 max-w-md mx-auto md:mx-0">
                    <input type="email" required placeholder="{{ __('Enter your email') }}"
                        class="flex-1 rounded-xl bg-white border border-gold-300/60 text-maroon-900 placeholder-maroon-400/50 px-5 py-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-gold-400">
                    <button type="submit" x-show="!sent" class="btn-maroon">{{ __('Sign Up') }}</button>
                    <span x-show="sent" x-cloak class="flex items-center justify-center font-semibold text-pista-500 px-4">{{ __('Welcome to the family!') }} 🙏</span>
                </form>
            </div>

            {{-- TODO: replace with a real product photo once available --}}
            <div class="relative z-10 flex justify-center md:justify-end">
                <div class="mithai-frame ring-offset-cream w-44 h-44 sm:w-60 sm:h-60 flex items-center justify-center -rotate-2"
                     style="background: linear-gradient(150deg, #7a1622, #c8962e);">
                    <span class="text-8xl drop-shadow-xl">🍯</span>
                </div>
            </div>
        </div>
    </div>
</section>
