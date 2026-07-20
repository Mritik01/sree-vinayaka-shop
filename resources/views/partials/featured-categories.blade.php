{{-- Admin-curated "Featured Categories" shortcut row — Amazon/Blinkit-style flat icons, right
     below the header/search (per the user's chosen placement). $featuredCategories comes from
     the home route: active, ordered, with a real product behind at least one mapped tag (a
     Featured Category with no reachable products would be a dead-end tile). Distinct from the
     circular category-photo row below it — this one is tag-driven, not the shop's formal
     Category taxonomy. Reuses the same overflow-arrow + "center when it fits" scroll behavior
     as category-row.blade.php and category-shop.blade.php. --}}
@if ($featuredCategories->isNotEmpty())
    <section x-data="featuredCategoryRow()" class="relative bg-ivory">
        <div class="relative max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 pt-4 sm:pt-5 pb-1">

            <button x-show="canLeft" x-cloak @click="scrollBy(-1)" aria-label="{{ __('Scroll featured categories left') }}"
                    class="hidden sm:flex absolute left-1 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white border border-gold-300/60 shadow-md items-center justify-center text-maroon-700 hover:bg-cream transition">
                ‹
            </button>

            <div x-ref="track" @scroll.debounce.100ms="update()"
                 :class="fits() && 'sm:justify-center'"
                 class="flex items-start gap-3 sm:gap-5 overflow-x-auto scroll-smooth pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($featuredCategories as $fc)
                    <a href="{{ route('products.index', ['featured_category' => $fc->slug]) }}"
                       class="group flex flex-col items-center gap-1.5 shrink-0 w-[76px] sm:w-24">
                        <span class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-white border border-gold-200/70 shadow-sm flex items-center justify-center p-2 group-hover:shadow-md group-hover:border-gold-300 group-hover:-translate-y-0.5 transition duration-200">
                            @if ($fc->image_path)
                                <img src="{{ asset($fc->image_path) }}" alt="{{ $fc->name }}" loading="lazy" class="w-full h-full object-contain">
                            @else
                                <span class="text-xl font-display font-bold text-gold-600">{{ mb_strtoupper(mb_substr($fc->name, 0, 1)) }}</span>
                            @endif
                        </span>
                        <span class="text-xs sm:text-sm font-semibold text-maroon-800 text-center leading-tight">{{ $fc->name }}</span>
                    </a>
                @endforeach
            </div>

            <button x-show="canRight" x-cloak @click="scrollBy(1)" aria-label="{{ __('Scroll featured categories right') }}"
                    class="hidden sm:flex absolute right-1 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white border border-gold-300/60 shadow-md items-center justify-center text-maroon-700 hover:bg-cream transition">
                ›
            </button>
        </div>
    </section>
@endif
