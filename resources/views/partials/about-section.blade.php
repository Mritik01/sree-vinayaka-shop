{{-- "Our Story" (trust badges) replaced with a promotional "Shop By Range" banner grid — one
     large hero banner plus three smaller ones, each real photography + a working link into
     /products?category=slug. Distinct in purpose from partials/category-shop.blade.php (a
     functional tabbed product grid) and partials/promo-banner.blade.php (custom gift/wedding
     orders) — this is pure "here's what we make, go explore" inspiration, quick-commerce style.
     Reuses the exact banner/scrim/reveal treatment already established in hero-slider.blade.php
     for visual consistency rather than inventing a new pattern. --}}
<section id="about" class="py-16 sm:py-20 bg-white"
         x-data="{ shown: false }"
         x-init="const io = new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { shown = true; io.disconnect(); } }, { threshold: 0.1 }); io.observe($el)">
    <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12">
        <p class="text-center text-gold-600 font-semibold tracking-widest uppercase text-sm mb-3">{{ __('Shop By Range') }}</p>
        <h2 class="section-heading">{{ __('Everything We Make, Freshly Every Day') }}</h2>
        <p class="text-center text-maroon-500 mt-4 mb-10 sm:mb-14 max-w-2xl mx-auto">
            {{ __('From classic mithai to crunchy namkeen — explore what comes out of our kitchen each morning.') }}
        </p>

        <div class="space-y-4 sm:space-y-5">
            {{-- large banner --}}
            <a href="{{ route('products.index', ['category' => 'sweets']) }}"
               class="group relative block rounded-2xl sm:rounded-3xl overflow-hidden h-[220px] sm:h-[280px] shadow-md transition-all duration-700 ease-out"
               :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                <img src="{{ asset('images/ranges/all-mithai.jpg') }}" alt="{{ __('Mithai range') }}" loading="lazy"
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-r from-maroon-900/90 via-maroon-900/55 to-maroon-900/10"></div>

                <div class="relative z-10 h-full flex items-center px-6 sm:px-10 lg:px-14">
                    <div class="max-w-sm">
                        <p class="text-gold-300 font-semibold tracking-widest uppercase text-[10px] sm:text-xs mb-2 sm:mb-3">{{ __('Our Signature') }}</p>
                        <h3 class="font-display text-2xl sm:text-4xl font-bold text-cream leading-tight drop-shadow-md mb-2 sm:mb-3">{{ __('Handcrafted Mithai, Made Fresh Daily') }}</h3>
                        <p class="text-xs sm:text-base text-gold-100/90 mb-4 sm:mb-6">{{ __('100% desi ghee, traditional recipes, zero shortcuts.') }}</p>
                        <span class="inline-flex items-center gap-1.5 bg-white text-maroon-800 font-semibold text-sm px-5 py-2.5 rounded-xl shadow group-hover:shadow-lg group-hover:gap-2.5 transition-all">
                            {{ __('Shop Mithai') }} <span aria-hidden="true">→</span>
                        </span>
                    </div>
                </div>
            </a>

            {{-- three smaller banners --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                @foreach ([
                    ['image' => 'namkeen.jpg', 'title' => __('Crispy Namkeen'), 'text' => __('Snacks worth snacking on'), 'category' => 'namkeen'],
                    ['image' => 'cookies.jpg', 'title' => __('Cookies & Bakes'), 'text' => __('Buttery, always fresh'), 'category' => 'cookies'],
                    ['image' => 'desi-ghee.jpg', 'title' => __('Pure Desi Ghee Sweets'), 'text' => __('No shortcuts, ever'), 'category' => 'desi-ghee'],
                ] as $i => $range)
                    <a href="{{ route('products.index', ['category' => $range['category']]) }}"
                       class="group relative block rounded-2xl overflow-hidden h-[170px] sm:h-[190px] shadow-sm transition-all duration-700 ease-out"
                       :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                       style="transition-delay: {{ 150 + $i * 120 }}ms">
                        <img src="{{ asset('images/ranges/' . $range['image']) }}" alt="{{ $range['title'] }}" loading="lazy"
                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-maroon-900/90 via-maroon-900/30 to-transparent"></div>

                        <div class="relative z-10 h-full flex flex-col justify-end p-4 sm:p-5">
                            <h3 class="font-display text-lg sm:text-xl font-bold text-cream leading-tight drop-shadow-md">{{ $range['title'] }}</h3>
                            <p class="text-xs text-gold-100/90 mt-0.5 mb-3">{{ $range['text'] }}</p>
                            <span class="inline-flex items-center gap-1.5 self-start bg-white text-maroon-800 font-semibold text-xs px-4 py-2 rounded-lg shadow group-hover:shadow-lg group-hover:gap-2 transition-all">
                                {{ __('Shop Now') }} <span aria-hidden="true">→</span>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
