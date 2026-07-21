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
    // only a loose product with more than one configured weight needs an inline picker —
    // a single-portion loose product (or a piece product) has nothing to choose between
    $hasMultiplePortions = $product->isLoose() && count($product->portions ?? []) > 1;
@endphp
<div @dblclick="window.location.href = '{{ route('products.show', $product) }}'"
     class="h-full flex flex-col bg-white rounded-2xl shadow-md hover:shadow-xl hover:-translate-y-1 transition duration-300 overflow-hidden cursor-pointer">
    <div class="relative aspect-[4/3] overflow-hidden" style="background: linear-gradient(160deg, {{ $product->color }}18, {{ $product->color }}4d);">
        <a href="{{ route('products.show', $product) }}" class="absolute inset-0 z-0" aria-label="View {{ $product->name }}">
            <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" style="object-position: {{ $product->image_position ?? '50% 50%' }};"
                 class="absolute inset-0 w-full h-full object-cover transition duration-300 hover:scale-105 {{ $product->is_out_of_stock ? 'grayscale opacity-60' : '' }}">
        </a>

        @if ($product->is_out_of_stock)
            <div class="absolute inset-0 z-[5] bg-maroon-950/35 flex items-center justify-center pointer-events-none">
                <span class="bg-maroon-900 text-cream text-xs font-bold uppercase tracking-wider px-3 py-1.5 rounded-full shadow-lg border border-white/20">
                    {{ __('Out of Stock') }}
                </span>
            </div>
        @elseif ($product->hasDiscount())
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

    <div class="{{ $compact ? 'p-3.5 sm:p-5' : 'p-5' }} flex flex-col flex-1"
         @if ($hasMultiplePortions)
         x-data="{
             portionOpen: false, selPortion: {{ $product->defaultPortion() }},
             basePrice: {{ $product->discountedBasePrice() }}, rawPrice: {{ $product->price }}, hasDiscount: {{ $product->hasDiscount() ? 'true' : 'false' }},
             menuStyle: '',
             selectedPrice() { return Math.round(this.basePrice * (this.selPortion / 250)); },
             selectedOriginalPrice() { return Math.round(this.rawPrice * (this.selPortion / 250)); },
             // positions the teleported popover under whichever trigger opened it, clamped to the
             // viewport — sized to match a single portion pill, not a full-width dropdown
             openPicker(anchorEl) {
                 const ci = window.cartItem({{ $product->id }});
                 if (ci) { this.selPortion = ci.portion; }
                 const rect = anchorEl.getBoundingClientRect();
                 const width = 224;
                 const left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8)) + window.scrollX;
                 const top = rect.bottom + window.scrollY + 6;
                 this.menuStyle = `position:absolute; top:${top}px; left:${left}px; width:${width}px;`;
                 this.portionOpen = true;
             },
         }"
         @endif>
        <p class="text-[11px] font-semibold tracking-widest uppercase text-gold-600">{{ $product->category }}</p>
        <h3 class="font-display font-bold {{ $compact ? 'text-base sm:text-lg' : 'text-lg' }} text-maroon-800 mt-0.5 truncate">
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

        {{-- always a single line, same height on every card regardless of tag text length —
             previously this wrapped to 2 lines on longer tags ("Melt in Mouth", "Savoury Bite"),
             which threw off the vertical rhythm between cards sharing a mobile grid row --}}
        <div class="flex items-center gap-2 mt-2.5">
            @if ($hasMultiplePortions)
                <button type="button"
                        @click="portionOpen ? (portionOpen = false) : openPicker($el)"
                        @dblclick.stop
                        class="flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-md border transition shrink-0"
                        :class="portionOpen ? 'border-maroon-600 bg-maroon-50 text-maroon-800' : 'border-gold-300/60 text-maroon-600 hover:border-maroon-400'">
                    <span x-text="window.portionLabel(selPortion)"></span>
                    <svg class="w-3 h-3 transition-transform duration-200" :class="portionOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
            @else
                <span class="text-xs font-semibold px-2.5 py-1 rounded-md border border-gold-300/60 text-maroon-600 shrink-0">
                    {{ $product->isLoose() ? \App\Models\Product::portionLabel($product->defaultPortion()) : $product->weight }}
                </span>
            @endif
            <span class="text-xs font-semibold px-2.5 py-1 rounded-md truncate min-w-0" style="background-color: {{ $product->color }}; color: {{ $onColor }};">{{ $product->tag }}</span>
        </div>

        @if ($hasMultiplePortions)
            {{-- floating popover, teleported to <body> — the card's own rounded-2xl wrapper uses
                 overflow-hidden (for the product image's corners), which would clip an in-place
                 dropdown; teleporting escapes that and avoids resizing/pushing the card open --}}
            <template x-teleport="body">
                <div x-show="portionOpen" x-cloak @click.outside="portionOpen = false" @click.stop
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                     :style="menuStyle"
                     class="z-[60] rounded-xl border border-gold-200/70 bg-white shadow-xl p-3">
                    <p class="text-[10px] font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">{{ __('Select Weight') }}</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (collect($product->portions)->sort()->values() as $grams)
                            {{-- picking a weight adds it to the cart right away (replacing any
                                 different portion already there — see CartController::add) and
                                 closes the popover, so there's no separate "Add to Cart" step --}}
                            <button type="button"
                                    @click="selPortion = {{ $grams }}; addProductToCart({{ $product->id }}, 1, false, selPortion).then(() => portionOpen = false)"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-full border-2 transition"
                                    :class="selPortion === {{ $grams }} ? 'bg-maroon-700 border-maroon-700 text-cream' : 'bg-white border-gold-300/70 text-maroon-700 hover:border-maroon-400'">
                                {{ \App\Models\Product::portionLabel($grams) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </template>
        @endif

        <p class="{{ $compact ? 'hidden sm:block' : '' }} text-sm text-maroon-500/90 mt-3 leading-relaxed line-clamp-3">{{ $product->description }}</p>

        <div class="flex items-center justify-between {{ $compact ? 'gap-2 flex-wrap sm:flex-nowrap sm:gap-0' : '' }} mt-4 pt-1 mt-auto">
            <p class="flex items-baseline gap-1.5 flex-wrap">
                @if ($hasMultiplePortions)
                    <template x-if="hasDiscount">
                        <span class="text-sm text-maroon-300 line-through">₹<span x-text="selectedOriginalPrice().toLocaleString('en-IN')"></span></span>
                    </template>
                    <span class="font-display font-bold text-lg" style="color: {{ $product->color }};">
                        ₹<span x-text="selectedPrice().toLocaleString('en-IN')"></span>
                    </span>
                @else
                    @if ($product->hasDiscount())
                        <span class="text-sm text-maroon-300 line-through">₹{{ $product->originalPriceForPortion($product->defaultPortion()) }}</span>
                    @endif
                    <span class="font-display font-bold text-lg" style="color: {{ $product->color }};">
                        {{ $product->isLoose() ? __('From') . ' ' : '' }}₹{{ $product->priceForPortion($product->defaultPortion()) }}
                    </span>
                @endif
            </p>
            <div class="flex items-center gap-2">
                @if ($product->is_out_of_stock)
                    <span class="text-xs font-bold uppercase tracking-wide text-red-600 bg-red-50 border border-red-200 rounded-full px-3 py-2 shrink-0 whitespace-nowrap">
                        {{ __('Out of Stock') }}
                    </span>
                @else
                {{-- plain Add button when it's not in the cart yet — for a multi-portion loose
                     product this adds whatever weight is currently selected above (defaults to
                     the smallest portion until the customer picks a different one) --}}
                <template x-if="cartQty({{ $product->id }}) === 0">
                    <button type="button"
                        @click="{{ $hasMultiplePortions ? "addProductToCart({$product->id}, 1, false, selPortion)" : "addProductToCart({$product->id}, 1, false, " . ($product->defaultPortion() ?? 'null') . ')' }}"
                        @dblclick.stop aria-label="Add {{ $product->name }} to cart"
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

                <button type="button"
                   @click="orderNow({{ $product->id }}, 1, {{ $hasMultiplePortions ? 'selPortion' : ($product->defaultPortion() ?? 'null') }})"
                   @dblclick.stop
                   class="{{ $compact ? 'hidden sm:inline-block' : '' }} text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm hover:shadow-md hover:scale-105 transition transform duration-200"
                   style="background-color: {{ $product->color }}; color: {{ $onColor }};">
                    {{ __('Order Now') }}
                </button>
                @endif
            </div>
        </div>
    </div>
</div>
