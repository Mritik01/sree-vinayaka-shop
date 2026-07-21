@php
    // sparkle particle flight paths around the gift centerpiece, evenly spaced in a circle
    $giftSparkles = [];
    foreach ([0, 45, 90, 135, 180, 225, 270, 315] as $i => $angle) {
        $giftSparkles[] = [
            'tx' => round(cos(deg2rad($angle)) * 90),
            'ty' => round(sin(deg2rad($angle)) * 90),
            'delay' => $i * 0.18,
        ];
    }
@endphp

{{-- full-bleed section, matching the edge-to-edge treatment used by hero-slider/festival-special above it.
     pb trimmed vs. pt — this section and about-section.blade.php right after it both used to carry a full
     py-16/py-20, stacking into a ~160px gap between "Sweeten Every Celebration" and "Shop By Range" --}}
<section class="relative overflow-hidden pt-16 sm:pt-20 pb-8 sm:pb-10"
         style="background: linear-gradient(120deg, #052e12 0%, #16a34a 70%, #f59e0b 160%);"
         x-data="{ shown: false }"
         x-init="const io = new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { shown = true; io.disconnect(); } }, { threshold: 0.15 }); io.observe($el)">

    {{-- ambient glow + dot texture --}}
    <div class="pointer-events-none absolute inset-0 opacity-15" style="background-image: radial-gradient(circle, #fcd34d 1.5px, transparent 1.5px); background-size: 22px 22px;"></div>
    <div class="pointer-events-none absolute -top-20 right-[8%] w-96 h-96 rounded-full bg-gold-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 left-[4%] w-96 h-96 rounded-full bg-gold-500/15 blur-3xl"></div>

    {{-- thin gold hairlines top & bottom, giving the full-bleed block definition without boxing it in --}}
    <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold-300/60 to-transparent"></div>
    <div class="absolute bottom-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold-300/60 to-transparent"></div>

    {{-- fairy-light strand across the very top, echoing festival-special above it --}}
    <div class="pointer-events-none absolute top-3 sm:top-4 inset-x-0 flex justify-around px-4 sm:px-10">
        @for ($i = 0; $i < 26; $i++)
            <span class="w-1.5 h-1.5 rounded-full bg-gold-300 animate-bulb-glow" style="animation-delay: {{ $i * 0.12 }}s"></span>
        @endfor
    </div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-8 lg:px-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-10 items-center">
            <div class="text-center md:text-left transition-all duration-700 ease-out"
                 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
                <p class="text-gold-300 font-semibold tracking-widest uppercase text-sm mb-3">{{ __('Weddings · Festivals · Corporate') }}</p>
                <h2 class="font-display text-3xl sm:text-5xl font-bold text-cream leading-tight">
                    {{ __('Gift Every') }}<br>
                    <span class="relative inline-block">
                        {{ __('Celebration') }}
                        <svg class="absolute left-0 -bottom-1.5 w-full h-2.5 text-gold-400" viewBox="0 0 200 10" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M0 6 Q50 0 100 6 T200 6" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        </svg>
                    </span>
                </h2>
                <p class="text-gold-100/85 mt-5 text-lg max-w-md mx-auto md:mx-0">{{ __("Custom grocery hampers packed fresh to order — tell us the occasion, we'll put it together for you.") }}</p>

                {{-- feature rows, so the CTA isn't the only thing telling customers what they're getting --}}
                <div class="space-y-3.5 mt-7 max-w-md mx-auto md:mx-0">
                    @foreach ([
                        ['icon' => '🎁', 'title' => __('Custom Packaging'), 'desc' => __('Boxes wrapped and themed to match your occasion')],
                        ['icon' => '⚡', 'title' => __('Packed Fresh to Order'), 'desc' => __('Nothing sits around — prepared once your box is confirmed')],
                        ['icon' => '📦', 'title' => __('Any Quantity'), 'desc' => __('From a single hamper to a thousand-piece order')],
                    ] as $i => $feature)
                        <div class="flex items-center gap-3.5 text-left transition-all duration-500 ease-out"
                             :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                             style="transition-delay: {{ 250 + $i * 120 }}ms">
                            <span class="shrink-0 w-11 h-11 rounded-full bg-white/10 border border-gold-300/30 grid place-items-center text-xl">{{ $feature['icon'] }}</span>
                            <div class="min-w-0">
                                <p class="font-display font-semibold text-cream text-sm">{{ $feature['title'] }}</p>
                                <p class="text-gold-100/70 text-xs mt-0.5">{{ $feature['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($businessPhone)
                    <a href="https://wa.me/91{{ $businessPhone['digits'] }}?text={{ rawurlencode(__('Hi! I would like to plan a gift box order with Shree Vinayak Family Shop.')) }}"
                       target="_blank" rel="noopener" class="btn-gold mt-8">{{ __('Plan Your Order') }}</a>
                @else
                    {{-- no contact number configured — fall back to browsing instead of a dead WhatsApp link --}}
                    <a href="/#bestsellers" class="btn-gold mt-8">{{ __('Browse Store') }}</a>
                @endif
            </div>

            {{-- animated gift centerpiece --}}
            <div class="flex justify-center md:justify-start transition-all duration-700 ease-out"
                 style="transition-delay: 150ms"
                 :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-90'">
                <div class="relative w-full max-w-[22rem] aspect-square mx-8 sm:mx-0">
                    {{-- soft glow ring behind the box so the centerpiece claims more of the column --}}
                    <div class="pointer-events-none absolute inset-0 rounded-full bg-gold-400/20 blur-3xl scale-110"></div>

                    <div class="mithai-frame absolute inset-0 bg-gradient-to-br from-maroon-400/40 to-gold-500/30 backdrop-blur-sm flex items-center justify-center overflow-visible">
                        <div class="absolute inset-0 rounded-2xl overflow-hidden">
                            <img src="{{ asset('images/promo/gift-box-showcase.jpg') }}"
                                 alt="{{ __('Shree Vinayak Family Shop gift boxes with assorted treats') }}"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        </div>

                        <template x-if="shown">
                            <div>
                                @foreach ($giftSparkles as $sparkle)
                                    <span class="absolute top-1/2 left-1/2 w-2 h-2 rounded-full bg-gold-300 animate-gift-sparkle"
                                          style="--tx: {{ $sparkle['tx'] }}px; --ty: {{ $sparkle['ty'] }}px; animation-delay: {{ $sparkle['delay'] }}s"></span>
                                @endforeach
                            </div>
                        </template>
                    </div>

                    {{-- floating occasion chips, echoing the "Weddings · Festivals · Corporate" eyebrow --}}
                    <div class="absolute -top-6 -left-8 w-16 h-16 rounded-full bg-white shadow-lg grid place-items-center text-3xl animate-bounce" style="animation-duration: 3s">💍</div>
                    <div class="absolute top-1/2 -translate-y-1/2 -right-8 w-16 h-16 rounded-full bg-white shadow-lg grid place-items-center text-3xl animate-bounce" style="animation-duration: 3.4s; animation-delay: 0.4s">🪔</div>
                    <div class="absolute -bottom-6 left-8 w-16 h-16 rounded-full bg-white shadow-lg grid place-items-center text-3xl animate-bounce" style="animation-duration: 2.8s; animation-delay: 0.8s">🎂</div>
                </div>
            </div>
        </div>
    </div>
</section>
