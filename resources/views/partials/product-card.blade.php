{{-- Reusable product card — used by the homepage bestsellers carousel, the "My Favorites" page and the
     /products listing. Expects: $product. Must be rendered inside an element with x-data="productSlider()",
     "favoritesList()" or "productListing()" (all expose isFavorited()/toggleFavorite()) so the heart works.
     Optional: $compact (tighter paddings, no description — for dense mobile grids). Rating stars appear
     automatically when the product was loaded withAvg('reviews','rating') / withCount('reviews'). --}}
@php
    $hex = ltrim($product->color, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    $onColor = $luminance > 0.55 ? '#3a0b12' : '#fdf6e9';
    $compact = $compact ?? false;
    $avgRating = isset($product->reviews_avg_rating) && $product->reviews_count > 0 ? round($product->reviews_avg_rating, 1) : null;
@endphp
<div @dblclick="window.location.href = '{{ route('products.show', $product) }}'"
     class="h-full flex flex-col bg-white rounded-2xl shadow-md hover:shadow-xl hover:-translate-y-1 transition duration-300 overflow-hidden cursor-pointer">
    <div class="relative aspect-[4/3] overflow-hidden" style="background: linear-gradient(160deg, {{ $product->color }}18, {{ $product->color }}4d);">
        <a href="{{ route('products.show', $product) }}" class="absolute inset-0 z-0" aria-label="View {{ $product->name }}">
            <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" style="object-position: {{ $product->image_position ?? '50% 50%' }};"
                 class="absolute inset-0 w-full h-full object-cover transition duration-300 hover:scale-105">
        </a>

        @if ($product->hasDiscount())
            <span class="absolute top-3 left-3 z-10 text-xs font-bold uppercase tracking-wide px-2.5 py-1 rounded-full bg-red-600 text-white shadow-sm">
                {{ $product->discountBadgeLabel() }}
            </span>
        @endif

        <button @click="toggleFavorite({{ $product->id }})" @dblclick.stop aria-label="Save {{ $product->name }} to favourites"
            class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-white/85 hover:bg-white flex items-center justify-center shadow-sm transition">
            <svg class="w-5 h-5 transition" :class="isFavorited({{ $product->id }}) ? 'text-maroon-600' : 'text-maroon-300'"
                 :fill="isFavorited({{ $product->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
            </svg>
        </button>
    </div>

    <div class="{{ $compact ? 'p-3.5 sm:p-5' : 'p-5' }} flex flex-col flex-1">
        <p class="text-[11px] font-semibold tracking-widest uppercase text-gold-600">{{ $product->category }}</p>
        <h3 class="font-display font-bold {{ $compact ? 'text-base sm:text-lg' : 'text-lg' }} text-maroon-800 mt-0.5">
            <a href="{{ route('products.show', $product) }}" class="hover:text-gold-600 transition">{{ $product->name }}</a>
        </h3>

        @if ($avgRating !== null)
            <div class="flex items-center gap-1.5 mt-1.5">
                <span class="flex text-gold-500 text-sm leading-none" aria-label="Rated {{ $avgRating }} out of 5">
                    @for ($i = 1; $i <= 5; $i++)<span class="{{ $i <= round($avgRating) ? '' : 'opacity-25' }}">★</span>@endfor
                </span>
                <span class="text-xs text-maroon-400">{{ $avgRating }} ({{ $product->reviews_count }})</span>
            </div>
        @endif

        <div class="flex flex-wrap gap-2 mt-2.5">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-md border border-gold-300/60 text-maroon-600">
                {{ $product->isLoose() ? \App\Models\Product::portionLabel($product->defaultPortion()) : $product->weight }}
            </span>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-md" style="background-color: {{ $product->color }}; color: {{ $onColor }};">{{ $product->tag }}</span>
        </div>

        @unless ($compact)
            <p class="text-sm text-maroon-500/90 mt-3 leading-relaxed line-clamp-3">{{ $product->description }}</p>
        @endunless

        <div class="flex items-center justify-between {{ $compact ? 'gap-2 flex-wrap' : '' }} mt-4 pt-1 mt-auto">
            <p class="flex items-baseline gap-1.5 flex-wrap">
                @if ($product->hasDiscount())
                    <span class="text-sm text-maroon-300 line-through">₹{{ $product->originalPriceForPortion($product->defaultPortion()) }}</span>
                @endif
                <span class="font-display font-bold text-lg" style="color: {{ $product->color }};">
                    {{ $product->isLoose() ? __('From') . ' ' : '' }}₹{{ $product->priceForPortion($product->defaultPortion()) }}
                </span>
            </p>
            <div class="flex items-center gap-2">
                {{-- plain Add button when it's not in the cart yet --}}
                <template x-if="cartQty({{ $product->id }}) === 0">
                    <button type="button" @click="addProductToCart({{ $product->id }}, 1, false, {{ $product->defaultPortion() ?? 'null' }})" @dblclick.stop aria-label="Add {{ $product->name }} to cart"
                        class="w-10 h-10 rounded-xl border-2 flex items-center justify-center shrink-0 transition hover:scale-105"
                        style="border-color: {{ $product->color }}; color: {{ $product->color }};">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.693 2.602-7.152.232-.94-.437-1.85-1.402-1.85H5.106M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                    </button>
                </template>

                {{-- once it's in the cart, the button becomes a "− N +" stepper so the user
                     gets immediate, unambiguous confirmation and can adjust without leaving the grid --}}
                <template x-if="cartQty({{ $product->id }}) > 0">
                    <div @dblclick.stop
                         class="animate-fab-bump flex items-center h-10 rounded-xl border-2 shrink-0 overflow-hidden"
                         style="border-color: {{ $product->color }};">
                        <button type="button" @click="stepCartQty({{ $product->id }}, -1)" aria-label="Decrease quantity"
                            class="w-8 h-full flex items-center justify-center font-bold text-base hover:bg-black/5 transition" style="color: {{ $product->color }};">−</button>
                        <span class="w-5 text-center text-sm font-bold tabular-nums" style="color: {{ $product->color }};" x-text="cartQty({{ $product->id }})"></span>
                        <button type="button" @click="stepCartQty({{ $product->id }}, 1)" aria-label="Increase quantity"
                            class="w-8 h-full flex items-center justify-center font-bold text-base hover:bg-black/5 transition" style="color: {{ $product->color }};">+</button>
                    </div>
                </template>

                <button type="button" @click="orderNow({{ $product->id }}, 1, {{ $product->defaultPortion() ?? 'null' }})" @dblclick.stop
                   class="{{ $compact ? 'hidden sm:inline-block' : '' }} text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm hover:shadow-md hover:scale-105 transition transform duration-200"
                   style="background-color: {{ $product->color }}; color: {{ $onColor }};">
                    {{ __('Order Now') }}
                </button>
            </div>
        </div>
    </div>
</div>
