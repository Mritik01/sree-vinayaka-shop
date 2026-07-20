{{-- circular category icons under the header — backend-driven from the Category model.
     Loaded here (not from the layout's $navCategories) because section content renders
     before the layout itself does. Arrows appear only when the row overflows.
     Admin → Configuration → "Category Row" can hide this whole section site-wide. --}}
@php
    $rowCategories = \App\Models\ShopSetting::current()->show_category_row
        ? \App\Models\Category::where('is_active', true)->orderBy('sort_order')->get()
        : collect();
@endphp
@if ($rowCategories->isNotEmpty())
    <section id="range" x-data="categoryRow()" class="relative bg-ivory border-b border-gold-200/40">
        <div class="relative max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 py-4 sm:py-5">

            <button x-show="canLeft" x-cloak @click="scrollBy(-1)" aria-label="{{ __('Scroll categories left') }}"
                    class="hidden sm:flex absolute left-1 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white border border-gold-300/60 shadow-md items-center justify-center text-maroon-700 hover:bg-cream transition">
                ‹
            </button>

            <div x-ref="track" @scroll.debounce.100ms="update()"
                 :class="fits() && 'sm:justify-center'"
                 class="flex items-start gap-4 sm:gap-7 overflow-x-auto scroll-smooth pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($rowCategories as $category)
                    <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                       class="group flex flex-col items-center gap-2 shrink-0 w-[74px] sm:w-24">
                        @if ($category->image_path)
                            <span class="w-[68px] h-[68px] sm:w-[88px] sm:h-[88px] rounded-full overflow-hidden bg-white border border-gold-200/70 shadow-sm group-hover:shadow-md group-hover:scale-105 transition duration-200">
                                <img src="{{ asset($category->image_path) }}" alt="{{ $category->name }}" loading="lazy" class="w-full h-full object-cover">
                            </span>
                        @else
                            <span class="w-[68px] h-[68px] sm:w-[88px] sm:h-[88px] rounded-full bg-gold-100 border border-gold-300/60 shadow-sm flex items-center justify-center font-display font-bold text-2xl text-gold-600 group-hover:shadow-md group-hover:scale-105 transition duration-200">
                                {{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}
                            </span>
                        @endif
                        <span class="text-xs sm:text-sm font-semibold text-maroon-800 text-center leading-tight">{{ $category->name }}</span>
                    </a>
                @endforeach

                {{-- "More" tile — bottom-sheet on mobile (existing category panel), Shop All on desktop --}}
                <button type="button" @click="window.matchMedia('(min-width: 1024px)').matches ? window.location.href = '{{ route('products.index') }}' : window.dispatchEvent(new CustomEvent('open-category-panel'))"
                        class="group flex flex-col items-center gap-2 shrink-0 w-[74px] sm:w-24">
                    <span class="w-[68px] h-[68px] sm:w-[88px] sm:h-[88px] rounded-full bg-white border-2 border-gold-400/70 shadow-sm flex items-center justify-center text-maroon-700 tracking-widest text-xl font-bold group-hover:shadow-md group-hover:scale-105 transition duration-200">•••</span>
                    <span class="text-xs sm:text-sm font-semibold text-maroon-800 text-center leading-tight">{{ __('More') }}</span>
                </button>
            </div>

            <button x-show="canRight" x-cloak @click="scrollBy(1)" aria-label="{{ __('Scroll categories right') }}"
                    class="hidden sm:flex absolute right-1 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white border border-gold-300/60 shadow-md items-center justify-center text-maroon-700 hover:bg-cream transition">
                ›
            </button>
        </div>
    </section>
@endif
