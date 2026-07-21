{{-- Bestsellers — real products pulled from the `products` table, shown as a seamless-looping swipeable carousel. Each card is accented with its own DB-driven color.
     One tripled (clone-real-clone) track is pre-rendered per category "bucket" (including an "all" bucket) so clicking a
     card in shop-by-range.blade.php can switch buckets via x-show instead of mutating a single track's DOM — see
     productSlider() in app.js for why: the infinite-loop trick depends on a per-track scrollWidth/3 measurement that
     would break if we filtered a single shared track in place. --}}
@php
    $categoryOrder = ['Sweets', 'Namkeen', 'Desi Ghee', 'Cookies', 'Mathi', 'Dry Fruits', 'Syrup'];
    $grouped = $products->groupBy('category');
    $buckets = collect(['all' => $products]);
    foreach ($categoryOrder as $cat) {
        $buckets->put($cat, $grouped->get($cat, collect()));
    }
    $bucketCounts = $buckets->map->count();
    $favoritedIds = Auth::check() ? Auth::user()->favorites()->pluck('products.id')->all() : [];
@endphp

<section id="bestsellers" class="relative py-20 bg-ivory overflow-hidden"
         x-data='productSlider({{ Auth::check() ? "true" : "false" }}, @json($favoritedIds), @json($bucketCounts))'
         @filter-category.window="setCategory($event.detail)">
    {{-- ambient glow blobs --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-16 left-[8%] w-72 h-72 rounded-full bg-gold-400/25 blur-3xl"></div>
        <div class="absolute top-1/3 -right-10 w-80 h-80 rounded-full bg-maroon-400/15 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/4 w-64 h-64 rounded-full bg-pista-400/15 blur-3xl"></div>
    </div>

    {{-- glowing fairy-light canopy — three layered strands draping down behind the heading --}}
    @php
        $strands = [
            ['top' => 4, 'amp' => 22, 'count' => 22, 'delay' => 0.14, 'size' => 'w-2 h-2 sm:w-2.5 sm:h-2.5', 'opacity' => ''],
            ['top' => 34, 'amp' => 16, 'count' => 18, 'delay' => 0.17, 'size' => 'w-1.5 h-1.5 sm:w-2 sm:h-2', 'opacity' => 'opacity-80'],
            ['top' => 62, 'amp' => 12, 'count' => 15, 'delay' => 0.2, 'size' => 'w-1.5 h-1.5', 'opacity' => 'opacity-60'],
        ];
    @endphp
    <div class="pointer-events-none absolute top-0 inset-x-0 h-40 sm:h-56 z-0">
        @foreach ($strands as $strand)
            <svg class="absolute inset-x-0 w-full h-10" style="top: {{ $strand['top'] }}px" viewBox="0 0 1200 40" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 20 Q 75 {{ 20 - $strand['amp'] }} 150 20 T 300 20 T 450 20 T 600 20 T 750 20 T 900 20 T 1050 20 T 1200 20"
                      fill="none" stroke="#e9c87345" stroke-width="1.5" />
            </svg>
            <div class="absolute inset-x-0 flex justify-around px-3 sm:px-8" style="top: {{ $strand['top'] + 14 }}px">
                @for ($i = 0; $i < $strand['count']; $i++)
                    <span class="{{ $strand['size'] }} {{ $strand['opacity'] }} rounded-full bg-gold-300 animate-bulb-glow"
                          style="animation-delay: {{ $i * $strand['delay'] }}s"></span>
                @endfor
            </div>
        @endforeach
    </div>

    <div class="relative z-10 max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12">
        <h2 class="section-heading">{{ __("Siswa Bazar's Favourites") }}</h2>
        <p class="text-center text-maroon-500 mt-3 mb-10 max-w-xl mx-auto">{{ __("Hand-picked bestsellers, made fresh every morning — swipe through and see what everyone's ordering.") }}</p>
    </div>

    <div class="relative z-10 max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12">
        <button @click="scrollTrack(-1)" aria-label="Previous products"
            class="hidden sm:flex absolute left-1 sm:left-3 top-[calc(50%-1rem)] -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white shadow-lg border border-gold-300/50 items-center justify-center text-maroon-700 hover:bg-gold-50 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
        </button>
        <button @click="scrollTrack(1)" aria-label="Next products"
            class="hidden sm:flex absolute right-1 sm:right-3 top-[calc(50%-1rem)] -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white shadow-lg border border-gold-300/50 items-center justify-center text-maroon-700 hover:bg-gold-50 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </button>

        <div x-show="activeCategory !== 'all' && (bucketCounts[activeCategory] ?? 0) === 0" x-cloak
             class="text-center py-16 max-w-md mx-auto">
            <p class="text-4xl mb-3">🪔</p>
            <p class="text-maroon-700 font-display font-bold text-lg">{{ __('New batch of') }} <span x-text="activeCategory"></span> {{ __('arriving soon') }}</p>
            <p class="text-maroon-500 mt-2">{{ __('Meanwhile, enjoy our bestsellers.') }}</p>
            <button @click="setCategory('all')" class="btn-gold inline-block mt-6 text-sm px-6 py-2.5">{{ __('See Bestsellers') }}</button>
        </div>

        @foreach ($buckets as $key => $bucketProducts)
            @php
                $slug = strtolower(str_replace(' ', '-', $key));
                $tripled = $bucketProducts->isNotEmpty() ? $bucketProducts->concat($bucketProducts)->concat($bucketProducts) : collect();
            @endphp
            <div x-ref="track_{{ $slug }}" x-show="activeCategory === '{{ $key }}'" x-cloak
                 class="flex gap-3 sm:gap-6 overflow-x-auto snap-x snap-mandatory pb-4 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($tripled as $product)
                    <div class="snap-start shrink-0 w-[42vw] sm:w-[300px] lg:w-[320px]">
                        @include('partials.product-card', ['product' => $product, 'compact' => true])
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</section>
