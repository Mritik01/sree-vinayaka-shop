@php
    // shop hours are IST regardless of the app's configured (UTC) timezone
    $outletHourIst = now('Asia/Kolkata')->hour;
    $outletIsOpen = $outletHourIst >= 8 && $outletHourIst < 21;
@endphp

<section class="relative overflow-hidden" style="background: linear-gradient(135deg, #052e12 0%, #0a3d1a 60%, #062915 100%);"
         x-data="{ shown: false }"
         x-init="const io = new IntersectionObserver((entries) => { if (entries[0].isIntersecting) { shown = true; io.disconnect(); } }, { threshold: 0.15 }); io.observe($el)">
    {{-- ambient glow, echoing the promo banner above --}}
    <div class="pointer-events-none absolute top-1/4 -left-16 w-80 h-80 rounded-full bg-gold-400/10 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-0 -right-16 w-96 h-96 rounded-full bg-gold-500/10 blur-3xl"></div>

    <div class="relative max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 md:gap-16 items-center">
            <div class="relative transition-all duration-700 ease-out mb-6 md:mb-0"
                 :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-6'">
                <div class="mithai-frame h-72 sm:h-96 relative overflow-hidden group">
                    <img src="{{ asset('images/outlet/storefront.jpg') }}" alt="{{ __('Shree Vinayak Family Shop storefront in Siswa Bazar') }}" loading="lazy"
                         class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-maroon-900/60 via-transparent to-transparent"></div>
                </div>

                {{-- floating welcome badge --}}
                <div class="absolute -bottom-5 -right-3 sm:-bottom-6 sm:-right-6 bg-white rounded-2xl shadow-xl px-5 py-3.5 flex items-center gap-3 border border-gold-200 animate-rise-in" style="animation-delay: 0.5s">
                    <span class="text-3xl animate-gift-sway inline-block">🪔</span>
                    <p class="font-display font-bold text-maroon-800 text-sm leading-tight">{{ __('Walk-ins') }}<br>{{ __('Welcome') }}</p>
                </div>
            </div>

            <div class="text-center md:text-left transition-all duration-700 ease-out" style="transition-delay: 150ms"
                 :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-6'">
                <p class="text-gold-300 font-semibold tracking-widest uppercase text-sm mb-3">{{ __('Our Outlet') }}</p>
                <h2 class="font-display text-3xl sm:text-5xl font-bold text-cream leading-tight">{{ __('Come, Grab It Fresh') }}</h2>
                <p class="text-gold-100/85 mt-4">{{ __('Nothing beats picking it up fresh from the counter. Drop by our outlet — quick visit, always in stock.') }}</p>

                <div class="mt-8 space-y-3 text-cream/90 text-sm">
                    <p class="flex items-center justify-center md:justify-start gap-3">📍 {{ __('Roadways Bus Stand, Nichlaul Road, Siswa Bazar, Maharajganj, UP 273163') }}</p>
                    <p class="flex items-center justify-center md:justify-start gap-3">🕗 {{ __('Open Daily') }} · 8:00 AM – 9:00 PM
                        @if ($outletIsOpen)
                            <span class="bg-pista-500 text-white text-xs font-semibold px-3 py-1 rounded-md">{{ __('Open Now') }}</span>
                        @else
                            <span class="bg-white/10 text-cream/70 border border-cream/20 text-xs font-semibold px-3 py-1 rounded-md">{{ __('Closed Now') }}</span>
                        @endif
                    </p>
                    @if ($businessPhone)
                        <p class="flex items-center justify-center md:justify-start gap-3">📞 <a href="{{ $businessPhone['tel'] }}" class="hover:text-gold-300 transition">{{ $businessPhone['display'] }}</a></p>
                    @endif
                </div>

                <a href="https://www.google.com/maps/place/Vinayak+family+shop/@27.1555715,83.7583867,116m/data=!3m1!1e3!4m15!1m8!3m7!1s0x399405f255a1917b:0x6a81af17a7f824bc!2sSiswa+Bazar,+Uttar+Pradesh+273163!3b1!8m2!3d27.1496918!4d83.7597505!16s%2Fg%2F11c1pcy3jv!3m5!1s0x399405c96c7bf3d1:0x2b1f6ee963c89b2d!8m2!3d27.155558!4d83.7584378!16s%2Fg%2F11fs1rllp9?entry=ttu&amp;g_ep=EgoyMDI2MDcxOS4wIKXMDSoASAFQAw%3D%3D" target="_blank" rel="noopener" class="btn-gold mt-8">{{ __('Get Directions') }}</a>
            </div>
        </div>
    </div>
</section>
