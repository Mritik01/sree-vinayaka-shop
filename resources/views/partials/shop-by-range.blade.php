{{-- Shop By Range — clicking a card filters the bestsellers carousel above (see #bestsellers / productSlider() in app.js) via a `filter-category` window event.
     Cards are simple gold-bordered rounded rectangles with the category photo filling the frame and a
     white/cream nameplate overlapping the TOP edge (double-bordered, like a mithai box label) — modelled
     on a reference screenshot of a similar sweets brand's "Shop By Range" section.

     Category photos in public/images/ranges/ are from Wikimedia Commons (CC/PD licenses):
     all-mithai.jpg "Display of Indian Sweets Mithai in Kolkata, West Bengal", namkeen.jpg "Shop selling Bikaneri
     bhujia in Jaipur", desi-ghee.jpg "Pure Ghee-Homemade-Maharashtra", cookies.jpg "Nankhatai", mathi.jpg
     "Spicy Mathri", syrup.jpg "Jalebi 1". --}}
@php
    $ranges = [
        'all' => ['name' => __('All'), 'photo' => 'images/ranges/all-mithai.jpg'],
        'Sweets' => ['name' => __('Sweets'), 'photo' => 'images/products/rabri.jpg'],
        'Namkeen' => ['name' => __('Namkeen'), 'photo' => 'images/ranges/namkeen.jpg'],
        'Desi Ghee' => ['name' => __('Desi Ghee'), 'photo' => 'images/ranges/desi-ghee.jpg'],
        'Cookies' => ['name' => __('Cookies'), 'photo' => 'images/ranges/cookies.jpg'],
        'Mathi' => ['name' => __('Mathi'), 'photo' => 'images/ranges/mathi.jpg'],
        'Dry Fruits' => ['name' => __('Dry Fruits'), 'photo' => 'images/products/dryfruit-roll.jpg'],
        'Syrup' => ['name' => __('Syrup'), 'photo' => 'images/ranges/syrup.jpg'],
    ];
@endphp

<section id="range" class="relative bg-white pb-24 pt-6"
         x-data="{ active: 'all', shown: false }"
         x-init="const io = new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { shown = true; io.disconnect(); } }, { threshold: 0.12 }); io.observe($el)"
         @filter-category.window="active = $event.detail">
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6">
        <h2 class="section-heading">{{ __('Shop By Range') }}</h2>
        <p class="text-center text-maroon-500 mt-3 mb-10 sm:mb-14 max-w-xl mx-auto">{{ __("Each piece reflects quality and authentic flavours — pick a range to filter Thuthibari's Favourites above.") }}</p>

        {{-- mobile swipe hint --}}
        <p class="sm:hidden text-center text-[11px] uppercase tracking-[0.2em] text-gold-600/80 mb-6 animate-pulse">← {{ __('swipe to explore') }} →</p>

        {{-- phones get an app-like snap carousel; sm+ gets the 4-column grid --}}
        <div class="flex overflow-x-auto snap-x snap-mandatory no-scrollbar gap-4 -mx-4 px-6 pb-4
                    sm:grid sm:grid-cols-3 lg:grid-cols-4 sm:gap-5 sm:overflow-visible sm:mx-0 sm:px-0 sm:pb-0">
            @foreach ($ranges as $key => $range)
                <a href="#bestsellers"
                   @click.prevent="window.dispatchEvent(new CustomEvent('filter-category', { detail: '{{ $key }}' }))"
                   class="group relative block shrink-0 snap-center w-44 sm:w-auto sm:shrink
                          transition-all duration-700 ease-out"
                   :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
                   style="transition-delay: {{ $loop->index * 80 }}ms">

                    {{-- card: photo frame with a gold border --}}
                    <div class="relative w-full h-40 sm:h-48 rounded-2xl overflow-hidden border-2 shadow-sm transition duration-300 ease-out group-hover:-translate-y-1.5 group-hover:shadow-xl group-active:scale-95"
                         :class="active === '{{ $key }}' ? 'border-maroon-700 ring-2 ring-maroon-300/60' : 'border-gold-400'">

                        {{-- photo, zooms slowly on hover --}}
                        <img src="{{ asset($range['photo']) }}" alt="{{ $range['name'] }}" loading="lazy"
                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-110">

                        {{-- soft top shade so the nameplate always reads clearly over any photo --}}
                        <div class="absolute inset-x-0 top-0 h-16 bg-gradient-to-b from-black/45 to-transparent"></div>

                        {{-- shine sweep on hover --}}
                        <span class="absolute inset-y-0 -left-2/3 w-1/2 bg-gradient-to-r from-transparent via-white/30 to-transparent skew-x-[-20deg] transition-transform duration-700 ease-out group-hover:translate-x-[320%] pointer-events-none"></span>

                        {{-- nameplate, overlapping the top edge like a mithai-box label --}}
                        <div class="absolute top-3 left-1/2 -translate-x-1/2 z-10 transition-transform duration-300 group-hover:scale-105">
                            <div class="relative border-2 rounded-md px-3.5 sm:px-4 py-1.5 shadow-md"
                                 :class="active === '{{ $key }}' ? 'bg-maroon-700 border-maroon-700' : 'bg-cream/95 border-gold-500'">
                                <div class="absolute inset-[3px] border pointer-events-none rounded-[3px]"
                                     :class="active === '{{ $key }}' ? 'border-gold-200/50' : 'border-gold-400/60'"></div>
                                <span class="relative font-display font-bold uppercase tracking-wider text-xs sm:text-sm whitespace-nowrap transition-colors duration-300"
                                      :class="active === '{{ $key }}' ? 'text-cream' : 'text-gold-700'">{{ $range['name'] }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
