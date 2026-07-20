@extends('layouts.app')

@section('title', 'All Sweets & Snacks — Makhanbhog Sweets')

@section('content')
@php
    $favoritedIds = Auth::check() ? Auth::user()->favorites()->pluck('products.id')->all() : [];

    $priceBuckets = [
        ['label' => __('Under ₹200'), 'min' => 0, 'max' => 200],
        ['label' => '₹200 – ₹350', 'min' => 200, 'max' => 350],
        ['label' => '₹350 – ₹500', 'min' => 350, 'max' => 500],
        ['label' => __('₹500 & above'), 'min' => 500, 'max' => null],
    ];

    $productsForJs = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'category' => $p->category,
        'category_ids' => $p->categories->pluck('id'),
        'tag_ids' => $p->tags->pluck('id'),
        'search_tags' => $p->search_tags_flat,
        'price' => (int) $p->price,
        'rating' => round($p->reviews_avg_rating ?? 0, 1),
        'reviews' => (int) $p->reviews_count,
    ])->values();
@endphp

{{-- $activeCategory is resolved server-side from ?category=slug (see the /products route
     closure) — e.g. arriving from the mobile category panel — and pre-checks that entry in
     productListing()'s checkbox-driven `filters.categories` below (same array the sidebar/sheet
     checkboxes use — keeping it a single dimension is what lets it combine with price/search
     instead of AND-ing against itself and zeroing out the grid). $activeFeaturedCategory
     (?featured_category=slug, from the homepage's Featured Categories row) works the same way
     but resolves to a SET of tag ids (filters.tagIds), since a Featured Category can map to more
     than one tag. --}}
<section class="relative py-10 sm:py-14 bg-ivory min-h-[80vh] overflow-hidden"
         x-data='productListing({{ Auth::check() ? 'true' : 'false' }}, @json($favoritedIds), @json($productsForJs), @json($priceBuckets), @json($activeCategory?->name), @json($activeTagIds))'>

    {{-- ambient glow blobs --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-16 left-[6%] w-72 h-72 rounded-full bg-gold-400/15 blur-3xl"></div>
        <div class="absolute bottom-10 -right-12 w-80 h-80 rounded-full bg-maroon-400/10 blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6">
        <nav class="text-sm text-maroon-500 flex items-center gap-2 mb-5">
            <a href="/" class="hover:text-gold-600 transition">{{ __('Home') }}</a>
            <span class="text-gold-400">✦</span>
            <span class="text-maroon-700 font-medium">{{ __('All Sweets') }}</span>
        </nav>

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-gold-600 tracking-[0.3em] uppercase text-xs font-semibold mb-1.5">✦ {{ __('Fresh Every Morning') }} ✦</p>
                <h1 class="font-display text-3xl sm:text-4xl font-bold text-maroon-800">{{ __('Our Sweets & Snacks') }}</h1>
            </div>
            <p class="text-sm text-maroon-400">
                {{ __('Showing') }} <span class="font-semibold text-maroon-700" x-text="visibleCount()"></span> {{ __('of') }} {{ $products->count() }} {{ __('treats') }}
            </p>
        </div>

        {{-- arrived via the mobile category panel / a shared ?category=slug link — visible
             indicator + easy way to undo it, since it's not one of the checkbox filters --}}
        @if ($activeCategory)
            <div x-show="initialCategoryName && filters.categories.includes(initialCategoryName)" x-cloak class="mt-4 inline-flex items-center gap-2 bg-gold-100 border border-gold-300/60 text-maroon-700 text-sm font-semibold rounded-full pl-4 pr-2 py-1.5">
                {{ __('Filtered by') }}: {{ $activeCategory->name }}
                <button type="button" @click="clearCategoryFilter()" aria-label="{{ __('Clear category filter') }}"
                        class="w-5 h-5 rounded-full bg-white/70 hover:bg-white text-maroon-500 hover:text-maroon-800 transition flex items-center justify-center text-xs leading-none">✕</button>
            </div>
        @endif

        {{-- arrived via the homepage's Featured Categories row (?featured_category=slug) --}}
        @if ($activeFeaturedCategory)
            <div x-show="filters.tagIds.length" x-cloak class="mt-4 inline-flex items-center gap-2 bg-gold-100 border border-gold-300/60 text-maroon-700 text-sm font-semibold rounded-full pl-4 pr-2 py-1.5">
                {{ __('Filtered by') }}: {{ $activeFeaturedCategory->name }}
                <button type="button" @click="clearTagFilter()" aria-label="{{ __('Clear filter') }}"
                        class="w-5 h-5 rounded-full bg-white/70 hover:bg-white text-maroon-500 hover:text-maroon-800 transition flex items-center justify-center text-xs leading-none">✕</button>
            </div>
        @endif

        {{-- toolbar: search + sort --}}
        <div class="flex items-center gap-3 mt-6 mb-8">
            <div class="relative flex-1 sm:max-w-xs">
                <input type="search" x-model="filters.q" placeholder="{{ __('Search sweets…') }}"
                       class="w-full rounded-xl border border-gold-300/70 bg-white pl-10 pr-4 py-2.5 text-sm text-maroon-800 placeholder-maroon-400/60 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition shadow-sm">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-maroon-300 pointer-events-none">🔎</span>
            </div>
            <select x-model="sort" aria-label="Sort products"
                    class="rounded-xl border border-gold-300/70 bg-white px-3.5 py-2.5 text-sm text-maroon-700 font-medium focus:outline-none focus:ring-2 focus:ring-gold-400 focus:border-gold-400 transition shadow-sm cursor-pointer">
                <option value="recommended">{{ __('Recommended') }}</option>
                <option value="price_asc">{{ __('Price: Low to High') }}</option>
                <option value="price_desc">{{ __('Price: High to Low') }}</option>
                <option value="rating">{{ __('Top Rated') }}</option>
                <option value="name">{{ __('Name: A to Z') }}</option>
            </select>
        </div>

        <div class="flex gap-8 items-start">
            {{-- desktop filter sidebar --}}
            <aside class="hidden lg:block w-60 shrink-0 sticky top-28 bg-white rounded-2xl border border-gold-200/60 shadow-sm p-5">
                <p class="font-display font-bold text-maroon-800 mb-5 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                    </svg>
                    {{ __('Filters') }}
                </p>
                @include('partials.product-filters')
            </aside>

            {{-- product grid --}}
            <div class="flex-1 min-w-0">
                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    @foreach ($products as $product)
                        <div x-show="visible({{ $product->id }})" :style="`order: ${orderOf({{ $product->id }})}`"
                             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                             class="animate-fade-up" style="animation-delay: {{ min($loop->index, 11) * 60 }}ms">
                            @include('partials.product-card-mini', ['product' => $product, 'fixedWidth' => false])
                        </div>
                    @endforeach
                </div>

                {{-- empty state --}}
                <div x-show="visibleCount() === 0" x-cloak class="text-center py-20"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                    <p class="text-5xl mb-4">🍬</p>
                    <p class="text-maroon-700 font-display text-xl">{{ __('No sweets match those filters') }}</p>
                    <p class="text-maroon-500 mt-2 max-w-sm mx-auto">{{ __('Try removing a filter or two — the good stuff is hiding just behind them.') }}</p>
                    <button type="button" @click="clearAll()" class="btn-gold mt-8">{{ __('Clear All Filters') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- mobile floating filter button — small icon-only, tucked to the right so the centered
         "View Cart" pill stays the visual focus of this row. It lifts above that pill (rather
         than sitting at a fixed bottom-20) whenever the cart has items, since the pill's own
         height at bottom-[4.6rem] (see layouts/app.blade.php) otherwise overlaps and z-index
         alone can't make an overlapping button comfortable to tap. --}}
    <div class="lg:hidden fixed right-5 z-[60] transition-[bottom] duration-300"
         :class="$store.cart.count > 0 ? 'bottom-40' : 'bottom-20'">
        <button type="button" @click="sheetOpen = true" aria-label="{{ __('Filters') }}"
                class="relative flex items-center justify-center w-12 h-12 rounded-full bg-maroon-700 text-cream shadow-2xl shadow-maroon-900/40 active:scale-95 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
            </svg>
            <span x-show="activeCount() > 0" x-cloak
                  class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-gold-400 text-maroon-900 text-[10px] font-bold flex items-center justify-center"
                  x-text="activeCount()"></span>
        </button>
    </div>

    {{-- mobile bottom-sheet filters --}}
    <div x-show="sheetOpen" x-cloak class="lg:hidden fixed inset-0 z-[105]">
        <div x-show="sheetOpen" class="absolute inset-0 bg-maroon-900/50 backdrop-blur-sm"
             x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="sheetOpen = false"></div>

        <div x-show="sheetOpen"
             x-transition:enter="transition-transform ease-[cubic-bezier(0.32,0.72,0,1)] duration-[400ms]" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition-transform ease-in duration-[250ms]" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute inset-x-0 bottom-0 bg-ivory rounded-t-3xl shadow-2xl max-h-[80vh] flex flex-col will-change-transform">
            <div class="pt-3 pb-1 flex justify-center shrink-0" @click="sheetOpen = false">
                <span class="w-10 h-1.5 rounded-full bg-gold-300"></span>
            </div>
            <div class="flex items-center justify-between px-5 py-3 border-b border-gold-200/60 shrink-0">
                <p class="font-display font-bold text-maroon-800">{{ __('Filters') }}</p>
                <button type="button" @click="sheetOpen = false" aria-label="Close filters" class="text-maroon-400 hover:text-maroon-700 transition text-xl leading-none">✕</button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-5">
                @include('partials.product-filters')
            </div>

            <div class="px-5 py-4 border-t border-gold-200/60 shrink-0 bg-ivory">
                <button type="button" @click="sheetOpen = false" class="btn-gold w-full text-center">
                    {{ __('Show') }} <span x-text="visibleCount()"></span> {{ __('Sweets') }}
                </button>
            </div>
        </div>
    </div>
</section>
@endsection
