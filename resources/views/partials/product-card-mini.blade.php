{{-- compact reference-style bestseller card — image, name, weight, rating, price/discount,
     round add button that becomes a stepper once in the cart. Expects $product loaded with
     reviews_avg_rating / reviews_count aggregates (nullable). --}}
@php
    $miniPortion = $product->defaultPortion();
    $miniWeight = $product->isLoose() ? \App\Models\Product::portionLabel($miniPortion) : $product->weight;
    $miniAvg = $product->reviews_avg_rating ? round($product->reviews_avg_rating, 1) : null;
@endphp

<div x-data class="snap-start shrink-0 w-40 sm:w-auto bg-white rounded-2xl border border-gold-200/60 shadow-sm hover:shadow-md transition p-3 flex flex-col">
    <a href="{{ route('products.show', $product) }}" class="block">
        <div class="relative rounded-xl overflow-hidden aspect-square bg-cream/60">
            <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" loading="lazy"
                 class="w-full h-full object-cover" style="object-position: {{ $product->image_position ?? '50% 50%' }};">
            @if ($product->hasDiscount())
                <span class="absolute top-1.5 left-1.5 bg-maroon-700 text-cream text-[10px] font-bold px-1.5 py-0.5 rounded-md shadow">
                    {{ $product->discountBadgeLabel() }}
                </span>
            @endif
        </div>

        <p class="mt-2.5 text-sm font-semibold text-maroon-800 leading-snug line-clamp-2">{{ $product->name }}</p>
        <p class="text-xs text-maroon-400 mt-0.5">{{ $miniWeight }}</p>

        @if ($miniAvg !== null)
            <p class="flex items-center gap-1 mt-1 text-[11px] text-maroon-500">
                <span class="text-gold-500 leading-none">★</span>
                <span class="font-semibold">{{ $miniAvg }}</span>
                <span class="text-maroon-300">({{ $product->reviews_count }})</span>
            </p>
        @endif
    </a>

    <div class="flex items-center justify-between gap-2 mt-2 pt-1 mt-auto">
        <p class="flex items-baseline gap-1 flex-wrap min-w-0">
            @if ($product->hasDiscount())
                <span class="text-[11px] text-maroon-300 line-through">₹{{ number_format($product->originalPriceForPortion($miniPortion)) }}</span>
            @endif
            <span class="font-display font-bold text-base text-maroon-800">₹{{ number_format($product->priceForPortion($miniPortion)) }}</span>
        </p>

        <template x-if="cartQty({{ $product->id }}) === 0">
            <button type="button" @click="addProductToCart({{ $product->id }}, 1, false, {{ $miniPortion ?? 'null' }})"
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
    </div>
</div>
