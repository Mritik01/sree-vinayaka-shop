{{-- "Why Shree Vinayak?" feature strip — 4-across desktop (unchanged), a single horizontally
     scrollable row on mobile (below sm) instead of a cramped 2×2 grid.
     The delivery item reads the live admin-configured minutes from $store.shop. --}}
<section x-data class="bg-ivory">
    <div class="max-w-[1760px] mx-auto px-4 sm:px-8 lg:px-12 pt-5 sm:pt-6 pb-5 sm:pb-6">
        <div class="bg-white rounded-2xl border border-gold-200/60 shadow-sm px-4 sm:px-6 py-4 sm:py-5">
            <p class="sm:hidden text-center font-display font-bold text-maroon-800 mb-3">✦ {{ __('Why Shree Vinayak?') }} ✦</p>
            <div class="flex sm:grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-x-3 sm:gap-y-5 overflow-x-auto sm:overflow-visible snap-x snap-mandatory scroll-smooth pb-1 sm:pb-0 -mx-1 px-1 sm:mx-0 sm:px-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:divide-x sm:divide-gold-100">
                @foreach ([
                    ['icon' => '🌿', 'title' => __('Freshly Stocked Daily'), 'text' => __('New stock arrives every morning')],
                    ['icon' => '🏷️', 'title' => __('Best Prices'), 'text' => __('Great value on everyday essentials')],
                    ['icon' => '😊', 'title' => __('Happy Customers'), 'text' => __('Trusted by shoppers in Siswa Bazar')],
                ] as $feature)
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-2 sm:gap-3 text-center sm:text-left sm:px-4 first:sm:pl-0 shrink-0 w-[132px] sm:w-auto snap-start">
                        <span class="w-11 h-11 rounded-full bg-gold-50 border border-gold-200/70 flex items-center justify-center text-xl shrink-0">{{ $feature['icon'] }}</span>
                        <span>
                            <span class="block text-sm font-bold text-maroon-800 leading-snug">{{ $feature['title'] }}</span>
                            <span class="block text-xs text-maroon-500 mt-0.5 leading-snug">{{ $feature['text'] }}</span>
                        </span>
                    </div>
                @endforeach

                {{-- delivery feature — minutes are live from the admin setting; wording flips
                     to a fresh-delivery promise when the admin hides the estimate --}}
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-2 sm:gap-3 text-center sm:text-left sm:px-4 shrink-0 w-[132px] sm:w-auto snap-start">
                    <span class="w-11 h-11 rounded-full bg-gold-50 border border-gold-200/70 flex items-center justify-center text-xl shrink-0">🛵</span>
                    <span>
                        <span class="block text-sm font-bold text-maroon-800 leading-snug">
                            <template x-if="$store.shop.deliveryTimeMinutes > 0">
                                <span>{{ __('Fast Delivery in') }} <span x-text="$store.shop.deliveryTimeMinutes"></span> {{ __('Minutes') }}</span>
                            </template>
                            <template x-if="!($store.shop.deliveryTimeMinutes > 0)">
                                <span>{{ __('Fast Home Delivery') }}</span>
                            </template>
                        </span>
                        <span class="block text-xs text-maroon-500 mt-0.5 leading-snug">{{ __('Freshness delivered right to your door') }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>
