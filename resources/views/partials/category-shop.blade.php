{{-- Category-tabbed shop section — replaces the old static "Best Sellers" grid. A permanent
     "⭐ Top Picks" tab (the admin-curated is_bestseller list) plus one tab per active category
     that actually has products ($categoryTabs, computed in the home route — categories with zero
     tagged products, e.g. a freshly-created one, are dropped automatically). Only Top Picks (the
     default active tab) is pre-rendered server-side; every other tab's grid is fetched on first
     click and cached client-side — see categoryShop()'s loadTab() in app.js and
     partials/category-shop-tab-grid.blade.php. Shipping all of them upfront (the old approach)
     meant up to 30 products × every active category sitting in the DOM on every homepage load,
     which was the main driver behind a NO_LCP Lighthouse mobile result. --}}
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
                <button type="button" @click="activeTab = 'top-picks'; $nextTick(() => updateGridArrows())"
                        class="group flex flex-col items-center gap-1.5 shrink-0 w-20 sm:w-24 pb-2 border-b-2 transition"
                        :class="activeTab === 'top-picks' ? 'border-gold-500' : 'border-transparent'">
                    <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-full flex items-center justify-center text-xl sm:text-2xl border transition"
                          :class="activeTab === 'top-picks' ? 'bg-gold-100 border-gold-400' : 'bg-white border-gold-200/70 group-hover:border-gold-300'">⭐</span>
                    <span class="text-[11px] sm:text-xs font-semibold text-center leading-tight transition"
                          :class="activeTab === 'top-picks' ? 'text-maroon-900' : 'text-maroon-500'">{{ __('Top Picks') }}</span>
                </button>

                @foreach ($categoryTabs as $tab)
                    @php $tabKey = $tab['category']->slug; @endphp
                    <button type="button" @click="activeTab = '{{ $tabKey }}'; loadTab('{{ $tabKey }}'); $nextTick(() => updateGridArrows())"
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
                     once, auto-advanced by categoryShop()'s timer — see grid-flow-col below.
                     sm+: same 2-row idea, but each column is sized to fit 6 full cards with the
                     7th sliced in half (auto-cols divides the row into 6.5 slots) so it's obvious
                     there's more to slide to — prev/next arrows page through the rest instead of
                     the old wrap-everything static grid, which produced walls of rows on wide
                     screens. --}}
                <div class="relative">
                    <button x-show="gridCanLeft" x-cloak @click="gridScrollBy(-1)" aria-label="{{ __('Scroll products left') }}"
                            class="hidden sm:flex absolute left-1 sm:-left-4 top-1/2 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white shadow-lg border border-gold-300/50 items-center justify-center text-maroon-700 hover:bg-gold-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </button>

                    <div x-ref="grid-top-picks" @scroll.debounce.100ms="updateGridArrows()"
                         class="grid grid-rows-2 grid-flow-col sm:auto-cols-[calc((100%-6rem)/6.5)] gap-3 sm:gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-2 sm:pb-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        @foreach ($products as $product)
                            @include('partials.product-card-mini', ['product' => $product])
                        @endforeach
                        {{-- one more Top Pick than fits on this slider means there's more to see —
                             $homepageGridLimit (30, in the '/' route) is even so this always lands
                             in a fresh, fully-empty column (row-span-2), never a half-filled one. --}}
                        @if ($topPicksTotal > $products->count())
                            <a href="{{ route('products.index') }}"
                               class="row-span-2 snap-start shrink-0 w-40 sm:w-auto bg-gold-50 hover:bg-gold-100 border-2 border-dashed border-gold-300 rounded-2xl flex flex-col items-center justify-center gap-2 p-3 text-center transition group">
                                <span class="w-10 h-10 rounded-full bg-white border border-gold-300 flex items-center justify-center text-maroon-700 group-hover:translate-x-0.5 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                </span>
                                <span class="text-sm font-semibold text-maroon-800">{{ __('View All') }}</span>
                                <span class="text-xs text-maroon-500">+{{ $topPicksTotal - $products->count() }} {{ __('more') }}</span>
                            </a>
                        @endif
                    </div>

                    <button x-show="gridCanRight" x-cloak @click="gridScrollBy(1)" aria-label="{{ __('Scroll products right') }}"
                            class="hidden sm:flex absolute right-1 sm:-right-4 top-1/2 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white shadow-lg border border-gold-300/50 items-center justify-center text-maroon-700 hover:bg-gold-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        {{-- one lazy-loaded grid per category tab — empty until the tab is first opened, then
             filled in-place by loadTab() (app.js) with the fragment from
             partials/category-shop-tab-grid.blade.php and cached for the rest of the visit --}}
        @foreach ($categoryTabs as $tab)
            @php $tabKey = $tab['category']->slug; @endphp
            <div x-show="activeTab === '{{ $tabKey }}'" x-cloak x-ref="tabpanel-{{ $tabKey }}">
                <div class="grid grid-rows-2 grid-flow-col auto-cols-[7.5rem] sm:auto-cols-[9rem] gap-3 sm:gap-4 overflow-hidden" aria-hidden="true">
                    @for ($i = 0; $i < 8; $i++)
                        <div class="rounded-2xl bg-gold-100/60 animate-pulse aspect-square"></div>
                    @endfor
                </div>
            </div>
        @endforeach
    </div>
</section>
