{{-- THE canonical product card — used everywhere a product is shown in a list/grid (homepage
     Top Picks + category tabs, /products listing, related products, My Favorites). Image, name,
     weight, rating, price/discount, heart-toggle, round add button that becomes a stepper once
     in the cart. Expects $product loaded with reviews_avg_rating / reviews_count aggregates
     (nullable — the rating row just doesn't render when absent) and must be rendered inside an
     x-data scope exposing isFavorited(id)/toggleFavorite(id) (categoryShop(), productListing(),
     favoritesList(), accountPage() all do). A loose product with more than one configured weight
     gets a small chevron next to the weight that opens a teleported portion picker.

     $fixedWidth (default true): the homepage's horizontal-scroll rows need the card's own width
     to define each column (they use grid-flow-col, not an explicit column count) — pass false
     when reusing this inside a real CSS grid (/products, related products, favorites) so the
     card stretches to fill its grid cell instead of sitting at a fixed width. --}}
@php
    $miniPortion = $product->defaultPortion();
    $miniWeight = $product->isLoose() ? \App\Models\Product::portionLabel($miniPortion) : $product->weight;
    $miniAvg = $product->reviews_avg_rating ? round($product->reviews_avg_rating, 1) : null;
    $hasMultiplePortions = $product->isLoose() && count($product->portions ?? []) > 1;
    $fixedWidth = $fixedWidth ?? true;
@endphp

{{-- h-full (non-fixed-width usages only): /products, favorites etc. wrap this card in their
     own grid-item div (for fade-in animation classes) — CSS Grid stretches THAT wrapper to
     match its row, but without h-full here the actual bordered card stayed shrink-wrapped to
     its own content inside it, so shorter cards visibly ended higher than their row-mates. --}}
<div class="{{ $fixedWidth ? 'snap-start shrink-0 w-40 sm:w-auto' : 'w-full h-full' }} bg-white rounded-2xl border border-gold-200/60 shadow-sm hover:shadow-md transition p-3 flex flex-col"
     @if ($hasMultiplePortions)
     x-data="{
         portionOpen: false, selPortion: {{ $miniPortion }},
         basePrice: {{ $product->discountedBasePrice() }}, rawPrice: {{ $product->price }}, hasDiscount: {{ $product->hasDiscount() ? 'true' : 'false' }},
         discountType: {{ Illuminate\Support\Js::from($product->discount_type) }}, discountValue: {{ (int) $product->discount_value }},
         // per-portion price overrides (see Product::portionOverridePrice()) — a pre-packaged
         // item's pack sizes aren't always a clean multiple of the 250g price, so a portion with
         // its own admin-set price here wins over the basePrice*ratio math below
         portionPrices: {{ Illuminate\Support\Js::from($product->portion_prices ?? (object) []) }},
         menuStyle: '',
         applyDiscount(p) {
             if (!this.hasDiscount) return p;
             if (this.discountType === 'percentage') return Math.round(p * (1 - Math.min(this.discountValue, 100) / 100));
             return Math.max(0, p - this.discountValue);
         },
         selectedPrice() {
             const override = this.portionPrices[this.selPortion];
             if (override !== undefined && override !== null) return this.applyDiscount(override);
             return Math.round(this.basePrice * (this.selPortion / 250));
         },
         selectedOriginalPrice() {
             const override = this.portionPrices[this.selPortion];
             if (override !== undefined && override !== null) return override;
             return Math.round(this.rawPrice * (this.selPortion / 250));
         },
         openPicker(anchorEl) {
             const ci = window.cartItem({{ $product->id }});
             if (ci) { this.selPortion = ci.portion; }
             const rect = anchorEl.getBoundingClientRect();
             const width = 200;
             const left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8)) + window.scrollX;
             const top = rect.bottom + window.scrollY + 6;
             this.menuStyle = `position:absolute; top:${top}px; left:${left}px; width:${width}px;`;
             this.portionOpen = true;
         },
     }"
     @else
     x-data
     @endif>
    {{-- the heart needs to be its own clickable element, so the image link can't wrap it too
         (a <button> nested inside an <a> is invalid HTML — same reason the weight picker below
         lives outside the link once it becomes interactive) --}}
    <div class="relative rounded-xl overflow-hidden aspect-square bg-cream/60">
        <a href="{{ route('products.show', $product) }}" class="absolute inset-0 z-0" aria-label="View {{ $product->name }}">
            <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" loading="lazy"
                 class="absolute inset-0 w-full h-full object-cover {{ $product->is_out_of_stock ? 'grayscale opacity-60' : '' }}" style="object-position: {{ $product->image_position ?? '50% 50%' }};">
        </a>
        @if ($product->is_out_of_stock)
            <div class="absolute inset-0 z-[5] bg-maroon-950/35 flex items-center justify-center pointer-events-none">
                <span class="bg-maroon-900 text-cream text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full shadow-lg border border-white/20">
                    {{ __('Out of Stock') }}
                </span>
            </div>
        @elseif ($product->hasDiscount())
            <span class="absolute top-1.5 left-1.5 z-10 bg-maroon-700 text-cream text-[10px] font-bold px-1.5 py-0.5 rounded-md shadow">
                {{ $product->discountBadgeLabel() }}
            </span>
        @endif
        <button type="button" @click="toggleFavorite({{ $product->id }})" @dblclick.stop aria-label="Save {{ $product->name }} to favourites"
                class="absolute top-1.5 right-1.5 z-10 w-7 h-7 rounded-full bg-white/85 hover:bg-white flex items-center justify-center shadow-sm transition">
            <svg class="w-3.5 h-3.5 transition" :class="isFavorited({{ $product->id }}) ? 'text-maroon-600' : 'text-maroon-300'"
                 :fill="isFavorited({{ $product->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
            </svg>
        </button>
    </div>

    <a href="{{ route('products.show', $product) }}" class="block">
        {{-- min-h reserves 2 lines' worth of height even when the name is short — line-clamp-2
             only caps the max, it doesn't guarantee a matching minimum, so a 1-line name would
             otherwise leave its card shorter than a 2-line name right next to it in the grid. --}}
        <p class="mt-2.5 min-h-[2.5rem] text-sm font-semibold text-maroon-800 leading-snug line-clamp-2 break-words">{{ $product->name }}</p>
    </a>

    {{-- weight lives outside the <a> once it becomes interactive — a <button> nested inside a
         link is invalid HTML and would fight the link's own navigation on click --}}
    @if ($hasMultiplePortions)
        <button type="button" @click="portionOpen ? (portionOpen = false) : openPicker($el)"
                class="flex items-center gap-0.5 text-xs font-semibold px-1.5 py-0.5 -ml-1.5 mt-0.5 rounded-md transition self-start"
                :class="portionOpen ? 'text-maroon-800 bg-gold-50' : 'text-maroon-400 hover:text-maroon-700'">
            <span x-text="window.portionLabel(selPortion)"></span>
            <svg class="w-3 h-3 transition-transform duration-200" :class="portionOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
    @elseif ($miniWeight)
        <p class="text-xs text-maroon-400 mt-0.5">{{ $miniWeight }}</p>
    @endif

    {{-- always shown, "0 (0)" and all, for an unrated product — matches how every other product
         on the site displays its rating, and keeps every card's structure identical so grid
         stretch + mt-auto (see product-card-mini's h-full) line the price rows up cleanly. --}}
    <p class="flex items-center gap-1 mt-1 text-[11px] text-maroon-500">
        <span class="text-gold-500 leading-none">★</span>
        <span class="font-semibold">{{ $miniAvg ?? '0' }}</span>
        <span class="text-maroon-300">({{ $product->reviews_count }})</span>
    </p>

    @if ($hasMultiplePortions)
        {{-- teleported so the card's overflow-hidden image corners never clip the popover --}}
        <template x-teleport="body">
            <div x-show="portionOpen" x-cloak @click.outside="portionOpen = false"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 :style="menuStyle"
                 class="z-[60] rounded-xl border border-gold-200/70 bg-white shadow-xl p-2.5">
                <p class="text-[10px] font-semibold text-maroon-500 uppercase tracking-wide mb-1.5">{{ __('Select Weight') }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (collect($product->portions)->sort()->values() as $grams)
                        <button type="button"
                                @click="selPortion = {{ $grams }}; addProductToCart({{ $product->id }}, 1, false, selPortion).then(() => portionOpen = false)"
                                class="text-xs font-semibold px-2.5 py-1.5 rounded-full border-2 transition"
                                :class="selPortion === {{ $grams }} ? 'bg-maroon-700 border-maroon-700 text-cream' : 'bg-white border-gold-300/70 text-maroon-700 hover:border-maroon-400'">
                            {{ \App\Models\Product::portionLabel($grams) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </template>
    @endif

    <div class="flex items-center justify-between gap-2 mt-2 pt-1 mt-auto">
        <p class="flex items-baseline gap-1 flex-wrap min-w-0">
            @if ($hasMultiplePortions)
                <template x-if="hasDiscount">
                    <span class="text-[11px] text-maroon-300 line-through">₹<span x-text="selectedOriginalPrice().toLocaleString('en-IN')"></span></span>
                </template>
                <span class="font-display font-bold text-base text-maroon-800">₹<span x-text="selectedPrice().toLocaleString('en-IN')"></span></span>
            @else
                @if ($product->hasDiscount())
                    <span class="text-[11px] text-maroon-300 line-through">₹{{ number_format($product->originalPriceForPortion($miniPortion)) }}</span>
                @endif
                <span class="font-display font-bold text-base text-maroon-800">₹{{ number_format($product->priceForPortion($miniPortion)) }}</span>
            @endif
        </p>

        @if ($product->is_out_of_stock)
            <span class="text-[10px] font-bold uppercase tracking-wide text-red-600 bg-red-50 border border-red-200 rounded-full px-2.5 py-1.5 shrink-0 whitespace-nowrap">
                {{ __('Out of Stock') }}
            </span>
        @else
        <template x-if="cartQty({{ $product->id }}) === 0">
            {{-- multi-portion loose products: the round add button opens the same weight picker
                 as the chevron above (Amazon/Blinkit-style) instead of silently adding whatever
                 portion happened to be selected — a first-time shopper never chose a size yet. --}}
            <button type="button"
                    @click="{{ $hasMultiplePortions ? 'openPicker($el)' : "addProductToCart({$product->id}, 1, false, " . ($miniPortion ?? 'null') . ')' }}"
                    aria-label="Add {{ $product->name }} to cart"
                    class="w-8 h-8 rounded-full bg-maroon-800 hover:bg-maroon-700 text-cream flex items-center justify-center shrink-0 shadow transition hover:scale-105">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </template>
        <template x-if="cartQty({{ $product->id }}) > 0">
            <div class="flex items-center h-8 rounded-full bg-maroon-800 text-cream shrink-0 overflow-hidden animate-fab-bump">
                <button type="button" @click="stepCartQty({{ $product->id }}, -1)" class="w-7 h-full flex items-center justify-center hover:bg-maroon-700 transition" aria-label="Decrease quantity">−</button>
                <span class="w-5 text-center text-xs font-bold tabular-nums" x-text="cartQty({{ $product->id }})"></span>
                <button type="button" @click="stepCartQty({{ $product->id }}, 1)" class="w-7 h-full flex items-center justify-center hover:bg-maroon-700 transition" aria-label="Increase quantity">+</button>
            </div>
        </template>
        @endif
    </div>
</div>
