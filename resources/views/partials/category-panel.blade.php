{{-- Mobile "Categories" bottom-sheet — opened by the bottom nav's Categories tab dispatching
     `open-category-panel` (see partials/bottom-nav.blade.php). Tapping a tile navigates to
     /products?category={slug}, which productListing() (resources/js/app.js) pre-filters to on
     load via the server-resolved `activeCategory` (see routes/web.php's /products closure).
     Motion/easing matches the /products filter sheet (products.blade.php) for consistency. --}}
<div x-data="{ open: false }" @open-category-panel.window="open = true" class="lg:hidden">
    <div x-show="open" x-cloak class="fixed inset-0 z-[105]">
        <div x-show="open" class="absolute inset-0 bg-maroon-900/50 backdrop-blur-sm"
             x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="open = false"></div>

        <div x-show="open"
             x-transition:enter="transition-transform ease-[cubic-bezier(0.32,0.72,0,1)] duration-[400ms]" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition-transform ease-in duration-[250ms]" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute inset-x-0 bottom-0 bg-ivory rounded-t-3xl shadow-2xl max-h-[80vh] flex flex-col will-change-transform">
            <div class="pt-3 pb-1 flex justify-center shrink-0" @click="open = false">
                <span class="w-10 h-1.5 rounded-full bg-gold-300"></span>
            </div>
            <div class="flex items-center justify-between px-5 py-3 border-b border-gold-200/60 shrink-0">
                <p class="font-display font-bold text-maroon-800">{{ __('Categories') }}</p>
                <button type="button" @click="open = false" aria-label="{{ __('Close categories') }}" class="text-maroon-400 hover:text-maroon-700 transition text-xl leading-none">✕</button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
                @if ($categories->isEmpty())
                    <p class="text-center text-sm text-maroon-400 py-8">{{ __('No categories yet.') }}</p>
                @else
                    <div class="grid grid-cols-2 gap-4">
                        @foreach ($categories as $category)
                            <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                               class="flex flex-col items-center gap-2 text-center group">
                                <span class="relative w-20 h-20 sm:w-24 sm:h-24 rounded-full border-2 border-gold-400 shadow-sm overflow-hidden transition duration-200 group-active:scale-95 group-hover:border-maroon-600">
                                    @if ($category->image_path)
                                        <img src="{{ asset($category->image_path) }}" alt="{{ $category->name }}" loading="lazy" class="w-full h-full object-cover">
                                    @else
                                        <span class="w-full h-full bg-gold-100 flex items-center justify-center font-display font-bold text-2xl text-gold-600">
                                            {{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}
                                        </span>
                                    @endif
                                </span>
                                <span class="text-sm font-semibold text-maroon-800 group-hover:text-gold-600 transition">{{ $category->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
