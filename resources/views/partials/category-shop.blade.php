{{-- Category-tabbed shop section — replaces the old static "Best Sellers" grid. A permanent
     "⭐ Top Picks" tab (the admin-curated is_bestseller list) plus one tab per active category
     that actually has products ($categoryTabs, computed in the home route — categories with zero
     tagged products, e.g. a freshly-created one, are dropped automatically). Every tab's grid is
     pre-rendered server-side and swapped client-side via x-show — no fetch, instant switching,
     same "render every bucket, toggle visibility" approach partials/category-row.blade.php and
     the old shop-by-range filter already used on this page. --}}
<section id="bestsellers" x-data="categoryShop({{ Auth::check() ? 'true' : 'false' }}, @json($favoritedIds))" class="bg-ivory">
    <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 pt-7 sm:pt-9 pb-2">

        {{-- tab strip --}}
        <div class="relative">
            <button x-show="canLeft" x-cloak @click="scrollBy(-1)" aria-label="{{ __('Scroll categories left') }}"
                    class="hidden sm:flex absolute -left-1 top-0 bottom-3 z-10 w-8 items-center justify-center bg-gradient-to-r from-ivory via-ivory/90 to-transparent text-maroon-700">
                ‹
            </button>

            <div x-ref="track" @scroll.debounce.100ms="update()"
                 :class="fits() && 'sm:justify-center'"
                 class="flex items-stretch gap-1.5 sm:gap-3 overflow-x-auto scroll-smooth pb-3 mb-3 border-b border-gold-200/60 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                {{-- permanent Top Picks tab --}}
                <button type="button" @click="activeTab = 'top-picks'"
                        class="group flex flex-col items-center gap-1.5 shrink-0 w-20 sm:w-24 pb-2 border-b-2 transition"
                        :class="activeTab === 'top-picks' ? 'border-gold-500' : 'border-transparent'">
                    <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-full flex items-center justify-center text-xl sm:text-2xl border transition"
                          :class="activeTab === 'top-picks' ? 'bg-gold-100 border-gold-400' : 'bg-white border-gold-200/70 group-hover:border-gold-300'">⭐</span>
                    <span class="text-[11px] sm:text-xs font-semibold text-center leading-tight transition"
                          :class="activeTab === 'top-picks' ? 'text-maroon-900' : 'text-maroon-500'">{{ __('Top Picks') }}</span>
                </button>

                @foreach ($categoryTabs as $tab)
                    @php $tabKey = $tab['category']->slug; @endphp
                    <button type="button" @click="activeTab = '{{ $tabKey }}'"
                            class="group flex flex-col items-center gap-1.5 shrink-0 w-20 sm:w-24 pb-2 border-b-2 transition"
                            :class="activeTab === '{{ $tabKey }}' ? 'border-gold-500' : 'border-transparent'">
                        @if ($tab['category']->image_path)
                            <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-full overflow-hidden border transition"
                                  :class="activeTab === '{{ $tabKey }}' ? 'border-gold-400' : 'border-gold-200/70 group-hover:border-gold-300'">
                                <img src="{{ asset($tab['category']->image_path) }}" alt="{{ $tab['category']->name }}" loading="lazy" class="w-full h-full object-cover">
                            </span>
                        @else
                            <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-full flex items-center justify-center text-lg font-display font-bold border transition"
                                  :class="activeTab === '{{ $tabKey }}' ? 'bg-gold-100 border-gold-400 text-gold-700' : 'bg-white border-gold-200/70 text-gold-600 group-hover:border-gold-300'">
                                {{ mb_strtoupper(mb_substr($tab['category']->name, 0, 1)) }}
                            </span>
                        @endif
                        <span class="text-[11px] sm:text-xs font-semibold text-center leading-tight transition"
                              :class="activeTab === '{{ $tabKey }}' ? 'text-maroon-900' : 'text-maroon-500'">{{ $tab['category']->name }}</span>
                    </button>
                @endforeach
            </div>

            <button x-show="canRight" x-cloak @click="scrollBy(1)" aria-label="{{ __('Scroll categories right') }}"
                    class="hidden sm:flex absolute -right-1 top-0 bottom-3 z-10 w-8 items-center justify-center bg-gradient-to-l from-ivory via-ivory/90 to-transparent text-maroon-700">
                ›
            </button>
        </div>

        {{-- Top Picks grid --}}
        <div x-show="activeTab === 'top-picks'">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 class="font-display text-xl sm:text-2xl font-bold text-maroon-900">{{ __('Top Picks') }}</h2>
                <a href="{{ route('products.index') }}" class="flex items-center gap-1.5 text-sm font-semibold text-maroon-700 hover:text-gold-600 transition shrink-0">
                    {{ __('View all') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>

            @if ($products->isEmpty())
                <p class="text-maroon-400 text-sm py-6 text-center">{{ __('Fresh batches coming soon — check back shortly!') }}</p>
            @else
                {{-- mobile: a 2-row × N-column scrolling grid so 4 cards (2×2) are visible at
                     once instead of a single scrolling row of ~2 — same card size, just paired
                     into columns via grid-flow-col; sm+ resets back to the normal static grid --}}
                <div class="grid grid-rows-2 sm:grid-rows-none grid-flow-col sm:grid-flow-row sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 overflow-x-auto sm:overflow-visible snap-x snap-mandatory pb-2 sm:pb-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($products as $product)
                        @include('partials.product-card-mini', ['product' => $product])
                    @endforeach
                </div>
            @endif
        </div>

        {{-- one grid per category tab --}}
        @foreach ($categoryTabs as $tab)
            @php $tabKey = $tab['category']->slug; @endphp
            <div x-show="activeTab === '{{ $tabKey }}'" x-cloak>
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="font-display text-xl sm:text-2xl font-bold text-maroon-900">{{ $tab['category']->name }}</h2>
                    <a href="{{ route('products.index', ['category' => $tab['category']->slug]) }}" class="flex items-center gap-1.5 text-sm font-semibold text-maroon-700 hover:text-gold-600 transition shrink-0">
                        {{ __('View all') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>

                {{-- mobile: a 2-row × N-column scrolling grid so 4 cards (2×2) are visible at
                     once instead of a single scrolling row of ~2 — same card size, just paired
                     into columns via grid-flow-col; sm+ resets back to the normal static grid --}}
                <div class="grid grid-rows-2 sm:grid-rows-none grid-flow-col sm:grid-flow-row sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 overflow-x-auto sm:overflow-visible snap-x snap-mandatory pb-2 sm:pb-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($tab['products'] as $product)
                        @include('partials.product-card-mini', ['product' => $product])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
