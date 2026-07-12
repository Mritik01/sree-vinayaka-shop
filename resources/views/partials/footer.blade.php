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
                    <li><a href="#about" class="hover:text-gold-300 transition">{{ __('Our Story') }}</a></li>
                    <li><a href="#bestsellers" class="hover:text-gold-300 transition">{{ __('Favourites') }}</a></li>
                    <li><a href="#contact" class="hover:text-gold-300 transition">{{ __('Contact') }}</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-gold-400 mb-4 tracking-wide">{{ __('Reach Us') }}</h3>
                <ul class="space-y-2.5 text-sm text-cream/75">
                    <li>📍 {{ __('Main Market Road, Thuthibari') }}</li>
                    <li>📞 <a href="tel:+918920937331" class="hover:text-gold-300 transition">+91 89209 37331</a></li>
                    <li>💬 <a href="https://wa.me/918920937331" target="_blank" rel="noopener" class="hover:text-gold-300 transition">{{ __('Chat with us on WhatsApp') }}</a></li>
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

    <div class="border-t border-gold-300/20 py-6 text-center text-xs text-cream/50">
        &copy; {{ date('Y') }} Makhanbhog Sweets, Thuthibari. {{ __('All rights reserved.') }}
    </div>
</footer>
