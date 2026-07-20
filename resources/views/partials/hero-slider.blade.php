{{-- hero banner carousel — fully admin-managed (Admin → Hero Banners). $heroBanners comes from
     the home route (HeroBanner::active()). Contained rounded banner per the reference design;
     reuses the existing heroSlider() Alpine autoplay/arrows/dots. --}}
@if ($heroBanners->isNotEmpty())
    <section id="home" class="bg-ivory">
        <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 pt-4 sm:pt-6"
             x-data="heroSlider({{ $heroBanners->count() }})">
            <div @mouseenter="paused = true" @mouseleave="paused = false"
                 class="relative rounded-2xl sm:rounded-3xl overflow-hidden bg-maroon-900 h-[240px] sm:h-[300px] lg:h-[360px] xl:h-[420px] shadow-md">

                @foreach ($heroBanners as $i => $banner)
                    {{-- first slide renders visible server-side (no inline display:none) so its
                         image — the page's LCP element — can paint before Alpine has hydrated,
                         instead of waiting on JS to reveal it; matches heroSlider()'s active:0
                         initial state so there's no mismatch once Alpine mounts --}}
                    <div x-show="active === {{ $i }}"
                         x-transition:enter="transition ease-out duration-700"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-500"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute inset-0"
                         @if ($i > 0) style="display: none;" @endif>

                        @php
                            // a smaller companion image (see HeroBannerController::generateMobileVariant)
                            // so phones don't download the same desktop-width crop — file_exists guards
                            // banners uploaded before this existed, so it degrades to plain $src cleanly
                            $mobilePath = \App\Http\Controllers\Admin\HeroBannerController::mobileVariantPath($banner->image_path);
                            $hasMobileVariant = file_exists(public_path($mobilePath));
                            if ($hasMobileVariant) {
                                $fullSize = @getimagesize(public_path($banner->image_path));
                                $mobileSize = @getimagesize(public_path($mobilePath));
                            }
                        @endphp
                        <img src="{{ asset($banner->image_path) }}" alt="{{ $banner->title }}"
                             @if ($hasMobileVariant && $fullSize && $mobileSize)
                                 srcset="{{ asset($mobilePath) }} {{ $mobileSize[0] }}w, {{ asset($banner->image_path) }} {{ $fullSize[0] }}w"
                                 sizes="100vw"
                             @endif
                             @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif
                             class="absolute inset-0 w-full h-full object-cover animate-kenburns">

                        {{-- warm maroon scrim keeps any uploaded photo legible behind the text --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-maroon-900/85 via-maroon-900/45 to-transparent"></div>

                        <div class="relative z-10 h-full flex items-center px-5 sm:px-10 lg:px-14">
                            <div class="max-w-md lg:max-w-lg">
                                @if ($banner->eyebrow)
                                    <p class="animate-fade-up [animation-delay:100ms] text-gold-300 font-semibold tracking-widest uppercase text-[10px] sm:text-xs mb-2 sm:mb-3">
                                        {{ $banner->eyebrow }}
                                    </p>
                                @endif
                                <h1 class="animate-fade-up [animation-delay:250ms] text-xl sm:text-3xl lg:text-4xl font-bold text-cream leading-tight drop-shadow-md mb-2 sm:mb-3">
                                    {{ $banner->title }}
                                </h1>
                                @if ($banner->subtitle)
                                    <p class="animate-fade-up [animation-delay:400ms] text-xs sm:text-base text-gold-100/90 mb-4 sm:mb-6">
                                        {{ $banner->subtitle }}
                                    </p>
                                @endif
                                @if ($banner->button_text)
                                    <a href="{{ $banner->button_url ?: '#bestsellers' }}"
                                       @if(str_starts_with($banner->button_url ?? '', 'http')) target="_blank" rel="noopener" @endif
                                       class="animate-fade-up [animation-delay:550ms] inline-block bg-maroon-800 hover:bg-maroon-700 text-cream text-xs sm:text-sm font-semibold px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl shadow transition">
                                        {{ $banner->button_text }} <span aria-hidden="true">→</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                @if ($heroBanners->count() > 1)
                    <button @click="prev()" aria-label="Previous slide"
                        class="hidden sm:flex absolute left-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-maroon-900/50 hover:bg-maroon-900/80 text-cream items-center justify-center backdrop-blur transition text-lg">
                        &#8249;
                    </button>
                    <button @click="next()" aria-label="Next slide"
                        class="hidden sm:flex absolute right-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-maroon-900/50 hover:bg-maroon-900/80 text-cream items-center justify-center backdrop-blur transition text-lg">
                        &#8250;
                    </button>
                @endif
            </div>

            {{-- dot indicators below the banner, per the reference --}}
            @if ($heroBanners->count() > 1)
                <div class="flex justify-center gap-2 mt-3">
                    @foreach ($heroBanners as $i => $banner)
                        <button @click="goTo({{ $i }})"
                            :class="active === {{ $i }} ? 'bg-maroon-700 w-2.5' : 'bg-maroon-900/20 w-2.5 hover:bg-maroon-900/40'"
                            class="h-2.5 rounded-full transition-all duration-300"
                            aria-label="Go to slide {{ $i + 1 }}">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
