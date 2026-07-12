@php
    $slides = [
        [
            'image' => 'images/hero/hero-1.jpg',
            'eyebrow' => __('No. 1 Sweet Shop in Thuthibari'),
            'title' => __('Celebrating Moments, Creating Sweet Memories'),
            'subtitle' => __('Pure ingredients. Authentic taste. Made with love.'),
            'cta' => ['label' => __('Explore Our Sweets'), 'href' => '#bestsellers'],
            'badges' => [__('Premium Quality'), __('Made with Love'), __('Pure & Fresh'), __('Every Occasion')],
        ],
        [
            'image' => 'images/hero/hero-2.jpg',
            'eyebrow' => __('Fresh Every Morning'),
            'title' => __('Handcrafted Mithai, Straight From Thuthibari'),
            'subtitle' => __('100% pure ghee. Traditional recipes. No shortcuts, ever.'),
            'cta' => ['label' => __('Order Now — Cash on Delivery'), 'href' => '#bestsellers'],
            'badges' => [],
        ],
        [
            'image' => 'images/hero/hero-3.jpg',
            'eyebrow' => __('Something For Everyone'),
            'title' => __('From Classic Mithai to Everyday Cravings'),
            'subtitle' => __('Sweets, snacks and more — all made fresh, all made right.'),
            'cta' => ['label' => __('Explore Our Sweets'), 'href' => '#bestsellers'],
            'badges' => [],
        ],
        [
            'image' => 'images/hero/hero-4.jpg',
            'eyebrow' => __('Crafted With Care'),
            'title' => __('Every Sweet, Made By Hand'),
            'subtitle' => __('Our halwais start before sunrise so your mithai is always fresh.'),
            'cta' => ['label' => __('See Our Kitchen'), 'href' => '#range'],
            'badges' => [],
            'focus' => 'object-top origin-top',
        ],
        [
            'image' => 'images/hero/hero-5.jpg',
            'eyebrow' => __('Loved By Thuthibari'),
            'title' => __("The Sweet Box Everyone's Taking Home"),
            'subtitle' => __('Perfect for gifting, celebrating, or simply treating yourself.'),
            'cta' => ['label' => __('Order Now — Cash on Delivery'), 'href' => '#bestsellers'],
            'badges' => [],
            'scrim' => 'from-maroon-900/90 via-maroon-900/60 to-maroon-900/10',
            'focus' => 'object-top origin-top',
        ],
    ];
@endphp

<section id="home"
    x-data="heroSlider({{ count($slides) }})"
    @mouseenter="paused = true" @mouseleave="paused = false"
    class="relative h-[85vh] min-h-[560px] max-h-[760px] overflow-hidden bg-maroon-900">

    @foreach ($slides as $i => $slide)
        <div x-show="active === {{ $i }}"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0"
             style="display: none;">

            {{-- background photo with slow Ken Burns zoom --}}
            <img src="{{ asset($slide['image']) }}" alt="{{ $slide['title'] }}"
                 class="absolute inset-0 w-full h-full object-cover {{ $slide['focus'] ?? 'object-center origin-center' }} animate-kenburns">

            {{-- warm maroon scrim so text stays legible on both light & dark photos --}}
            <div class="absolute inset-0 bg-gradient-to-r {{ $slide['scrim'] ?? 'from-maroon-900/85 via-maroon-900/45 to-transparent' }}"></div>

            <div class="relative z-10 h-full flex items-center px-6 sm:px-10 lg:px-20">
                <div class="max-w-xl">
                    <p class="animate-fade-up [animation-delay:100ms] text-gold-300 font-semibold tracking-widest uppercase text-xs sm:text-sm mb-4">
                        {{ $slide['eyebrow'] }}
                    </p>
                    <h1 class="animate-fade-up [animation-delay:250ms] text-3xl sm:text-5xl lg:text-6xl font-bold text-cream leading-tight drop-shadow-md mb-5">
                        {{ $slide['title'] }}
                    </h1>
                    <p class="animate-fade-up [animation-delay:400ms] text-base sm:text-lg text-gold-100/90 mb-8">
                        {{ $slide['subtitle'] }}
                    </p>

                    @if (!empty($slide['badges']))
                        <div class="animate-fade-up [animation-delay:550ms] grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
                            @foreach ($slide['badges'] as $badge)
                                <div class="flex items-center gap-2 text-cream/90 text-xs sm:text-sm">
                                    <span class="w-7 h-7 rounded-full border border-gold-300/70 flex items-center justify-center shrink-0 text-gold-300">✦</span>
                                    {{ $badge }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <a href="{{ $slide['cta']['href'] }}"
                       @if(str_starts_with($slide['cta']['href'], 'http')) target="_blank" rel="noopener" @endif
                       class="animate-fade-up [animation-delay:700ms] btn-gold inline-block">
                        {{ $slide['cta']['label'] }}
                    </a>
                </div>
            </div>
        </div>
    @endforeach

    <button @click="prev()" aria-label="Previous slide"
        class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-xl bg-cream/15 hover:bg-cream/35 text-cream flex items-center justify-center backdrop-blur transition text-xl">
        &#8249;
    </button>
    <button @click="next()" aria-label="Next slide"
        class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-xl bg-cream/15 hover:bg-cream/35 text-cream flex items-center justify-center backdrop-blur transition text-xl">
        &#8250;
    </button>

    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 flex gap-2">
        @foreach ($slides as $i => $slide)
            <button @click="goTo({{ $i }})"
                :class="active === {{ $i }} ? 'bg-gold-400 w-8' : 'bg-cream/40 w-2.5'"
                class="h-2.5 rounded-full transition-all duration-300"
                aria-label="Go to slide {{ $i + 1 }}">
            </button>
        @endforeach
    </div>

    <div class="absolute bottom-0 left-0 right-0 z-20">
        @include('partials.trim', ['fill' => '#fdf6e9'])
    </div>
</section>
