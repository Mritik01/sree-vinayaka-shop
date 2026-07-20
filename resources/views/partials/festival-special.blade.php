{{-- Festival Special — a curated homepage section (admin picks products at /admin/festival-special,
     mirroring the Bestsellers curation pattern).

     v3: v2 used the same dark maroon→gold gradient as promo-banner.blade.php right below it on the
     page, with no gap between them — the two dark blocks fused into one confusing mass (a real bug,
     not just a style nitpick). This version keeps the maroon+gold DNA but inverts it — a warm
     cream/gold section with maroon accents — so it reads as its own moment and gives the dark promo
     banner right after it something to contrast against, instead of competing with it. --}}
@php
    if (!function_exists('scallopFramePath')) {
    function scallopFramePath(float $size, int $bumps, float $bumpDepth, float $squareness = 4): string
    {
        $c = $size / 2;
        $steps = $bumps * 10;
        $points = [];
        for ($i = 0; $i <= $steps; $i++) {
            $theta = ($i / $steps) * 2 * M_PI;
            $cosT = cos($theta);
            $sinT = sin($theta);
            $base = $c / (pow(abs($cosT), $squareness) + pow(abs($sinT), $squareness)) ** (1 / $squareness);
            $r = $base + $bumpDepth * cos($bumps * $theta);
            $points[] = round($c + $r * $cosT, 2) . ',' . round($c + $r * $sinT, 2);
        }

        return 'M ' . implode(' L ', $points) . ' Z';
    }
    }

    $festivalOuter = scallopFramePath(100, 16, 2.4);
    $festivalInner = scallopFramePath(100, 16, 1.8, 4.2);
@endphp

@if ($festivalProducts->isNotEmpty())
    <section class="relative mt-6 sm:mt-10 py-12 sm:py-20 overflow-hidden bg-gradient-to-b from-gold-50 via-cream to-cream"
              x-data="festivalSpecialCarousel()">

        {{-- ambient glow blobs, echoing the bestsellers/shop-by-range sections above --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-16 left-[8%] w-72 h-72 rounded-full bg-gold-400/15 blur-3xl"></div>
            <div class="absolute bottom-0 right-[6%] w-80 h-80 rounded-full bg-maroon-400/10 blur-3xl"></div>
        </div>

        {{-- draped maroon fairy-light strand across the top, echoing the bestsellers canopy above --}}
        <div class="pointer-events-none absolute top-0 inset-x-0 h-14 sm:h-16 z-0">
            <svg class="absolute inset-x-0 w-full h-10" style="top: 2px" viewBox="0 0 1200 40" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 16 Q 75 30 150 16 T 300 16 T 450 16 T 600 16 T 750 16 T 900 16 T 1050 16 T 1200 16"
                      fill="none" stroke="#7a162250" stroke-width="1.5" />
            </svg>
            <div class="absolute inset-x-0 flex justify-around px-2 sm:px-6" style="top: 12px">
                @for ($i = 0; $i < 26; $i++)
                    <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full {{ $i % 2 === 1 ? 'hidden sm:inline-block' : '' }} {{ $i % 3 === 2 ? 'bg-gold-400' : 'bg-maroon-600' }} {{ $i % 3 === 2 ? 'animate-bulb-glow' : 'animate-bulb-glow-maroon' }}"
                          style="animation-delay: {{ $i * 0.12 }}s"></span>
                @endfor
            </div>
        </div>

        {{-- hanging corner lamps, a touch heavier than the strand bulbs --}}
        <div class="pointer-events-none absolute top-0 left-5 sm:left-10 flex flex-col items-center z-10">
            <span class="w-px h-9 sm:h-11 bg-maroon-400/50"></span>
            <span class="w-3.5 h-3.5 sm:w-4 sm:h-4 rounded-full bg-maroon-600 animate-bulb-glow-maroon"></span>
        </div>
        <div class="pointer-events-none absolute top-0 right-5 sm:right-10 flex flex-col items-center z-10">
            <span class="w-px h-9 sm:h-11 bg-maroon-400/50"></span>
            <span class="w-3.5 h-3.5 sm:w-4 sm:h-4 rounded-full bg-maroon-600 animate-bulb-glow-maroon" style="animation-delay: 1.1s"></span>
        </div>

        <div class="relative max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12">
            <p class="text-center text-gold-600 tracking-[0.3em] uppercase text-xs font-semibold mb-2">✦ {{ __('Limited Time') }} ✦</p>
            <h2 class="section-heading">{{ __('Festival Special') }}</h2>
            <p class="text-center text-maroon-500 mt-3 mb-8 sm:mb-14 max-w-xl mx-auto">{{ __('Our festival special treats will instantly sweeten your day!') }}</p>

            <p class="sm:hidden text-center text-[11px] uppercase tracking-[0.2em] text-gold-600/80 mb-6 animate-pulse">← {{ __('swipe to explore') }} →</p>

            {{-- always a horizontally-scrolling row, never wraps to new rows as the admin adds more
                 products — auto-advances via festivalSpecialCarousel() (see app.js), pausing on
                 hover/touch so it doesn't fight a customer who's actively swiping through --}}
            <div x-ref="track" @mouseenter="paused = true" @mouseleave="paused = false"
                 @touchstart="paused = true" @touchend="paused = false"
                 class="flex overflow-x-auto snap-x snap-mandatory no-scrollbar gap-5 -mx-4 px-4 pb-4 sm:-mx-8 sm:px-8 lg:-mx-12 lg:px-12">
                @foreach ($festivalProducts as $product)
                    <div x-data="{ added: false }"
                         class="group relative flex flex-col items-center text-center shrink-0 snap-center w-40 sm:w-44
                                transition-all duration-700 ease-out"
                         :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
                         style="transition-delay: {{ $loop->index * 90 }}ms">

                        {{-- scalloped sticker frame, photo inset within it — maroon/gold on white, matching the rest of the page --}}
                        <a href="{{ route('products.show', $product) }}" aria-label="View {{ $product->name }}"
                           class="relative block w-36 h-36 sm:w-40 sm:h-40 transition-transform duration-300 ease-out group-hover:-translate-y-1.5 group-active:scale-95">
                            <svg viewBox="0 0 100 100" class="absolute inset-0 w-full h-full drop-shadow-md">
                                <path d="{{ $festivalOuter }}" fill="none" stroke="#c8962e" stroke-width="1.8" />
                                <path d="{{ $festivalInner }}" fill="#ffffff" stroke="#e9c873" stroke-width="0.9" />
                            </svg>
                            <div class="absolute inset-[16%] rounded-full overflow-hidden ring-2 ring-gold-400/70">
                                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" loading="lazy"
                                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-110">
                            </div>
                            {{-- finial, matching the shop-by-range arch motif for a consistent brand thread --}}
                            <span class="absolute -top-1 left-1/2 -translate-x-1/2 w-2.5 h-2.5 rounded-full bg-gold-300 border-2 border-gold-500 shadow z-10"></span>
                        </a>

                        <a href="{{ route('products.show', $product) }}" class="block min-h-[2.5rem] leading-snug line-clamp-2 font-display font-semibold text-maroon-800 mt-3.5 hover:text-gold-600 transition">{{ $product->name }}</a>

                        <span class="inline-block mt-2.5 text-[11px] font-semibold px-3 py-1 rounded-full border border-gold-300/60 text-maroon-600">{{ $product->weight }}</span>

                        <button type="button"
                                @click="addProductToCart({{ $product->id }}); added = true; setTimeout(() => added = false, 2000)"
                                class="btn-gold mt-3.5 !py-2 !px-5 text-sm min-w-[128px]">
                            <span x-show="!added">{{ __('Add To Cart') }}</span>
                            <span x-show="added" x-cloak>{{ __('Added') }} ✓</span>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
