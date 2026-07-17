{{-- "Deliver to <address>" selector — $headerAddresses comes from navbar.blade.php's @php block.
     Logged-in: shows the default address, dropdown switches it via the existing
     addresses.set-default endpoint. Guest: opens the auth modal. --}}
<div x-data='deliverTo(@json($headerAddresses), {{ Auth::check() ? 'true' : 'false' }}, @json(['empty' => __('Add your address'), 'guest' => __('Thuthibari & nearby')]))' @click.outside="open = false" class="relative">
    <button type="button" @click="isLoggedIn && addresses.length > 0 ? open = !open : handleEmpty()"
            class="w-full flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-maroon-900/5 transition text-left min-w-0">
        <svg class="w-5 h-5 text-maroon-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
        <span class="min-w-0 leading-tight">
            <span class="block text-[10px] font-semibold text-maroon-400 uppercase tracking-wide">{{ __('Deliver to') }}</span>
            <span class="flex items-center gap-1 text-xs sm:text-sm font-semibold text-maroon-800">
                <span class="truncate" x-text="currentLine"></span>
                <svg x-show="isLoggedIn && addresses.length > 0" class="w-3 h-3 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </span>
        </span>
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute left-0 top-full mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-2xl bg-white border border-gold-200/70 shadow-2xl overflow-hidden z-[70]">
        <p class="px-4 pt-3 pb-2 text-[10px] font-bold text-maroon-400 uppercase tracking-wide">{{ __('Choose delivery address') }}</p>
        <template x-for="a in addresses" :key="a.id">
            <button type="button" @click="choose(a)"
                    class="w-full flex items-start gap-2.5 px-4 py-2.5 text-left hover:bg-cream/70 transition"
                    :class="a.id === currentId && 'bg-gold-50'">
                <span class="mt-0.5 shrink-0" x-text="a.id === currentId ? '📍' : '🏠'"></span>
                <span class="min-w-0">
                    <span class="block text-xs font-bold text-maroon-800" x-text="a.label || '{{ __('Address') }}'"></span>
                    <span class="block text-xs text-maroon-500 leading-snug line-clamp-2" x-text="a.line"></span>
                </span>
            </button>
        </template>
        <a href="{{ route('account', ['tab' => 'addresses']) }}"
           class="block text-center text-xs font-semibold text-gold-600 hover:text-gold-700 hover:bg-cream/70 py-2.5 border-t border-gold-100 transition">
            + {{ __('Add or manage addresses') }}
        </a>
    </div>
</div>
