{{-- one category tab's product grid — the AJAX fragment fetched by categoryShop()'s loadTab()
     (resources/js/app.js) the first time a shopper opens that tab, and injected into the matching
     x-ref="tabpanel-{slug}" container in partials/category-shop.blade.php. Content here is
     identical to what used to be pre-rendered for every tab on every homepage load; only the
     "fetched on demand instead of always" part changed. $tab has 'category', 'products', 'total'
     — same shape the old inline loop used. --}}
@php $tabKey = $tab['category']->slug; @endphp
<div class="flex items-center justify-between gap-3 mb-4">
    <h2 class="font-display text-xl sm:text-2xl font-bold text-maroon-900">{{ $tab['category']->name }}</h2>
    <a href="{{ route('products.index', ['category' => $tab['category']->slug]) }}" class="flex items-center gap-1.5 text-sm font-semibold text-maroon-700 hover:text-gold-600 transition shrink-0">
        {{ __('View all') }}
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
    </a>
</div>

{{-- mobile: a 2-row × N-column scrolling grid so 4 cards (2×2) are visible at once, auto-advanced
     by categoryShop()'s timer. sm+: sized to fit 6 full cards with the 7th sliced in half so it's
     obvious there's more to slide to — prev/next arrows page through the rest. --}}
<div class="relative">
    <button x-show="gridCanLeft" x-cloak @click="gridScrollBy(-1)" aria-label="{{ __('Scroll products left') }}"
            class="hidden sm:flex absolute left-1 sm:-left-4 top-1/2 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white shadow-lg border border-gold-300/50 items-center justify-center text-maroon-700 hover:bg-gold-50 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
    </button>

    <div x-ref="grid-{{ $tabKey }}" @scroll.debounce.100ms="updateGridArrows()"
         class="grid grid-rows-2 grid-flow-col sm:auto-cols-[calc((100%-6rem)/6.5)] gap-3 sm:gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-2 sm:pb-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        @foreach ($tab['products'] as $product)
            @include('partials.product-card-mini', ['product' => $product])
        @endforeach
        {{-- see the matching comment on the Top Picks grid in category-shop.blade.php --}}
        @if ($tab['total'] > $tab['products']->count())
            <a href="{{ route('products.index', ['category' => $tab['category']->slug]) }}"
               class="row-span-2 snap-start shrink-0 w-40 sm:w-auto bg-gold-50 hover:bg-gold-100 border-2 border-dashed border-gold-300 rounded-2xl flex flex-col items-center justify-center gap-2 p-3 text-center transition group">
                <span class="w-10 h-10 rounded-full bg-white border border-gold-300 flex items-center justify-center text-maroon-700 group-hover:translate-x-0.5 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </span>
                <span class="text-sm font-semibold text-maroon-800">{{ __('View All') }}</span>
                <span class="text-xs text-maroon-500">+{{ $tab['total'] - $tab['products']->count() }} {{ __('more') }}</span>
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
