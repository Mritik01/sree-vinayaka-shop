<footer id="contact" class="bg-maroon-900 text-cream">
    @include('partials.trim', ['fill' => '#ffffff', 'flip' => true])

    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-10 pb-14">
        <div class="flex items-center gap-3 pb-8 border-b border-gold-300/20">
            <img src="{{ asset('images/logo-circle.png') }}" alt="Makhanbhog Sweets" class="h-14 w-14 shrink-0">
            <span class="font-display text-2xl font-bold">Makhanbhog <span class="text-gold-400">Sweets</span></span>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-10 pt-10">
            <div>
                <h3 class="font-semibold text-gold-400 mb-4 tracking-wide">{{ __('Our Range') }}</h3>
                <ul class="space-y-2.5 text-sm text-cream/75">
                    <li><a href="#bestsellers" class="hover:text-gold-300 transition">{{ __('Mithai') }}</a></li>
                    <li><a href="#bestsellers" class="hover:text-gold-300 transition">{{ __('Namkeen') }}</a></li>
                    <li><a href="#bestsellers" class="hover:text-gold-300 transition">{{ __('Ladoo') }}</a></li>
                    <li><a href="#range" class="hover:text-gold-300 transition">{{ __('Gift Boxes') }}</a></li>
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
                    <li>📍 {{ __('Main Market Road, Thuthibari') }}</li>
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
                <p class="text-xs text-cream/50 mt-4 italic">{{ __('Sweets made fresh every morning — come early for the best pick!') }}</p>
            </div>
        </div>
    </div>

    {{-- lg:pb-28 reserves clearance for the floating desktop View Cart bar (fixed, bottom-5) —
         added here rather than as page-level body padding so the reserved space extends this
         footer's own dark background instead of leaving a bare gap of page background below it --}}
    <div class="border-t border-gold-300/20 pt-6 pb-6 lg:pb-28 px-4 text-center text-xs text-cream/50 flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
        <span>&copy; {{ date('Y') }} Makhanbhog Sweets, Thuthibari. {{ __('All rights reserved.') }}</span>
        <span class="flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('legal.terms') }}" class="hover:text-gold-300 underline underline-offset-2 transition">{{ __('Terms & Conditions') }}</a>
            <a href="{{ route('legal.privacy') }}" class="hover:text-gold-300 underline underline-offset-2 transition">{{ __('Privacy Policy') }}</a>
            <a href="{{ route('legal.refund') }}" class="hover:text-gold-300 underline underline-offset-2 transition">{{ __('Refund & Cancellation Policy') }}</a>
            <a href="{{ route('legal.shipping') }}" class="hover:text-gold-300 underline underline-offset-2 transition">{{ __('Shipping & Delivery Policy') }}</a>
        </span>
    </div>
</footer>
