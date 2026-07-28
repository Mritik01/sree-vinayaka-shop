<footer id="contact" class="bg-maroon-900 text-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-10 pb-14">
        <div class="flex items-center gap-3 pb-8 border-b border-gold-300/20">
            <img src="{{ $businessLogo }}" alt="Shri Vinayak Family Shop" class="h-14 w-14 shrink-0 rounded-full object-cover bg-white">
            <span class="font-display text-2xl font-bold">Shri Vinayak <span class="text-gold-400">Family Shop</span></span>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-10 pt-10">
            <div>
                <h3 class="font-semibold text-gold-400 mb-4 tracking-wide">{{ __('Our Range') }}</h3>
                <ul class="space-y-2.5 text-sm text-cream/75">
                    <li><a href="#bestsellers" class="hover:text-gold-300 transition">{{ __('Grocery') }}</a></li>
                    <li><a href="#bestsellers" class="hover:text-gold-300 transition">{{ __('Fruits & Vegetables') }}</a></li>
                    <li><a href="#bestsellers" class="hover:text-gold-300 transition">{{ __('Dairy & Beverages') }}</a></li>
                    <li><a href="#range" class="hover:text-gold-300 transition">{{ __('Household & Personal Care') }}</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-gold-400 mb-4 tracking-wide">{{ __('Quick Links') }}</h3>
                <ul class="space-y-2.5 text-sm text-cream/75">
                    <li><a href="#home" class="hover:text-gold-300 transition">{{ __('Home') }}</a></li>
                    <li><a href="#about" class="hover:text-gold-300 transition">{{ __('Our Range') }}</a></li>
                    <li><a href="#bestsellers" class="hover:text-gold-300 transition">{{ __('Favourites') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-gold-300 transition">{{ __('Contact') }}</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-gold-400 mb-4 tracking-wide">{{ __('Reach Us') }}</h3>
                <ul class="space-y-2.5 text-sm text-cream/75">
                    <li>📍 {{ __('Roadways Bus Stand, Nichlaul Road, Siswa Bazar, Maharajganj, UP 273163') }}</li>
                    @if ($businessPhone)
                        <li>📞 <a href="{{ $businessPhone['tel'] }}" class="hover:text-gold-300 transition">{{ $businessPhone['display'] }}</a></li>
                        <li>💬 <a href="{{ $businessPhone['whatsapp'] }}" target="_blank" rel="noopener" class="hover:text-gold-300 transition">{{ __('Chat with us on WhatsApp') }}</a></li>
                    @endif
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-gold-400 mb-4 tracking-wide">{{ __('Shop Hours') }}</h3>
                <p class="text-sm text-cream/75">{{ __('Open Daily') }}</p>
                <p class="text-sm text-cream font-medium mt-1">8:00 AM – 9:00 PM</p>
                <p class="text-xs text-cream/50 mt-4 italic">{{ __('Fresh stock arrives every morning — shop early for the best pick!') }}</p>
            </div>
        </div>

        <div class="flex justify-center pt-10">
            <a href="https://elevvotech.com/" target="_blank" rel="noopener"
               class="group inline-flex items-center gap-2.5 rounded-full border border-gold-400/30 bg-gradient-to-r from-gold-500/10 via-gold-400/15 to-gold-500/10 px-5 py-2.5 text-xs sm:text-sm text-gold-200 shadow-sm transition-all hover:border-gold-400/60 hover:from-gold-500/20 hover:via-gold-400/25 hover:to-gold-500/20">
                <span class="text-base transition-transform duration-300 group-hover:scale-110 group-hover:rotate-12">🚀</span>
                <span>{{ __('Transform your business with') }} <span class="font-bold text-gold-300 transition-colors group-hover:text-white">Elevvotech</span></span>
                <span aria-hidden="true" class="transition-transform duration-300 group-hover:translate-x-0.5">→</span>
            </a>
        </div>
    </div>

    {{-- lg:pb-28 reserves clearance for the floating desktop View Cart bar (fixed, bottom-5) —
         added here rather than as page-level body padding so the reserved space extends this
         footer's own dark background instead of leaving a bare gap of page background below it --}}
    <div class="border-t border-gold-300/20 pt-6 pb-6 lg:pb-28 px-4 text-center text-xs text-cream/50 flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
        <span class="flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('legal.terms') }}" class="hover:text-gold-300 underline underline-offset-2 transition">{{ __('Terms & Conditions') }}</a>
            <a href="{{ route('legal.privacy') }}" class="hover:text-gold-300 underline underline-offset-2 transition">{{ __('Privacy Policy') }}</a>
            <a href="{{ route('legal.refund') }}" class="hover:text-gold-300 underline underline-offset-2 transition">{{ __('Refund & Cancellation Policy') }}</a>
            <a href="{{ route('legal.shipping') }}" class="hover:text-gold-300 underline underline-offset-2 transition">{{ __('Shipping & Delivery Policy') }}</a>
        </span>
    </div>
</footer>
