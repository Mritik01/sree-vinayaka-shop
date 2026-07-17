{{-- Best Sellers grid — admin-curated via the is_bestseller flag (Admin → Bestsellers);
     horizontal snap-scroll on mobile, 6-across grid on desktop, per the reference. --}}
<section id="bestsellers" class="bg-ivory">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12 pt-7 sm:pt-9 pb-2">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="font-display text-xl sm:text-2xl font-bold text-maroon-900">{{ __('Best Sellers') }}</h2>
            <a href="{{ route('products.index') }}" class="flex items-center gap-1.5 text-sm font-semibold text-maroon-700 hover:text-gold-600 transition shrink-0">
                {{ __('View all') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>

        @if ($products->isEmpty())
            <p class="text-maroon-400 text-sm py-6 text-center">{{ __('Fresh batches coming soon — check back shortly!') }}</p>
        @else
            <div class="flex sm:grid sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 overflow-x-auto sm:overflow-visible snap-x snap-mandatory pb-2 sm:pb-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($products as $product)
                    @include('partials.product-card-mini', ['product' => $product])
                @endforeach
            </div>
        @endif
    </div>
</section>
