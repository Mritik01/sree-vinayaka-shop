@extends('layouts.app')

@section('title', $product->name.' — Shri Vinayak Family Shop')

@section('content')
@php
    $hex = ltrim($product->color, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    $onColor = $luminance > 0.55 ? '#3a0b12' : '#fdf6e9';
@endphp

<div x-data="productPage({{ Auth::check() ? 'true' : 'false' }}, {{ $isFavorited ? 'true' : 'false' }}, {{ $product->id }}, {{ $product->isLoose() ? 'true' : 'false' }}, {{ $product->discountedBasePrice() }}, {{ Illuminate\Support\Js::from($product->portions ?? []) }}, {{ $product->hasDiscount() ? 'true' : 'false' }}, {{ $product->price }}, {{ Illuminate\Support\Js::from($product->discount_type) }}, {{ (int) $product->discount_value }}, {{ Illuminate\Support\Js::from($product->portion_prices ?? (object) []) }}, {{ Illuminate\Support\Js::from(collect($product->portion_images ?? [])->mapWithKeys(fn ($path, $grams) => [(string) $grams => asset($path)])->all()) }}, {{ Illuminate\Support\Js::from($product->unit) }}, {{ Illuminate\Support\Js::from(asset($product->image)) }})">

    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 pt-6">
        <nav class="animate-rise-in text-sm text-maroon-500 flex items-center gap-2 flex-wrap">
            <a href="/" class="hover:text-gold-600 transition">{{ __('Home') }}</a>
            <span class="text-gold-400">✦</span>
            <a href="/#range" class="hover:text-gold-600 transition">{{ __($product->category) }}</a>
            <span class="text-gold-400">✦</span>
            <span class="text-maroon-700 font-medium">{{ $product->name }}</span>
        </nav>
    </div>

    <section class="relative pb-16">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 py-8 sm:py-10 grid lg:grid-cols-2 gap-10 lg:gap-16 items-start">
            {{-- image column — a thumbnail rail sits left of the main photo, ready for more photos later; today there's only one real image so it shows as the single (active) thumbnail rather than faking extra ones --}}
            <div>
                <div class="animate-rise-in flex items-start gap-4 sm:gap-8 lg:gap-12" style="animation-delay: .05s">
                    <div class="hidden sm:flex flex-col gap-3 shrink-0">
                        <button type="button" aria-label="{{ $product->name }} photo" aria-current="true"
                            class="w-16 h-16 md:w-20 md:h-20 rounded-xl overflow-hidden border-2 border-maroon-700 shadow-sm shrink-0">
                            <img src="{{ asset($product->imageForPortion($product->defaultPortion())) }}" alt="" :src="selectedImage()" style="object-position: {{ $product->image_position ?? '50% 50%' }};" class="w-full h-full object-cover">
                        </button>
                    </div>

                    <div class="relative rounded-2xl overflow-hidden aspect-square shadow-md border border-gold-200/60 w-full sm:w-[400px] md:w-[450px] shrink-0">
                        <img src="{{ asset($product->imageForPortion($product->defaultPortion())) }}" alt="{{ $product->name }}" :src="selectedImage()" style="object-position: {{ $product->image_position ?? '50% 50%' }};"
                             class="absolute inset-0 w-full h-full object-cover transition duration-500 hover:scale-105 {{ $product->is_out_of_stock ? 'grayscale opacity-60' : '' }}">
                        @if ($product->is_out_of_stock)
                            <div class="absolute inset-0 z-[5] bg-maroon-950/35 flex items-center justify-center pointer-events-none">
                                <span class="bg-maroon-900 text-cream text-sm font-bold uppercase tracking-wider px-4 py-2 rounded-full shadow-lg border border-white/20">
                                    {{ __('Out of Stock') }}
                                </span>
                            </div>
                        @elseif ($product->hasDiscount())
                            <span class="absolute top-4 left-4 text-sm font-bold uppercase tracking-wide px-3 py-1.5 rounded-full bg-red-600 text-white shadow-md">
                                {{ $product->discountBadgeLabel() }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- desktop-only "Product Details" accordion, directly under the photo; the
                     mobile equivalent lives further down, swapped in for the trust-badges strip --}}
                @if ($product->description)
                    <div class="hidden lg:block animate-rise-in mt-5" style="animation-delay: .1s">
                        @include('partials.product-details-accordion', ['description' => $product->description])
                    </div>
                @endif
            </div>

            {{-- details column --}}
            <div class="flex flex-col max-w-md">
                <div class="animate-rise-in flex items-start justify-between gap-4" style="animation-delay: .1s">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.2em] uppercase text-gold-600">{{ __($product->category) }}</p>
                        <h1 class="font-display font-bold text-2xl sm:text-4xl md:text-5xl text-maroon-800 mt-1 leading-tight">
                            {{ $product->name }}
                        </h1>
                    </div>

                    <button @click="toggleFavorite()" aria-label="Save {{ $product->name }} to favourites"
                        class="shrink-0 w-11 h-11 rounded-full border border-gold-300/60 bg-white hover:bg-gold-50 flex items-center justify-center shadow-sm transition">
                        <svg class="w-5 h-5 transition" :class="[favorited ? 'text-maroon-600' : 'text-maroon-300', justFavorited ? 'animate-heart-pop' : '']"
                             :fill="favorited ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                    </button>
                </div>

                <div class="animate-rise-in mt-2" style="animation-delay: .12s"
                     x-data="{ average: {{ $averageRating }}, count: {{ $reviewsCount }} }"
                     @review-submitted.window="average = $event.detail.average; count = $event.detail.count">
                    <a href="#reviews" class="flex items-center gap-1.5 w-fit hover:opacity-80 transition">
                        <span class="flex gap-0.5">
                            <template x-for="i in 5" :key="i">
                                <svg class="w-4 h-4" :class="i <= Math.round(average) ? 'text-gold-500' : 'text-gold-200'" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                                </svg>
                            </template>
                        </span>
                        <span class="text-sm text-maroon-500" x-text="count > 0 ? average.toFixed(1) + ' · ' + count + (count === 1 ? ' review' : ' reviews') : 'No reviews yet'"></span>
                    </a>
                </div>

                <p class="animate-rise-in flex items-baseline gap-2.5 mt-4" style="animation-delay: .15s">
                    <span x-show="hasDiscount" x-cloak class="text-xl text-maroon-300 line-through">₹<span x-text="originalUnitPrice()"></span></span>
                    <span class="font-display font-bold text-3xl" style="color: {{ $product->color }};">₹ <span x-text="unitPrice()"></span></span>
                </p>

                <div class="animate-rise-in border-t border-gold-200/70 mt-4 pt-4 flex flex-wrap items-center gap-x-6 gap-y-2" style="animation-delay: .2s">
                    <span class="text-sm text-maroon-600 flex items-center gap-1.5">🌿 {{ __('Fresh & Preservative-Free') }}</span>
                    <span class="text-sm text-maroon-600 flex items-center gap-1.5">❤️ {{ __("Siswa Bazar's Favourite") }}</span>
                </div>

                {{-- real order data, not a canned line — see Product::recentOrderCount(). Hidden
                     entirely below a small threshold so a new/rarely-ordered product doesn't read
                     as "1 person ordered this," which undersells rather than builds trust --}}
                @if ($recentOrderCount >= 3)
                    <p class="animate-rise-in text-sm font-medium text-maroon-700 flex items-center gap-1.5 mt-2.5" style="animation-delay: .22s">
                        🔥 {{ __(':count people ordered this in the last week', ['count' => $recentOrderCount]) }}
                    </p>
                @endif

                @if ($product->description)
                    <p class="animate-rise-in text-maroon-600/90 leading-relaxed mt-5" style="animation-delay: .25s">
                        {{ $product->description }}
                    </p>
                @endif

                @if ($product->isLoose())
                    <div class="animate-rise-in mt-7" style="animation-delay: .3s">
                        <p class="text-sm font-semibold text-maroon-800 mb-2">{{ __('Select Weight') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="opt in portionOptions" :key="opt.grams">
                                <button type="button" @click="selectedPortion = opt.grams"
                                    class="text-sm font-semibold px-5 py-2 rounded-full border-2 transition"
                                    :class="selectedPortion === opt.grams ? 'bg-maroon-700 border-maroon-700 text-cream' : 'border-gold-300/60 text-maroon-700 hover:border-maroon-400'"
                                    x-text="opt.label"></button>
                            </template>
                        </div>
                    </div>
                @else
                    <div class="animate-rise-in mt-7" style="animation-delay: .3s">
                        <p class="text-sm font-semibold text-maroon-800 mb-2">{{ __('Size') }}</p>
                        <span class="inline-block text-sm font-semibold px-5 py-2 rounded-full bg-maroon-700 text-cream">{{ $product->weight }}</span>
                    </div>
                @endif

                <div class="animate-rise-in mt-6" style="animation-delay: .35s">
                    @if ($product->is_out_of_stock)
                        <div class="rounded-2xl bg-red-50 border border-red-200 px-5 py-4 flex items-center gap-3">
                            <span class="text-2xl">🚫</span>
                            <div>
                                <p class="text-sm font-bold text-red-700">{{ __('Out of Stock') }}</p>
                                <p class="text-xs text-red-500 mt-0.5">{{ __("This item isn't available right now — check back soon.") }}</p>
                            </div>
                        </div>
                    @else
                    {{-- "Select Quantity" only makes sense before the first Add to Cart — it
                         picks how many go in on that click. Once the product is in the cart, the
                         picker has nothing left to feed (the Add to Cart button is gone), so it's
                         replaced entirely by the live "− N +" stepper used on product cards
                         sitewide (see product-card-mini.blade.php), driven by the same
                         cartQty()/stepCartQty() helpers in app.js — reads live off the shared
                         cart store, and IS the quantity control from that point on. --}}
                    <template x-if="cartQty({{ $product->id }}) === 0">
                        <div>
                            <p class="text-sm font-semibold text-maroon-800 mb-2">{{ __('Select Quantity') }}</p>
                            <div class="flex items-stretch gap-3">
                                <div class="flex items-center gap-3 bg-white rounded-full border border-gold-300/60 px-3 shadow-sm">
                                    <button @click="decrement()" aria-label="Decrease quantity" class="w-8 h-8 rounded-full hover:bg-gold-50 text-maroon-700 font-bold text-lg transition">−</button>
                                    <span class="w-6 text-center font-semibold text-maroon-800" x-text="quantity"></span>
                                    <button @click="increment()" aria-label="Increase quantity" class="w-8 h-8 rounded-full hover:bg-gold-50 text-maroon-700 font-bold text-lg transition">+</button>
                                </div>
                                <button type="button" @click="addToCart()" :disabled="addingToCart"
                                   class="flex-1 inline-flex items-center justify-center gap-2 text-sm font-bold rounded-full border-2 border-maroon-700 text-maroon-700 hover:bg-maroon-700 hover:text-cream transition duration-200 disabled:opacity-60">
                                    <span x-show="!justAddedToCart">{{ __('Add to Cart') }}</span>
                                    <span x-show="justAddedToCart" x-cloak>{{ __('Added to Cart') }} 🛒</span>
                                </button>
                            </div>
                        </div>
                    </template>
                    <template x-if="cartQty({{ $product->id }}) > 0">
                        <div class="inline-flex items-center justify-between gap-1 rounded-full bg-maroon-700 text-cream px-1 shadow">
                            <button type="button" @click="stepCartQty({{ $product->id }}, -1)" aria-label="Decrease quantity" class="w-10 h-10 rounded-full hover:bg-maroon-600 text-lg font-bold transition flex items-center justify-center">−</button>
                            <span class="font-bold text-base tabular-nums px-2" x-text="cartQty({{ $product->id }})"></span>
                            <button type="button" @click="stepCartQty({{ $product->id }}, 1)" aria-label="Increase quantity" class="w-10 h-10 rounded-full hover:bg-maroon-600 text-lg font-bold transition flex items-center justify-center">+</button>
                        </div>
                    </template>

                    <a href="/cart" class="inline-block mt-3 text-sm text-maroon-500 hover:text-gold-600 underline underline-offset-2 transition">
                        {{ __('Go to cart & place your order — Cash on Delivery') }}
                    </a>
                    @endif
                </div>

                <div class="animate-rise-in mt-6 rounded-full text-center py-3.5 px-6 font-semibold text-sm sm:text-base shadow-sm"
                     style="animation-delay: .4s; background: linear-gradient(120deg, rgb(var(--color-gold-600)), rgb(var(--color-gold-500))); color: rgb(var(--color-cream));">
                    🛵 {{ __('Hyperlocal Delivery Available in Siswa Bazar') }}
                </div>

                <div class="animate-rise-in mt-6 rounded-2xl bg-gold-50/70 border border-gold-200/60 px-6 py-4 flex items-center gap-6" style="animation-delay: .45s">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">⏱️</span>
                        <p class="text-sm text-maroon-600 leading-snug">{{ __('Best enjoyed') }}<br>{{ __('fresh, within 2 days.') }}</p>
                    </div>
                    <div class="w-px self-stretch bg-gold-300/60"></div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">❄️</span>
                        <p class="text-sm text-maroon-600 leading-snug">{{ __('Store in a') }}<br>{{ __('cool, dry place.') }}</p>
                    </div>
                </div>

                {{-- on mobile, a "Product Details" accordion takes this spot instead — the icon
                     strip stays put on desktop either way (it sits below the separate accordion
                     already placed under the photo there, so nothing's lost) --}}
                <div class="animate-rise-in grid grid-cols-2 sm:grid-cols-4 gap-3 mt-8 pt-6 border-t border-gold-200/60 {{ $product->description ? 'hidden lg:grid' : '' }}" style="animation-delay: .5s">
                    @foreach ([['🌿','100% Fresh Daily'], ['🧼','Hygienic Packing'], ['🙏','Pure Ingredients'], ['❤️','Loved in Siswa Bazar']] as [$emoji, $label])
                        <div class="text-center">
                            <p class="text-2xl">{{ $emoji }}</p>
                            <p class="text-xs text-maroon-500 mt-1 leading-tight">{{ __($label) }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($product->description)
                    <div class="lg:hidden animate-rise-in mt-8" style="animation-delay: .5s">
                        @include('partials.product-details-accordion', ['description' => $product->description])
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- real co-purchase data (see Product::frequentlyBoughtWith()), not a same-category guess —
         shown ahead of "You Might Also Like" since it's a stronger, behaviour-backed signal --}}
    @if ($frequentlyBoughtWith->isNotEmpty())
        <section class="relative bg-white py-16 overflow-hidden border-b border-gold-100">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6">
                <h2 class="section-heading">{{ __('Frequently Bought Together') }}</h2>
                <p class="text-center text-maroon-500 mt-3 mb-10">{{ __('Customers who bought :name usually add these too.', ['name' => $product->name]) }}</p>

                <div x-data="favoritesList({{ Auth::check() ? 'true' : 'false' }}, @json($favoritedIds))"
                     class="flex flex-wrap justify-center gap-4 sm:gap-6">
                    @foreach ($frequentlyBoughtWith as $index => $item)
                        <div class="w-40 sm:w-48 lg:w-56 shrink-0 animate-rise-in" style="animation-delay: {{ 0.1 * $index }}s">
                            @include('partials.product-card-mini', ['product' => $item, 'fixedWidth' => false])
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="relative bg-ivory py-16 overflow-hidden">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6">
                <h2 class="section-heading">{{ __('You Might Also Like') }}</h2>
                <p class="text-center text-maroon-500 mt-3 mb-10">{{ __("More favourites from Siswa Bazar's kitchen.") }}</p>

                <div x-data="favoritesList({{ Auth::check() ? 'true' : 'false' }}, @json($favoritedIds))">
                    {{-- auto-advancing horizontal carousel on every breakpoint (see
                         window.relatedAutoSlider in app.js) — pauses on hover/touch, loops back
                         to the start at the end. Card width scales down on mobile so more than
                         one full card is visible at once. --}}
                    <div class="relative" x-data="relatedAutoSlider(240)" @mouseenter="paused = true" @mouseleave="paused = false" @touchstart="paused = true">
                        <button x-show="canLeft" x-cloak @click="scrollBy(-1)" aria-label="{{ __('Scroll left') }}"
                                class="hidden sm:flex absolute left-1 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white border border-gold-300/60 shadow-md items-center justify-center text-maroon-700 hover:bg-cream transition">
                            &#8249;
                        </button>
                        <div x-ref="track" @scroll.debounce.100ms="update()"
                             class="flex gap-4 sm:gap-6 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                             :class="fits() && 'justify-center'">
                            @foreach ($related as $index => $item)
                                <div class="w-40 sm:w-48 lg:w-56 shrink-0 snap-start animate-rise-in" style="animation-delay: {{ 0.1 * $index }}s">
                                    @include('partials.product-card-mini', ['product' => $item, 'fixedWidth' => false])
                                </div>
                            @endforeach
                        </div>
                        <button x-show="canRight" x-cloak @click="scrollBy(1)" aria-label="{{ __('Scroll right') }}"
                                class="hidden sm:flex absolute right-1 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white border border-gold-300/60 shadow-md items-center justify-center text-maroon-700 hover:bg-cream transition">
                            &#8250;
                        </button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @php
        $reviewsForJs = $reviews->map(fn ($r) => [
            'id' => $r->id,
            'user_id' => $r->user_id,
            'user_name' => $r->user->name,
            'rating' => $r->rating,
            'comment' => $r->comment,
            'photos' => $r->photos ?? [],
            'created_at' => $r->created_at->diffForHumans(),
        ]);
        // sparkle flight paths for the 5-star celebration burst (--tx/--ty consumed by .animate-sparkle-burst)
        $sparkles = [
            ['tx' => '-78px', 'ty' => '-50px', 'emoji' => '✨', 'delay' => '0s'],
            ['tx' => '-34px', 'ty' => '-66px', 'emoji' => '🌟', 'delay' => '.05s'],
            ['tx' => '14px',  'ty' => '-72px', 'emoji' => '✨', 'delay' => '.1s'],
            ['tx' => '62px',  'ty' => '-56px', 'emoji' => '💛', 'delay' => '.04s'],
            ['tx' => '98px',  'ty' => '-22px', 'emoji' => '✨', 'delay' => '.12s'],
            ['tx' => '-100px','ty' => '-8px',  'emoji' => '🎉', 'delay' => '.08s'],
            ['tx' => '-58px', 'ty' => '32px',  'emoji' => '✨', 'delay' => '.15s'],
            ['tx' => '48px',  'ty' => '36px',  'emoji' => '🌟', 'delay' => '.1s'],
            ['tx' => '92px',  'ty' => '16px',  'emoji' => '✨', 'delay' => '.18s'],
            ['tx' => '2px',   'ty' => '-88px', 'emoji' => '👑', 'delay' => '.02s'],
        ];
    @endphp

    <section id="reviews" class="relative py-20 border-t border-gold-200/50 overflow-hidden"
             style="background: linear-gradient(165deg, #fdf6e9 0%, #fbeed6 50%, #f6e3bd 100%);">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-24 -left-16 w-[28rem] h-[28rem] rounded-full bg-gold-400/20 blur-3xl"></div>
            <div class="absolute bottom-0 -right-16 w-[28rem] h-[28rem] rounded-full bg-maroon-400/10 blur-3xl"></div>
            <div class="absolute top-1/3 left-1/2 w-72 h-72 rounded-full bg-pista-400/10 blur-3xl"></div>
        </div>

        <div class="relative max-w-[1600px] mx-auto px-4 sm:px-8 lg:px-12" x-data='reviewsList(@json($reviewsForJs), {{ $averageRating }}, {{ $reviewsCount }})'>
            <div class="grid lg:grid-cols-[400px_1fr] gap-12 lg:gap-20 items-start">

                {{-- left rail: summary + write-a-review, sticky on desktop --}}
                <div class="lg:sticky lg:top-28">
                    <p class="text-xs font-medium tracking-[0.3em] uppercase text-gold-600">{{ __('What people say') }}</p>
                    <h2 class="font-display text-3xl text-maroon-800 mt-2">{{ __('Ratings & Reviews') }}</h2>

                    <div class="flex items-end gap-4 mt-8">
                        <p class="font-display font-light text-7xl leading-none text-maroon-800" x-text="count > 0 ? average.toFixed(1) : '—'"></p>
                        <div class="pb-1.5">
                            <div class="flex gap-1">
                                <template x-for="i in 5" :key="i">
                                    <svg class="w-4 h-4" :class="i <= Math.round(average) ? 'text-gold-500' : 'text-gold-200'" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                                    </svg>
                                </template>
                            </div>
                            <p class="text-sm text-maroon-400 mt-1.5" x-text="count + (count === 1 ? ' rating' : ' ratings')"></p>
                        </div>
                    </div>

                    <div class="space-y-2.5 mt-8">
                        <template x-for="(star, idx) in [5, 4, 3, 2, 1]" :key="star">
                            <div class="flex items-center gap-3">
                                <span class="w-3 text-sm text-maroon-500 text-right shrink-0" x-text="star"></span>
                                <svg class="w-3.5 h-3.5 text-gold-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                                </svg>
                                <div class="flex-1 h-2 rounded-full bg-gold-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-600 transition-all duration-1000 ease-out"
                                         :style="'width: ' + (barsShown ? percentFor(star) : 0) + '%; transition-delay: ' + (idx * 120) + 'ms'"></div>
                                </div>
                                <span class="w-8 text-xs text-maroon-400 text-right shrink-0 tabular-nums" x-text="countFor(star)"></span>
                            </div>
                        </template>
                    </div>

                    {{-- review form --}}
                    <div class="mt-10 pt-8 border-t border-gold-200/60"
                         x-data="reviewForm('{{ $product->slug }}', {{ Auth::check() ? 'true' : 'false' }}, {{ optional($userReview)->rating ?? 0 }}, {{ \Illuminate\Support\Js::from(optional($userReview)->comment ?? '') }})">
                        <p class="font-display text-lg text-maroon-800" x-text="hasReviewed ? 'Update your review' : 'Share your experience'"></p>
                        <p class="text-sm text-maroon-400 mt-1">How was it? Other shoppers want to know.</p>

                        <div class="relative w-fit flex gap-1 mt-5">
                            <template x-for="i in 5" :key="i">
                                <button type="button" @click="setRating(i)" @mouseenter="hoverRating = i" @mouseleave="hoverRating = 0"
                                    :aria-label="'Rate ' + i + ' out of 5'"
                                    class="transition transform duration-150" :class="[(hoverRating || rating) >= i ? 'scale-110' : '', justRatedIndex === i ? 'animate-star-select' : '']">
                                    <svg class="w-9 h-9 transition-colors duration-150" :class="(hoverRating || rating) >= i ? 'text-gold-500' : 'text-gold-200'" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                                    </svg>
                                </button>
                            </template>

                            {{-- 5-star celebration burst --}}
                            <template x-if="showBurst">
                                <div class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                    @foreach ($sparkles as $sparkle)
                                        <span class="absolute text-lg animate-sparkle-burst"
                                              style="--tx: {{ $sparkle['tx'] }}; --ty: {{ $sparkle['ty'] }}; animation-delay: {{ $sparkle['delay'] }};">{{ $sparkle['emoji'] }}</span>
                                    @endforeach
                                </div>
                            </template>
                        </div>

                        <textarea x-model="comment" rows="3" maxlength="1000" placeholder="Share your experience (optional)"
                            class="w-full mt-4 rounded-xl bg-white border border-gold-300/60 px-4 py-3 text-base sm:text-sm text-maroon-700 placeholder-maroon-300 focus:outline-none focus:ring-2 focus:ring-gold-400"></textarea>

                        {{-- photo attachments --}}
                        <div class="flex flex-wrap gap-3 mt-4">
                            <template x-for="(src, index) in photoPreviews" :key="src">
                                <div class="relative w-20 h-20 rounded-xl overflow-hidden border border-gold-300/60">
                                    <img :src="src" alt="" class="w-full h-full object-cover">
                                    <button type="button" @click="removePhoto(index)" aria-label="Remove photo"
                                        class="absolute top-1 right-1 w-5 h-5 rounded-full bg-maroon-900/70 hover:bg-maroon-900 text-cream text-xs leading-none flex items-center justify-center">×</button>
                                </div>
                            </template>

                            <label x-show="photoPreviews.length < 3"
                                class="w-20 h-20 rounded-xl border-2 border-dashed border-gold-300/80 hover:border-gold-500 hover:bg-gold-50 cursor-pointer flex flex-col items-center justify-center gap-1 text-gold-600 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                                </svg>
                                <span class="text-[10px] font-medium">{{ __('Add photo') }}</span>
                                <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="addPhotos($event)">
                            </label>
                        </div>
                        <p class="text-xs text-maroon-300 mt-2">{{ __('Up to 3 photos, 2 MB each.') }}</p>

                        <p x-show="error" x-cloak x-text="error" class="text-sm text-maroon-600 mt-2"></p>

                        <button @click="submit()" :disabled="submitting" type="button"
                            class="btn-gold mt-5 text-sm px-6 py-2.5 disabled:opacity-60 disabled:cursor-not-allowed">
                            <span x-show="!submitting" x-text="hasReviewed ? 'Update Review' : 'Submit Review'"></span>
                            <span x-show="submitting" x-cloak>Submitting…</span>
                        </button>

                        <p x-show="justSubmitted" x-cloak x-transition class="text-sm text-pista-600 mt-3">🎉 Thank you for your review!</p>
                    </div>
                </div>

                {{-- right: the reviews themselves --}}
                <div class="divide-y divide-gold-200/50 lg:border-l lg:border-gold-200/50 lg:pl-16">
                    <template x-if="reviews.length === 0">
                        <div class="py-16 text-center">
                            <p class="text-4xl mb-3">🪔</p>
                            <p class="font-display text-lg text-maroon-700">{{ __('No reviews yet') }}</p>
                            <p class="text-sm text-maroon-400 mt-1">{{ __('Be the first to share your experience.') }}</p>
                        </div>
                    </template>

                    <template x-for="review in reviews" :key="review.id">
                        <article class="py-7 first:pt-0 last:pb-0"
                             x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gold-200 to-cream border border-gold-400/60 flex items-center justify-center font-display text-maroon-700 shrink-0"
                                         x-text="review.user_name.charAt(0).toUpperCase()"></div>
                                    <div class="min-w-0">
                                        <p class="text-maroon-800 font-medium truncate" x-text="review.user_name"></p>
                                        <p class="text-xs text-maroon-400" x-text="review.created_at"></p>
                                    </div>
                                </div>
                                <div class="flex gap-0.5 shrink-0">
                                    <template x-for="i in 5" :key="i">
                                        <svg class="w-4 h-4" :class="i <= review.rating ? 'text-gold-500' : 'text-gold-200'" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.2 1.3 6-5.4-3-5.4 3 1.3-6L1.3 7.7l6.1-.6z"/>
                                        </svg>
                                    </template>
                                </div>
                            </div>

                            <p class="text-[15px] text-maroon-600/90 mt-3.5 leading-relaxed" x-text="review.comment" x-show="review.comment"></p>

                            {{-- attached photos --}}
                            <div class="flex flex-wrap gap-2.5 mt-4" x-show="review.photos && review.photos.length">
                                <template x-for="photo in review.photos" :key="photo">
                                    <button type="button" @click="lightbox = photo" :aria-label="'View photo from ' + review.user_name"
                                        class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden border border-gold-200/70 hover:border-gold-400 hover:shadow-md transition">
                                        <img :src="photo" alt="" loading="lazy" class="w-full h-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </article>
                    </template>
                </div>
            </div>

            {{-- photo lightbox --}}
            <div x-show="lightbox" x-cloak x-transition.opacity.duration.200ms
                 @click="lightbox = null" @keydown.escape.window="lightbox = null"
                 class="fixed inset-0 z-50 bg-maroon-900/85 backdrop-blur-sm flex items-center justify-center p-6">
                <img :src="lightbox" alt="Review photo" class="max-w-full max-h-full rounded-2xl shadow-2xl" @click.stop>
                <button type="button" @click="lightbox = null" aria-label="Close photo"
                    class="absolute top-5 right-6 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-cream text-2xl leading-none flex items-center justify-center transition">×</button>
            </div>
        </div>
    </section>

    {{-- sticky mobile order bar --}}
    <div x-show="showStickyBar" x-cloak
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-full" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-full"
         class="fixed bottom-16 inset-x-0 z-40 md:hidden bg-white/95 backdrop-blur border-t border-gold-300/50 shadow-2xl px-4 py-3 flex items-center justify-between gap-3">
        @if ($product->is_out_of_stock)
            <p class="text-sm font-bold text-red-600 flex items-center gap-1.5">🚫 {{ __('Out of Stock') }}</p>
        @else
        <div>
            <p class="text-[10px] text-maroon-400 uppercase tracking-wide">{{ __('Total') }}</p>
            {{-- before it's in the cart, this previews "Select Quantity" × price; once it's in
                 the cart, "Select Quantity" is gone (see the stepper above), so this switches to
                 tracking the live cart quantity instead, same source as the stepper's own count --}}
            <p class="font-display font-bold text-lg" style="color: {{ $product->color }};">₹<span x-text="(cartQty({{ $product->id }}) > 0 ? cartQty({{ $product->id }}) : quantity) * unitPrice()"></span></p>
        </div>
        <template x-if="cartQty({{ $product->id }}) === 0">
            <button type="button" @click="addToCart()"
               class="shrink-0 text-center text-sm font-bold px-6 py-2.5 rounded-full shadow"
               style="background-color: {{ $product->color }}; color: {{ $onColor }};">
                <span x-show="!justAddedToCart">{{ __('Add to Cart') }}</span>
                <span x-show="justAddedToCart" x-cloak>{{ __('Added') }} 🛒</span>
            </button>
        </template>
        <template x-if="cartQty({{ $product->id }}) > 0">
            <div class="shrink-0 inline-flex items-center justify-between rounded-full px-1 shadow"
                 style="background-color: {{ $product->color }}; color: {{ $onColor }};">
                <button type="button" @click="stepCartQty({{ $product->id }}, -1)" aria-label="Decrease quantity" class="w-8 h-8 rounded-full hover:opacity-80 text-lg font-bold transition flex items-center justify-center">−</button>
                <span class="font-bold text-sm tabular-nums px-1" x-text="cartQty({{ $product->id }})"></span>
                <button type="button" @click="stepCartQty({{ $product->id }}, 1)" aria-label="Increase quantity" class="w-8 h-8 rounded-full hover:opacity-80 text-lg font-bold transition flex items-center justify-center">+</button>
            </div>
        </template>
        @endif
    </div>
</div>
@endsection
